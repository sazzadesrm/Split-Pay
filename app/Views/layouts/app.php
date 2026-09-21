<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Split Pay') ?> · Split Pay</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<?php
$activeTeamId = \App\Core\Auth::activeTeamId();
$teamsModel = new \App\Models\Team();
$userTeams = $activeTeamId ? $teamsModel->teamsForUser($auth['id']) : [];
$activeTeam = null;
foreach ($userTeams as $t) { if ((int)$t['id'] === (int)$activeTeamId) { $activeTeam = $t; break; } }
$notifModel = new \App\Models\Notification();
$unreadCount = $auth ? $notifModel->unreadCount((int) $auth['id']) : 0;
?>
<div class="app-shell">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <a href="<?= url('/dashboard') ?>" class="brand-link">
        <span class="brand-mark">SP</span> <span class="brand-name">Split Pay</span>
      </a>
      <button class="btn-close d-lg-none" id="sidebarClose" aria-label="Close menu"></button>
    </div>

    <?php if ($activeTeam): ?>
    <div class="team-switcher dropdown">
      <button class="btn team-switcher-btn dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
        <span class="team-avatar"><?= e(initials($activeTeam['name'])) ?></span>
        <span class="team-name"><?= e($activeTeam['name']) ?></span>
      </button>
      <ul class="dropdown-menu w-100">
        <?php foreach ($userTeams as $t): ?>
          <li>
            <form method="post" action="<?= url('/teams/' . $t['id'] . '/switch') ?>">
              <?= csrf_field() ?>
              <button type="submit" class="dropdown-item <?= (int)$t['id'] === (int)$activeTeamId ? 'active' : '' ?>"><?= e($t['name']) ?></button>
            </form>
          </li>
        <?php endforeach; ?>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="<?= url('/teams/create') ?>"><i class="bi bi-plus-circle me-1"></i> Create Team</a></li>
      </ul>
    </div>
    <a href="<?= url('/expenses/create') ?>" class="btn btn-primary w-100 mb-3"><i class="bi bi-plus-lg"></i> Add Expense</a>
    <?php endif; ?>

    <nav class="sidebar-nav">
      <?php
      $navItems = [
        ['/dashboard', 'bi-speedometer2', 'Dashboard'],
        ['/expenses', 'bi-receipt', 'Expenses'],
        ['/balances', 'bi-arrow-left-right', 'Balances & Settle Up'],
        ['/projects', 'bi-kanban', 'Projects'],
        ['/reports', 'bi-bar-chart', 'Reports'],
      ];
      if ($activeTeam) {
        $navItems[] = ['/teams/' . $activeTeam['id'] . '/settings', 'bi-gear', 'Team Settings'];
      }
      $navItems[] = ['/notifications', 'bi-bell', 'Notifications'];
      $navItems[] = ['/profile', 'bi-person', 'Profile'];
      foreach ($navItems as [$path, $icon, $label]):
        $active = str_starts_with($_SERVER['REQUEST_URI'] ?? '', $path);
      ?>
        <a href="<?= url($path) ?>" class="nav-link <?= $active ? 'active' : '' ?>">
          <i class="bi <?= $icon ?>"></i> <span><?= e($label) ?></span>
          <?php if ($path === '/notifications' && $unreadCount > 0): ?>
            <span class="badge rounded-pill bg-danger ms-auto"><?= (int) $unreadCount ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <?php if ($auth): ?>
    <div class="sidebar-user">
      <span class="avatar-circle"><?= e(initials($auth['name'])) ?></span>
      <div class="sidebar-user-info">
        <div class="fw-semibold text-truncate"><?= e($auth['name']) ?></div>
        <div class="text-muted small text-truncate"><?= e($auth['email']) ?></div>
      </div>
      <form method="post" action="<?= url('/logout') ?>">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-link text-muted" title="Log out"><i class="bi bi-box-arrow-right"></i></button>
      </form>
    </div>
    <?php endif; ?>
  </aside>

  <div class="app-main">
    <header class="app-header">
      <button class="btn btn-icon d-lg-none" id="sidebarToggle"><i class="bi bi-list"></i></button>
      <h1 class="app-title mb-0"><?= e($title ?? '') ?></h1>
      <div class="ms-auto d-flex align-items-center gap-2">
        <div class="dropdown">
          <button class="btn btn-icon position-relative" data-bs-toggle="dropdown" id="notifBell">
            <i class="bi bi-bell"></i>
            <?php if ($unreadCount > 0): ?><span class="notif-dot"></span><?php endif; ?>
          </button>
          <div class="dropdown-menu dropdown-menu-end notif-dropdown" id="notifDropdown">
            <div class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom">
              <strong>Notifications</strong>
              <a href="<?= url('/notifications') ?>" class="small">View all</a>
            </div>
            <div id="notifList" class="notif-list"><div class="p-3 text-muted small">Loading...</div></div>
          </div>
        </div>
      </div>
    </header>

    <main class="app-content">
      <?php if ($success = flash_get('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert"><?= e($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
      <?php endif; ?>
      <?php if ($errorMsg = flash_get('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert"><?= e($errorMsg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
      <?php endif; ?>

      <?= $content ?>
    </main>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script>window.APP_URL = <?= json_encode(config('app')['url']) ?>; window.CSRF_TOKEN = <?= json_encode($csrfToken) ?>;</script>
<script src="<?= asset('js/app.js') ?>"></script>
<script src="<?= asset('js/export-toolbar.js') ?>"></script>
</body>
</html>
