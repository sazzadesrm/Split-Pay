# Split Pay

Shared expense tracking and reimbursement platform for small teams —
multi-team workspaces, an approval-controlled expense ledger with four
split methods, protected receipt storage, balances and settlements,
projects/budgets, dashboards, filtered reports and CSV/PDF/PNG/JPG exports.

Built with PHP 8.2+, MySQL 8+ (PDO, prepared statements only), Bootstrap 5
and vanilla JavaScript — a modular, MVC-inspired app (no framework, no
single-file scripts). See `docs/` for architecture, permissions, financial
calculation rules, receipt security and deployment details.

## Requirements

- PHP 8.2+ with extensions: `pdo_mysql`, `fileinfo`, `mbstring`, `openssl`, `json`, `session`, `ctype`, `filter` (`zip`/`gd` optional)
- MySQL 8+ (InnoDB, utf8mb4)
- Composer 2+ (only needed for PHPUnit in development — the app itself runs without `vendor/`, via a built-in PSR-4 autoloader)
- Apache or Nginx with the document root ideally at `public/` (a root-level `.htaccess` fallback is included for shared hosts that can't change the doc root — see `docs/deployment.md`)

## Local setup

```bash
cp .env.example .env
# edit .env with your local MySQL credentials

php database/migrate.php          # create schema
php database/migrate.php --seed   # + demo data (local/demo only)

php -S localhost:8000 -t public   # quick local server
```

Visit `http://localhost:8000`.

### Demo credentials (seeded data only — never used in production)

| Name | Email | Role | Password |
|---|---|---|---|
| Maria Smith | maria@splitpay.test | Owner | password123 |
| Alex Johnson | alex@splitpay.test | Admin | password123 |
| Sam Lee | sam@splitpay.test | Member | password123 |
| Jordan Taylor | jordan@splitpay.test | Viewer | password123 |

Team: **BrightPath Studio** (USD), with 10 seeded expenses across every
status and split type, two projects with budgets, tags, categories and two
settlements (one completed, one pending).

## Production deployment (this app is configured for)

- **App URL:** `https://sazzad.co.financial`
- **Root directory:** `public_html/sazzad.co.financial`
- **Database:** `splitpay-353131377148` on `sdb-89.hosting.stackcp.net`

See `docs/deployment.md` for the exact StackCP shared-hosting steps
(`.env` values, the root `.htaccess` rewrite shim, cron setup for
`app/Console/Scheduler.php`, and storage permissions).

## Tests

```bash
composer install
composer test
```

`tests/MoneyTest.php`, `tests/ExpenseServiceTest.php` and
`tests/ReceiptUploadServiceTest.php` are pure unit tests (no database
needed). `tests/BalanceServiceTest.php` and `tests/SettlementServiceTest.php`
are integration tests that skip automatically without a reachable database.
See `docs/testing.md`.

## Features (MVP scope — see PRD §14 for what's intentionally out of scope)

- Registration/login/logout, password reset, login throttling, profile + optional avatar.
- Multi-team workspaces with Owner/Admin/Member/Viewer roles and centralized, policy-driven visibility rules.
- Expense ledger: equal/exact/percentage/shares splits (integer-cent arithmetic, always verified to sum exactly to the total), categories, tags, projects/clients, up to 10 receipts per expense.
- Approval workflow: draft → submitted → approved/rejected → reimbursed, with mandatory rejection reasons, self-approval prevention, and automatic re-approval on material edits.
- Receipts stored outside the web root, served only through an authorized, path-traversal-safe endpoint.
- Balances computed live (paid − owed + settlement adjustment) with suggested settlements; settlement recording, completion, cancellation and overpayment warnings.
- Dashboard KPIs and charts (Chart.js), filtered reports, and **CSV + PDF + PNG + JPG export on every section** (CSV streams from the server honoring filters/authorization; PDF/PNG/JPG are captured client-side via the export toolbar so every page — dashboard, expenses, balances, settlements, projects, reports, activity log — can be exported the same way).
- Every expense and settlement renders as a **card** (not just a table row) showing payer, amount, status, category/tags and quick actions, per this build's UI requirement, on both desktop and mobile.
- In-app notifications and a full activity/audit log (owners/admins).
- Security: PDO prepared statements everywhere, per-session CSRF tokens on every state-changing request, output escaping via `e()`, team-scoped authorization on every query, hashed passwords/reset tokens, security headers, formula-injection-safe CSV exports.

## Known limitations (intentional MVP scope — see PRD §14)

No native mobile app, no multi-currency conversion (one currency per
team), no accounting integrations, no two-factor auth, no public REST API
yet (the service layer is structured so one can be added without a
rewrite), no OCR receipt extraction, no multi-step approval chains.
