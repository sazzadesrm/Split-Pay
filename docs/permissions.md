# Permissions and Roles

Four roles per team: **Owner**, **Admin**, **Member**, **Viewer**. The
authoritative permission matrix is `config/permissions.php`, enforced by
`App\Middleware\RoleMiddleware` (route-level) and `Controller::can()`
(action-level, for cases where the same route serves multiple roles
differently).

## Role summary

- **Owner** — full control: team settings, currency, member roles, deletion, ownership transfer, everything Admin can do.
- **Admin** — invites members, manages categories/tags/projects, creates/edits/approves/rejects expenses, records settlements, exports reports. Cannot change roles, remove the owner, transfer ownership or delete the team.
- **Member** — creates and submits expenses, edits/deletes own drafts, uploads receipts to own expenses, records settlements they're involved in, views balances and limited reports.
- **Viewer** — read-only: approved/reimbursed expenses, balances, projects, basic reports. No create/edit/approve/delete/settle.

## Expense visibility (`App\Policies\ExpensePolicy`)

Centralized so lists, detail pages, receipts, reports and CSV exports all
agree on what a role may see:

- Owner/Admin: every non-deleted expense (draft, submitted, approved, rejected, reimbursed).
- Member: all approved/reimbursed expenses, plus their own draft/submitted/rejected expenses, plus any expense where they are a split participant.
- Viewer: approved/reimbursed expenses only.

## Self-approval

Regular members can never approve their own expense. Owner/Admin
self-approval is also denied by default and is only allowed when a team's
`team_settings.allow_self_approval` flag is enabled.

## Data isolation

Every team-scoped route runs `AuthMiddleware` then `TeamAccessMiddleware`,
which confirms the user is an **active** member of the session's active
team before any controller code runs. Every model query additionally
filters by `team_id`. Unauthorized cross-team access returns 403/404
without revealing whether the resource exists.
