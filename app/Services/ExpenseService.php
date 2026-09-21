<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Money;
use App\Models\Expense;
use App\Models\Tag;
use InvalidArgumentException;

final class ExpenseService
{
    private Expense $expenses;
    private Tag $tags;
    private ActivityLogService $activity;
    private NotificationService $notifications;

    public function __construct()
    {
        $this->expenses = new Expense();
        $this->tags = new Tag();
        $this->activity = new ActivityLogService();
        $this->notifications = new NotificationService();
    }

    /**
     * Calculate per-participant owed cents for a given split type.
     *
     * @param string $splitType equal|exact|percentage|shares
     * @param int[] $participantUserIds
     * @param array<int,string> $exactAmounts user_id => decimal string (exact)
     * @param array<int,string> $percentages user_id => decimal string percentage (percentage)
     * @param array<int,int> $shares user_id => integer shares (shares)
     * @return array<int,array{user_id:int, owed_cents:int, percentage:?string, shares:?int}>
     */
    public function calculateSplit(
        string $splitType,
        int $totalCents,
        array $participantUserIds,
        array $exactAmounts = [],
        array $percentages = [],
        array $shares = []
    ): array {
        $participantUserIds = array_values(array_unique($participantUserIds));
        $count = count($participantUserIds);
        if ($count < 1) {
            throw new InvalidArgumentException('At least one participant is required.');
        }

        $result = [];

        switch ($splitType) {
            case 'equal':
                $amounts = Money::splitEqual($totalCents, $count);
                foreach ($participantUserIds as $i => $userId) {
                    $result[] = ['user_id' => $userId, 'owed_cents' => $amounts[$i], 'percentage' => null, 'shares' => null];
                }
                break;

            case 'exact':
                $sum = 0;
                foreach ($participantUserIds as $userId) {
                    $cents = Money::toCents((string) ($exactAmounts[$userId] ?? '0'));
                    if ($cents < 0) {
                        throw new InvalidArgumentException('Exact amounts must be non-negative.');
                    }
                    $sum += $cents;
                    $result[] = ['user_id' => $userId, 'owed_cents' => $cents, 'percentage' => null, 'shares' => null];
                }
                if ($sum !== $totalCents) {
                    throw new InvalidArgumentException('Exact split amounts must sum exactly to the expense total.');
                }
                break;

            case 'percentage':
                $basisPoints = [];
                $sumBp = 0;
                foreach ($participantUserIds as $userId) {
                    $pct = (string) ($percentages[$userId] ?? '0');
                    if (!preg_match('/^\d+(\.\d{1,2})?$/', $pct)) {
                        throw new InvalidArgumentException('Invalid percentage value.');
                    }
                    $bp = (int) round(((float) $pct) * 100);
                    $basisPoints[] = $bp;
                    $sumBp += $bp;
                }
                if ($sumBp !== 10000) {
                    throw new InvalidArgumentException('Percentages must total exactly 100.00%.');
                }
                $amounts = Money::splitByBasisPoints($totalCents, $basisPoints);
                foreach ($participantUserIds as $i => $userId) {
                    $result[] = [
                        'user_id' => $userId,
                        'owed_cents' => $amounts[$i],
                        'percentage' => number_format($basisPoints[$i] / 100, 2, '.', ''),
                        'shares' => null,
                    ];
                }
                break;

            case 'shares':
                $shareValues = [];
                foreach ($participantUserIds as $userId) {
                    $s = (int) ($shares[$userId] ?? 0);
                    if ($s <= 0) {
                        throw new InvalidArgumentException('Each participant must hold shares greater than zero.');
                    }
                    $shareValues[] = $s;
                }
                $amounts = Money::splitByShares($totalCents, $shareValues);
                foreach ($participantUserIds as $i => $userId) {
                    $result[] = ['user_id' => $userId, 'owed_cents' => $amounts[$i], 'percentage' => null, 'shares' => $shareValues[$i]];
                }
                break;

            default:
                throw new InvalidArgumentException('Unknown split type.');
        }

        $sumCheck = array_sum(array_column($result, 'owed_cents'));
        if ($sumCheck !== $totalCents) {
            throw new InvalidArgumentException('Calculated split does not equal the expense total.');
        }

        return $result;
    }

    /**
     * Create an expense with splits, tags and activity log inside a transaction.
     */
    public function create(array $expenseData, array $splitRows, array $tagNames, int $teamId, int $actorId): int
    {
        Database::beginTransaction();
        try {
            $expenseData['team_id'] = $teamId;
            $expenseData['created_at'] = date('Y-m-d H:i:s');
            $expenseId = $this->expenses->insert($expenseData);

            $this->saveSplits($expenseId, $splitRows);
            $this->saveTags($expenseId, $teamId, $tagNames);

            $this->activity->log($teamId, $actorId, 'expense.created', 'expense', $expenseId, "Expense \"{$expenseData['title']}\" was created.");

            Database::commit();
            return $expenseId;
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public function update(int $expenseId, array $expenseData, array $splitRows, array $tagNames, int $teamId, int $actorId, bool $wasApproved): void
    {
        Database::beginTransaction();
        try {
            $this->expenses->update($expenseId, $expenseData);

            $this->expenses->execute('DELETE FROM expense_splits WHERE expense_id = :id', ['id' => $expenseId]);
            $this->saveSplits($expenseId, $splitRows);

            $this->expenses->execute('DELETE FROM expense_tags WHERE expense_id = :id', ['id' => $expenseId]);
            $this->saveTags($expenseId, $teamId, $tagNames);

            if ($wasApproved) {
                $this->expenses->update($expenseId, [
                    'status' => 'submitted',
                    'approved_by' => null,
                    'approved_at' => null,
                    'rejected_by' => null,
                    'rejected_at' => null,
                    'approval_notes' => null,
                ]);
                $this->activity->log($teamId, $actorId, 'expense.material_edit', 'expense', $expenseId, 'A material edit reset the expense to submitted for re-approval.');
            } else {
                $this->activity->log($teamId, $actorId, 'expense.updated', 'expense', $expenseId, 'Expense details were updated.');
            }

            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    private function saveSplits(int $expenseId, array $splitRows): void
    {
        foreach ($splitRows as $row) {
            $this->expenses->execute(
                'INSERT INTO expense_splits (expense_id, user_id, owed_amount, split_percentage, shares, created_at)
                 VALUES (:expense_id, :user_id, :owed_amount, :percentage, :shares, :created_at)',
                [
                    'expense_id' => $expenseId,
                    'user_id' => $row['user_id'],
                    'owed_amount' => Money::toDecimal($row['owed_cents']),
                    'percentage' => $row['percentage'],
                    'shares' => $row['shares'],
                    'created_at' => date('Y-m-d H:i:s'),
                ]
            );
        }
    }

    private function saveTags(int $expenseId, int $teamId, array $tagNames): void
    {
        foreach (array_slice(array_unique($tagNames), 0, 10) as $name) {
            $normalized = Tag::normalize($name);
            if ($normalized === '') {
                continue;
            }
            $tag = $this->tags->findByName($teamId, $normalized);
            $tagId = $tag['id'] ?? $this->tags->insert([
                'team_id' => $teamId,
                'name' => $normalized,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->expenses->execute(
                'INSERT IGNORE INTO expense_tags (expense_id, tag_id) VALUES (:e, :t)',
                ['e' => $expenseId, 't' => $tagId]
            );
        }
    }

    public function submit(int $expenseId, int $teamId, int $actorId): void
    {
        $this->expenses->update($expenseId, ['status' => 'submitted']);
        $this->activity->log($teamId, $actorId, 'expense.submitted', 'expense', $expenseId, 'Expense submitted for approval.');
    }

    public function approve(int $expenseId, int $teamId, int $actorId, ?string $notes = null): void
    {
        $this->expenses->update($expenseId, [
            'status' => 'approved',
            'approved_by' => $actorId,
            'approved_at' => date('Y-m-d H:i:s'),
            'approval_notes' => $notes,
        ]);
        $this->activity->log($teamId, $actorId, 'expense.approved', 'expense', $expenseId, 'Expense approved.');
    }

    public function reject(int $expenseId, int $teamId, int $actorId, string $reason): void
    {
        $this->expenses->update($expenseId, [
            'status' => 'rejected',
            'rejected_by' => $actorId,
            'rejected_at' => date('Y-m-d H:i:s'),
            'approval_notes' => $reason,
        ]);
        $this->activity->log($teamId, $actorId, 'expense.rejected', 'expense', $expenseId, "Expense rejected: $reason");
    }

    public function reimburse(int $expenseId, int $teamId, int $actorId): void
    {
        $this->expenses->update($expenseId, [
            'status' => 'reimbursed',
            'reimbursed_by' => $actorId,
            'reimbursed_at' => date('Y-m-d H:i:s'),
        ]);
        $this->activity->log($teamId, $actorId, 'expense.reimbursed', 'expense', $expenseId, 'Expense marked as reimbursed.');
    }

    public function withdraw(int $expenseId, int $teamId, int $actorId): void
    {
        $this->expenses->update($expenseId, ['status' => 'draft']);
        $this->activity->log($teamId, $actorId, 'expense.withdrawn', 'expense', $expenseId, 'Expense withdrawn to draft.');
    }

    public function softDelete(int $expenseId, int $teamId, int $actorId, ?string $reason = null): void
    {
        $this->expenses->update($expenseId, [
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $actorId,
            'deletion_reason' => $reason,
        ]);
        $this->activity->log($teamId, $actorId, 'expense.deleted', 'expense', $expenseId, 'Expense deleted.' . ($reason ? " Reason: $reason" : ''));
    }
}
