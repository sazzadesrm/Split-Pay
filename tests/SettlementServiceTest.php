<?php
declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use App\Core\Env;
use App\Services\SettlementService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/** Integration test: requires a configured test database (see BalanceServiceTest). */
final class SettlementServiceTest extends TestCase
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

    public function testPayerAndReceiverMustDiffer(): void
    {
        $service = new SettlementService();
        $this->expectException(InvalidArgumentException::class);
        $service->create([
            'payer_id' => 1, 'receiver_id' => 1, 'amount' => '10.00', 'currency' => 'USD',
            'payment_method' => 'cash', 'settlement_date' => date('Y-m-d'),
        ], 1, 1);
    }

    public function testAmountMustBePositive(): void
    {
        $service = new SettlementService();
        $this->expectException(InvalidArgumentException::class);
        $service->create([
            'payer_id' => 1, 'receiver_id' => 2, 'amount' => '0.00', 'currency' => 'USD',
            'payment_method' => 'cash', 'settlement_date' => date('Y-m-d'),
        ], 1, 1);
    }

    public function testCreateAndCompleteSettlement(): void
    {
        $service = new SettlementService();
        $id = $service->create([
            'payer_id' => 2, 'receiver_id' => 3, 'amount' => '5.00', 'currency' => 'USD',
            'payment_method' => 'cash', 'settlement_date' => date('Y-m-d'), 'status' => 'pending',
        ], 1, 2);
        $this->assertIsInt($id);

        $service->complete($id, 1, 2);

        $this->expectException(\RuntimeException::class);
        $service->complete($id, 1, 2); // cannot complete twice
    }
}
