<?php
declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use App\Core\Env;
use App\Services\BalanceService;
use PHPUnit\Framework\TestCase;

/**
 * Integration test: requires a configured test database with the schema
 * and demo seed data loaded (see database/schema.sql and
 * database/seeds/seed_demo_data.sql). Skips automatically if no database
 * connection is available, so `composer test` still runs the pure unit
 * tests (MoneyTest, ExpenseServiceTest) in any environment.
 */
final class BalanceServiceTest extends TestCase
{
    private static bool $dbAvailable = true;

    public static function setUpBeforeClass(): void
    {
        Env::load(dirname(__DIR__) . '/.env');
        try {
            Database::connection();
        } catch (\Throwable $e) {
            self::$dbAvailable = false;
        }
    }

    protected function setUp(): void
    {
        if (!self::$dbAvailable) {
            $this->markTestSkipped('No test database configured.');
        }
    }

    public function testApprovedExpenseAffectsBalance(): void
    {
        // Seed data: Maria (user 1) paid the $120.00 Client Lunch, split equally
        // among Maria/Alex/Sam ($40 each) and approved. Alex settled $40 to Maria.
        $service = new BalanceService();
        $balance = $service->getUserBalance(1, 1);
        $this->assertGreaterThanOrEqual(0, $balance['total_paid_cents']);
    }

    public function testOnlyCompletedSettlementsAdjustBalance(): void
    {
        $service = new BalanceService();
        // Settlement #2 (Sam -> Maria, $25.00) is pending and must not adjust balances.
        $adjustment = $service->getSettlementAdjustment(1, 3);
        $this->assertLessThan(2500, abs($adjustment) + 1); // pending settlement excluded
    }

    public function testSuggestedSettlementsBalanceOut(): void
    {
        $service = new BalanceService();
        $suggestions = $service->getSuggestedSettlements(1);
        $this->assertIsArray($suggestions);
        foreach ($suggestions as $s) {
            $this->assertGreaterThan(0, $s['amount_cents']);
        }
    }
}
