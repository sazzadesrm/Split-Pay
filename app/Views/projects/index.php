<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Projects</h5>
  <?php if (in_array($role,['owner','admin'],true)): ?>
  <a href="<?= url('/projects/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> New Project</a>
  <?php endif; ?>
</div>

<?php if (empty($projects)): ?>
  <?php \App\Core\View::partial('partials/empty-state', ['icon' => 'bi-kanban', 'title' => 'No active projects yet', 'message' => 'Create a project to track client budgets and project-related expenses.', 'actionUrl' => in_array($role,['owner','admin'],true) ? url('/projects/create') : null, 'actionLabel' => 'Create a project']); ?>
<?php else: ?>
<div class="row g-3">
  <?php foreach ($projects as $p):
    $pct = $p['percent_used'];
    $barClass = $pct >= 100 ? 'bg-danger' : ($pct >= 80 ? 'bg-warning' : 'bg-success');
  ?>
  <div class="col-md-6 col-lg-4">
    <div class="card h-100"><div class="card-body">
      <div class="d-flex justify-content-between">
        <h6 class="card-title"><a href="<?= url('/projects/'.$p['id']) ?>" class="text-decoration-none"><?= e($p['name']) ?></a></h6>
        <span class="badge <?= status_badge_class($p['status']) ?>"><?= ucfirst($p['status']) ?></span>
      </div>
      <p class="small text-muted mb-2"><?= e($p['client_name']) ?> <?= $p['project_code'] ? '· '.e($p['project_code']) : '' ?></p>
      <?php if ($p['budget_amount']): ?>
        <div class="d-flex justify-content-between small mb-1"><span><?= money_fmt((string)$p['spent']) ?> spent</span><span><?= money_fmt($p['budget_amount']) ?> budget</span></div>
        <div class="progress progress-thin"><div class="progress-bar <?= $barClass ?>" style="width:<?= min(100,$pct) ?>%"></div></div>
      <?php else: ?>
        <p class="small text-muted">No budget set</p>
      <?php endif; ?>
    </div></div>
  </div>
  <?php endforeach; ?>
</div>
<?php \App\Core\View::partial('partials/pagination', ['meta' => $meta]); ?>
<?php endif; ?>
