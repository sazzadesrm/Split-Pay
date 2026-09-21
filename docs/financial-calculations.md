# Financial Calculations

All money math uses **integer cents** (`App\Core\Money`). Floats are never
used for currency — floating-point rounding errors are unacceptable for a
ledger. Decimal strings (`"123.45"`) are the only I/O format; conversion to
and from integer cents happens at the edges (`Money::toCents()` /
`Money::toDecimal()`).

## Split methods (`App\Services\ExpenseService::calculateSplit`)

| Type | Rule |
|---|---|
| Equal | `base = intdiv(total, n)`; remainder cents distributed **one each to the first participants**, in the order submitted. |
| Exact | Each participant's amount is validated non-negative; the set must sum **exactly** to the expense total or the save is rejected. |
| Percentage | Percentages (2 decimal places) are converted to basis points (`round(pct * 100)`) and must sum to exactly `10000` (100.00%). Rounding remainder cents are distributed one at a time to the first participants. |
| Shares | Each participant's whole-number shares must be `> 0`. `owed = intdiv(total * shares, totalShares)`; remainder cents distributed one at a time to the first participants. |

Every split method is verified in code to sum exactly to the expense total
before it is persisted — see the `$sumCheck !== $totalCents` guard at the
end of `calculateSplit()`.

## Balances (`App\Services\BalanceService`)

```
balance = total_paid - total_owed + settlement_adjustment
```

- `total_paid`: sum of `expenses.amount` where the user is `paid_by_user_id`, status in (`approved`,`reimbursed`), not soft-deleted.
- `total_owed`: sum of `expense_splits.owed_amount` for the user, joined to expenses with the same status/deletion filter.
- `settlement_adjustment`: `+amount` for each `completed` settlement where the user is the payer, `-amount` where the user is the receiver. Pending/cancelled/deleted settlements are excluded.

Balances are always computed live from aggregate queries — never cached or
stored as duplicated totals — so they can never drift from the underlying
ledger.

### Suggested settlements

Members with a positive balance (creditors) and negative balance (debtors)
are matched greedily: the largest unresolved debt is paired against the
largest unresolved credit until every balance nets to zero, producing the
minimum number of suggested payments.

## Worked examples (from the PRD, verified by `tests/MoneyTest.php` and `tests/ExpenseServiceTest.php`)

- Equal, 3 participants, $100.00 → 33.34 / 33.33 / 33.33 (remainder cent to the first participant).
- Exact, $50/$60/$40 → accepted, totals $150.00.
- Percentage, 50%/25%/25% of $200.00 → $100.00/$50.00/$50.00.
- Shares, 2/1/1 of $120.00 → $60.00/$30.00/$30.00.
