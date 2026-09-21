# Testing

## Automated tests (PHPUnit)

Run with:

```bash
composer install
composer test
```

- `tests/MoneyTest.php` — pure unit tests for integer-cent arithmetic and all four split algorithms (equal/exact/percentage/shares), including edge cases ($0.01 among several participants). No database required.
- `tests/ExpenseServiceTest.php` — `ExpenseService::calculateSplit()` validation rules (exact totals must match, percentages must total 100.00%, shares must be positive, at least one participant). No database required.
- `tests/ReceiptUploadServiceTest.php` — upload error handling, oversized files, unsupported MIME types, and path-traversal protection in `resolveAbsolutePath()`. No database required.
- `tests/BalanceServiceTest.php` / `tests/SettlementServiceTest.php` — integration tests against a real database (approved-only balance rules, completed-only settlement rules, double-completion prevention). These `markTestSkipped()` automatically if no database is reachable via `.env`, so `composer test` always succeeds in an environment without MySQL.

To run the database-backed tests, point `.env` at a disposable MySQL
database, load `database/schema.sql` and `database/seeds/seed_demo_data.sql`,
then run `composer test`.

## Manual QA checklist (see PRD §11 for the full list)

- Authorization: a non-member cannot open another team's expense/receipt by guessing an ID or URL (403/404, no data leakage).
- A member cannot approve their own expense or edit another member's draft; a viewer cannot create anything.
- Split totals always equal the expense amount, including $0.01 among several participants.
- Only `approved`/`reimbursed` expenses and `completed` settlements affect balances.
- CSV exports honor the active filters, the caller's role, and the expense visibility policy; formula-like values (`=`, `+`, `-`, `@`) are neutralized with a leading apostrophe.
- Receipts: correct MIME/extension accepted, mismatched/oversized files rejected, filenames are random, cross-team access is denied.
