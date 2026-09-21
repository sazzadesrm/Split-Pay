<?php
declare(strict_types=1);

namespace Tests;

use App\Services\ExpenseService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ExpenseServiceTest extends TestCase
{
    private ExpenseService $service;

    protected function setUp(): void
    {
        $this->service = new ExpenseService();
    }

    public function testEqualSplitSumsToTotal(): void
    {
        $rows = $this->service->calculateSplit('equal', 10000, [1, 2, 3]);
        $this->assertSame(10000, array_sum(array_column($rows, 'owed_cents')));
        $this->assertSame(3334, $rows[0]['owed_cents']);
    }

    public function testExactSplitMustSumToTotal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->calculateSplit('exact', 15000, [1, 2, 3], [1 => '50.00', 2 => '60.00', 3 => '30.00']);
    }

    public function testExactSplitAcceptsMatchingTotal(): void
    {
        $rows = $this->service->calculateSplit('exact', 15000, [1, 2, 3], [1 => '50.00', 2 => '60.00', 3 => '40.00']);
        $this->assertSame(15000, array_sum(array_column($rows, 'owed_cents')));
    }

    public function testPercentageMustTotal10000BasisPoints(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->calculateSplit('percentage', 20000, [1, 2], [], [1 => '50.00', 2 => '40.00']);
    }

    public function testPercentageSplitMatchesWorkedExample(): void
    {
        $rows = $this->service->calculateSplit('percentage', 20000, [1, 2, 3], [], [1 => '50.00', 2 => '25.00', 3 => '25.00']);
        $owed = array_column($rows, 'owed_cents');
        $this->assertSame([10000, 5000, 5000], $owed);
    }

    public function testSharesSplitMatchesWorkedExample(): void
    {
        $rows = $this->service->calculateSplit('shares', 12000, [1, 2, 3], [], [], [1 => 2, 2 => 1, 3 => 1]);
        $owed = array_column($rows, 'owed_cents');
        $this->assertSame([6000, 3000, 3000], $owed);
    }

    public function testSharesMustBeGreaterThanZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->calculateSplit('shares', 12000, [1, 2], [], [], [1 => 0, 2 => 2]);
    }

    public function testOneCentAmongSeveralParticipants(): void
    {
        $rows = $this->service->calculateSplit('equal', 1, [1, 2, 3]);
        $this->assertSame(1, array_sum(array_column($rows, 'owed_cents')));
    }

    public function testRequiresAtLeastOneParticipant(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->calculateSplit('equal', 1000, []);
    }
}
