<?php
declare(strict_types=1);

namespace App\Core;

/**
 * All monetary arithmetic uses integer cents. Never use floats for money.
 */
final class Money
{
    public static function toCents(string $decimal): int
    {
        if (!preg_match('/^-?\d+(\.\d{1,2})?$/', trim($decimal))) {
            throw new \InvalidArgumentException("Invalid monetary value: $decimal");
        }
        [$whole, $fraction] = array_pad(explode('.', trim($decimal), 2), 2, '0');
        $fraction = str_pad(substr($fraction, 0, 2), 2, '0');
        $sign = str_starts_with($whole, '-') ? -1 : 1;
        $whole = ltrim($whole, '-');
        return $sign * ((int) $whole * 100 + (int) $fraction);
    }

    public static function toDecimal(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);
        return sprintf('%s%d.%02d', $sign, intdiv($cents, 100), $cents % 100);
    }

    public static function format(int $cents, string $currency = 'USD'): string
    {
        $symbols = ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'BDT' => '৳'];
        $symbol = $symbols[$currency] ?? ($currency . ' ');
        return $symbol . number_format($cents / 100, 2);
    }

    /**
     * Equal split: base = floor(total / n); remainder cents go one each to
     * the first participants in stable order.
     * @return int[]
     */
    public static function splitEqual(int $totalCents, int $participantCount): array
    {
        if ($participantCount <= 0) {
            return [];
        }
        $base = intdiv($totalCents, $participantCount);
        $remainder = $totalCents - ($base * $participantCount);
        $result = array_fill(0, $participantCount, $base);
        for ($i = 0; $i < $remainder; $i++) {
            $result[$i]++;
        }
        return $result;
    }

    /**
     * Percentage split: basis points (must total 10000). Remainder cents
     * distributed one at a time to the first participants in order.
     * @param int[] $basisPoints
     * @return int[]
     */
    public static function splitByBasisPoints(int $totalCents, array $basisPoints): array
    {
        $count = count($basisPoints);
        $amounts = [];
        $allocated = 0;
        foreach ($basisPoints as $bp) {
            $amount = intdiv($totalCents * $bp, 10000);
            $amounts[] = $amount;
            $allocated += $amount;
        }
        $remainder = $totalCents - $allocated;
        for ($i = 0; $i < $remainder; $i++) {
            $amounts[$i % $count]++;
        }
        return $amounts;
    }

    /**
     * Shares split: owed = total * shares / totalShares (integer arithmetic),
     * remainder distributed one cent at a time to the first participants.
     * @param int[] $shares
     * @return int[]
     */
    public static function splitByShares(int $totalCents, array $shares): array
    {
        $totalShares = array_sum($shares);
        if ($totalShares <= 0) {
            return array_fill(0, count($shares), 0);
        }
        $amounts = [];
        $allocated = 0;
        foreach ($shares as $share) {
            $amount = intdiv($totalCents * $share, $totalShares);
            $amounts[] = $amount;
            $allocated += $amount;
        }
        $remainder = $totalCents - $allocated;
        $count = count($amounts);
        for ($i = 0; $i < $remainder && $count > 0; $i++) {
            $amounts[$i % $count]++;
        }
        return $amounts;
    }
}
