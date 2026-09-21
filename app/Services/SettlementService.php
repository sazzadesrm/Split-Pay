<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Settlement;
use InvalidArgumentException;
use RuntimeException;

final class SettlementService
{
    private Settlement $settlements;
    private ActivityLogService $activity;
    private NotificationService $notifications;

    public function __construct()
    {
        $this->settlements = new Settlement();
        $this->activity = new ActivityLogService();
        $this->notifications = new NotificationService();
    }

    public function create(array $data, int $teamId, int $actorId): int
    {
        if ((float) $data['amount'] <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }
        if ((int) $data['payer_id'] === (int) $data['receiver_id']) {
            throw new InvalidArgumentException('Payer and receiver must be different people.');
        }

        Database::beginTransaction();
        try {
            $id = $this->settlements->insert([
                'team_id' => $teamId,
                'payer_id' => $data['payer_id'],
                'receiver_id' => $data['receiver_id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'payment_method' => $data['payment_method'],
                'reference_note' => $data['reference_note'] ?? null,
                'settlement_date' => $data['settlement_date'],
                'status' => $data['status'] ?? 'pending',
                'created_by' => $actorId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->activity->log($teamId, $actorId, 'settlement.created', 'settlement', $id, 'Settlement recorded.');
            $this->notifications->notifyMany(
                [(int) $data['payer_id'], (int) $data['receiver_id']],
                $teamId,
                'settlement_created',
                'Settlement recorded',
                'A settlement was recorded between team members.',
                '/settlements'
            );
            Database::commit();
            return $id;
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public function complete(int $settlementId, int $teamId, int $actorId): void
    {
        Database::beginTransaction();
        try {
            $settlement = $this->settlements->find($settlementId);
            if ($settlement === null || (int) $settlement['team_id'] !== $teamId) {
                throw new RuntimeException('Settlement not found.');
            }
            if ($settlement['status'] !== 'pending') {
                throw new RuntimeException('Only pending settlements can be completed.');
            }
            $this->settlements->update($settlementId, [
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
            ]);
            $this->activity->log($teamId, $actorId, 'settlement.completed', 'settlement', $settlementId, 'Settlement completed.');
            $this->notifications->notifyMany(
                [(int) $settlement['payer_id'], (int) $settlement['receiver_id']],
                $teamId,
                'settlement_completed',
                'Settlement completed',
                'A settlement was marked as completed.',
                '/settlements'
            );
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public function cancel(int $settlementId, int $teamId, int $actorId): void
    {
        $settlement = $this->settlements->find($settlementId);
        if ($settlement === null || (int) $settlement['team_id'] !== $teamId) {
            throw new RuntimeException('Settlement not found.');
        }
        $this->settlements->update($settlementId, ['status' => 'cancelled']);
        $this->activity->log($teamId, $actorId, 'settlement.cancelled', 'settlement', $settlementId, 'Settlement cancelled.');
    }

    public function delete(int $settlementId, int $teamId, int $actorId): void
    {
        $settlement = $this->settlements->find($settlementId);
        if ($settlement === null || (int) $settlement['team_id'] !== $teamId) {
            throw new RuntimeException('Settlement not found.');
        }
        if ($settlement['status'] === 'completed') {
            throw new RuntimeException('Completed settlements cannot be deleted; cancel or reverse them instead.');
        }
        $this->settlements->softDelete($settlementId);
        $this->activity->log($teamId, $actorId, 'settlement.deleted', 'settlement', $settlementId, 'Settlement deleted.');
    }
}
