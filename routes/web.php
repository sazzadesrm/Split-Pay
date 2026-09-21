<?php
declare(strict_types=1);

use App\Controllers\ActivityController;
use App\Controllers\AuthController;
use App\Controllers\AvatarController;
use App\Controllers\BalanceController;
use App\Controllers\CategoryController;
use App\Controllers\DashboardController;
use App\Controllers\ExpenseController;
use App\Controllers\NotificationController;
use App\Controllers\ProfileController;
use App\Controllers\ProjectController;
use App\Controllers\ReceiptController;
use App\Controllers\ReportController;
use App\Controllers\SettlementController;
use App\Controllers\TagController;
use App\Controllers\TeamController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\TeamAccessMiddleware;

$router = new Router();

$auth = [AuthMiddleware::class];
$team = [AuthMiddleware::class, TeamAccessMiddleware::class];
$csrf = [CsrfMiddleware::class];

// Public
$router->get('/', [DashboardController::class, 'index'], []);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login'], $csrf);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register'], $csrf);
$router->post('/logout', [AuthController::class, 'logout'], $csrf);
$router->get('/forgot-password', [AuthController::class, 'showForgotPassword']);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword'], $csrf);
$router->get('/reset-password', [AuthController::class, 'showResetPassword']);
$router->post('/reset-password', [AuthController::class, 'resetPassword'], $csrf);

// Dashboard
$router->get('/dashboard', [DashboardController::class, 'index'], $team);
$router->get('/dashboard/chart/{chart}', [DashboardController::class, 'chartData'], $team);

// Teams
$router->get('/teams', [TeamController::class, 'index'], $auth);
$router->get('/teams/create', [TeamController::class, 'showCreate'], $auth);
$router->post('/teams', [TeamController::class, 'create'], array_merge($auth, $csrf));
$router->get('/teams/{id}/settings', [TeamController::class, 'settings'], $team);
$router->post('/teams/{id}/update', [TeamController::class, 'update'], array_merge($team, $csrf));
$router->post('/teams/{id}/switch', [TeamController::class, 'switchTeam'], array_merge($auth, $csrf));
$router->post('/teams/{id}/delete', [TeamController::class, 'delete'], array_merge($team, $csrf));
$router->get('/teams/{id}/members', [TeamController::class, 'members'], $team);
$router->post('/teams/{id}/members/invite', [TeamController::class, 'invite'], array_merge($team, $csrf));
$router->post('/teams/{id}/members/{memberId}/role', [TeamController::class, 'updateRole'], array_merge($team, $csrf));
$router->post('/teams/{id}/members/{memberId}/remove', [TeamController::class, 'removeMember'], array_merge($team, $csrf));

// Invitations
$router->get('/invitations/{token}', [TeamController::class, 'showInvitation'], $auth);
$router->post('/invitations/{token}/accept', [TeamController::class, 'acceptInvitation'], array_merge($auth, $csrf));
$router->post('/invitations/{token}/decline', [TeamController::class, 'declineInvitation'], array_merge($auth, $csrf));

// Expenses
$router->get('/expenses', [ExpenseController::class, 'index'], $team);
$router->get('/expenses/create', [ExpenseController::class, 'showCreate'], $team);
$router->post('/expenses', [ExpenseController::class, 'store'], array_merge($team, $csrf));
$router->post('/expenses/preview-split', [ExpenseController::class, 'previewSplit'], array_merge($team, $csrf));
$router->get('/expenses/export', [ExpenseController::class, 'exportCsv'], $team);
$router->get('/expenses/{id}', [ExpenseController::class, 'show'], $team);
$router->get('/expenses/{id}/edit', [ExpenseController::class, 'showEdit'], $team);
$router->post('/expenses/{id}/update', [ExpenseController::class, 'update'], array_merge($team, $csrf));
$router->post('/expenses/{id}/submit', [ExpenseController::class, 'submit'], array_merge($team, $csrf));
$router->post('/expenses/{id}/approve', [ExpenseController::class, 'approve'], array_merge($team, $csrf));
$router->post('/expenses/{id}/reject', [ExpenseController::class, 'reject'], array_merge($team, $csrf));
$router->post('/expenses/{id}/reimburse', [ExpenseController::class, 'reimburse'], array_merge($team, $csrf));
$router->post('/expenses/{id}/withdraw', [ExpenseController::class, 'withdraw'], array_merge($team, $csrf));
$router->post('/expenses/{id}/delete', [ExpenseController::class, 'delete'], array_merge($team, $csrf));

// Receipts
$router->post('/expenses/{id}/receipts', [ExpenseController::class, 'addReceipts'], array_merge($team, $csrf));
$router->get('/receipts/{id}/view', [ReceiptController::class, 'view'], $team);
$router->get('/receipts/{id}/download', [ReceiptController::class, 'download'], $team);
$router->post('/receipts/{id}/delete', [ReceiptController::class, 'delete'], array_merge($team, $csrf));

// Balances and settlements
$router->get('/balances', [BalanceController::class, 'index'], $team);
$router->get('/settlements', [SettlementController::class, 'index'], $team);
$router->post('/settlements', [SettlementController::class, 'store'], array_merge($team, $csrf));
$router->post('/settlements/{id}/complete', [SettlementController::class, 'complete'], array_merge($team, $csrf));
$router->post('/settlements/{id}/cancel', [SettlementController::class, 'cancel'], array_merge($team, $csrf));
$router->post('/settlements/{id}/delete', [SettlementController::class, 'delete'], array_merge($team, $csrf));

// Projects
$router->get('/projects', [ProjectController::class, 'index'], $team);
$router->get('/projects/create', [ProjectController::class, 'showCreate'], $team);
$router->post('/projects', [ProjectController::class, 'store'], array_merge($team, $csrf));
$router->get('/projects/{id}', [ProjectController::class, 'show'], $team);
$router->get('/projects/{id}/edit', [ProjectController::class, 'showEdit'], $team);
$router->get('/projects/{id}/export', [ProjectController::class, 'exportCsv'], $team);
$router->post('/projects/{id}/update', [ProjectController::class, 'update'], array_merge($team, $csrf));
$router->post('/projects/{id}/delete', [ProjectController::class, 'delete'], array_merge($team, $csrf));

// Categories and tags
$router->get('/categories', [CategoryController::class, 'index'], $team);
$router->post('/categories', [CategoryController::class, 'store'], array_merge($team, $csrf));
$router->post('/categories/{id}/update', [CategoryController::class, 'update'], array_merge($team, $csrf));
$router->post('/categories/{id}/archive', [CategoryController::class, 'archive'], array_merge($team, $csrf));
$router->get('/tags', [TagController::class, 'index'], $team);
$router->post('/tags', [TagController::class, 'store'], array_merge($team, $csrf));
$router->post('/tags/{id}/update', [TagController::class, 'update'], array_merge($team, $csrf));
$router->post('/tags/{id}/delete', [TagController::class, 'delete'], array_merge($team, $csrf));

// Reports
$router->get('/reports', [ReportController::class, 'index'], $team);
$router->get('/reports/expenses/export', [ExpenseController::class, 'exportCsv'], $team);
$router->get('/reports/balances/export', [BalanceController::class, 'exportCsv'], $team);
$router->get('/reports/settlements/export', [SettlementController::class, 'exportCsv'], $team);
$router->get('/activity', [ActivityController::class, 'index'], $team);

// Notifications
$router->get('/notifications', [NotificationController::class, 'index'], $team);
$router->get('/notifications/dropdown', [NotificationController::class, 'dropdown'], $team);
$router->post('/notifications/{id}/read', [NotificationController::class, 'markRead'], array_merge($team, $csrf));
$router->post('/notifications/read-all', [NotificationController::class, 'markAllRead'], array_merge($team, $csrf));

// Profile
$router->get('/profile', [ProfileController::class, 'show'], $auth);
$router->post('/profile/update', [ProfileController::class, 'update'], array_merge($auth, $csrf));
$router->post('/profile/password', [ProfileController::class, 'updatePassword'], array_merge($auth, $csrf));
$router->post('/profile/avatar', [ProfileController::class, 'updateAvatar'], array_merge($auth, $csrf));
$router->get('/avatars/{userId}', [AvatarController::class, 'show'], $auth);

return $router;
