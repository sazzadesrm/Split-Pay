<div class="d-flex justify-content-between align-items-center mb-3">
  <ul class="nav nav-pills">
    <?php foreach (['all'=>'All','unread'=>'Unread','read'=>'Read'] as $v=>$label): ?>
      <li class="nav-item"><a class="nav-link <?= $view===$v?'active':'' ?>" href="?view=<?= $v ?>"><?= $label ?></a></li>
    <?php endforeach; ?>
  </ul>
  <button class="btn btn-sm btn-outline-secondary" data-mark-all-read>Mark all as read</button>
</div>

<?php if (empty($notifications)): ?>
  <?php \App\Core\View::partial('partials/empty-state', ['icon' => 'bi-bell', 'title' => 'You are all caught up', 'message' => 'There are no new notifications.']); ?>
<?php else: ?>
<div class="card">
  <?php foreach ($notifications as $n): ?>
    <a href="<?= e($n['link_url'] ?? '#') ?>" class="notif-item d-block <?= !$n['is_read'] ? 'unread' : '' ?>" style="border-bottom:1px solid var(--sp-border)">
      <div class="d-flex justify-content-between">
        <span class="notif-title"><?= e($n['title']) ?></span>
        <span class="small text-muted"><?= e(format_date($n['created_at'], 'M j, g:i A')) ?></span>
      </div>
      <div class="small text-muted"><?= e($n['message']) ?></div>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
