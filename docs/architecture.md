# Architecture

Split Pay is a server-rendered PHP 8.2+ application with a clean,
MVC-inspired, modular architecture (no framework, no single-file scripts).

## Layers

| Layer | Location | Responsibility |
|---|---|---|
| Core | `app/Core/` | `Database` (PDO), `Router`, `Request`, `Response`, `Session`, `Auth`, `Csrf`, `Validator`, `Money`, `View`, `Logger`, `Env`, helper functions. |
| Middleware | `app/Middleware/` | `AuthMiddleware`, `TeamAccessMiddleware`, `RoleMiddleware`, `CsrfMiddleware`. Composed per-route in `routes/web.php`. |
| Policies | `app/Policies/` | `ExpensePolicy` centralizes expense visibility/edit/delete/approve rules, reused by controllers, lists, receipts and exports. |
| Models | `app/Models/` | Data access via PDO prepared statements. Every query is team-scoped. Models never bypass authorization (that's the controller/policy's job, models just expose team-scoped queries). |
| Services | `app/Services/` | Financial and business logic: `AuthService`, `BalanceService`, `ExpenseService`, `SettlementService`, `ReceiptUploadService`, `CsvExportService`, `NotificationService`, `ActivityLogService`, `InvitationService`. Multi-step financial writes run inside a DB transaction. |
| Controllers | `app/Controllers/` | Thin request handlers: authenticate (middleware), verify CSRF (middleware), authorize (`Controller::can()` / policies), call a service, return a view/redirect/JSON response. |
| Views | `app/Views/` | `layouts/` (app, guest), `partials/` (export toolbar, pagination, cards, empty states), and page templates per module. |

## Request lifecycle

1. `public/index.php` boots `app/bootstrap.php` (env, session, error handler) and loads `routes/web.php`.
2. `Router::dispatch()` matches the request, runs the route's middleware chain in order, then calls the controller action.
3. Controllers call into services for anything financial or multi-step; services wrap writes in `Database::beginTransaction()/commit()/rollBack()`.
4. Controllers call `Response::view()` (HTML) or `Response::success()/error()` (JSON, for AJAX) — never both in the same action beyond the `Controller::respond()` helper, which picks the right one based on `Request::isAjax()`.

## Extensibility

Because business logic lives in the service layer and controllers stay thin,
a future `/api/v1` JSON API can reuse `App\Services\*` directly without
rewriting financial logic.
