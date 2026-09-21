<?php
declare(strict_types=1);

namespace Tests;

use App\Core\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testToCentsAndBack(): void
    {
        $this->assertSame(12345, Money::toCents('123.45'));
        $this->assertSame('123.45', Money::toDecimal(12345));
        $this->assertSame(100, Money::toCents('1'));
        $this->assertSame(1, Money::toCents('0.01'));
    }

    public function testSplitEqualDistributesRemainderToFirstParticipants(): void
    {
        // $100.00 among 3 participants: 33.34 / 33.33 / 33.33
        $result = Money::splitEqual(10000, 3);
        $this->assertSame([3334, 3333, 3333], $result);
        $this->assertSame(10000, array_sum($result));
    }

    public function testSplitEqualExactDivision(): void
    {
        $result = Money::splitEqual(12000, 3);
        $this->assertSame([4000, 4000, 4000], $result);
    }

    public function testSplitEqualOneCentAmongSeveral(): void
    {
        $result = Money::splitEqual(1, 4);
        $this->assertSame([1, 0, 0, 0], $result);
        $this->assertSame(1, array_sum($result));
    }

    public function testSplitByBasisPointsSumsToTotal(): void
    {
        // 50% / 25% / 25% of $200.00
        $result = Money::splitByBasisPoints(20000, [5000, 2500, 2500]);
        $this->assertSame([10000, 5000, 5000], $result);
    }

    public function testSplitBySharesSumsToTotal(): void
    {
        // shares 2/1/1 of $120.00
        $result = Money::splitByShares(12000, [2, 1, 1]);
        $this->assertSame([6000, 3000, 3000], $result);
        $this->assertSame(12000, array_sum($result));
    }

    public function testSplitBySharesWithRemainder(): void
    {
        $result = Money::splitByShares(100, [1, 1, 1]);
        $this->assertSame(100, array_sum($result));
    }
}
