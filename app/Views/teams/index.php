<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Your Teams</h5>
  <a href="<?= url('/teams/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Create Team</a>
</div>
<?php if (empty($teams)): ?>
  <?php \App\Core\View::partial('partials/empty-state', ['icon' => 'bi-people', 'title' => 'No teams yet', 'message' => 'Create a team to start tracking shared expenses.', 'actionUrl' => url('/teams/create'), 'actionLabel' => 'Create your first team']); ?>
<?php else: ?>
<div class="row g-3">
  <?php foreach ($teams as $t): ?>
    <div class="col-md-6 col-lg-4">
      <div class="card h-100"><div class="card-body">
        <h6 class="card-title"><?= e($t['name']) ?></h6>
        <p class="text-muted small"><?= e($t['description']) ?></p>
        <span class="badge <?= role_badge_class($t['role']) ?>"><?= e(ucfirst($t['role'])) ?></span>
        <form method="post" action="<?= url('/teams/'.$t['id'].'/switch') ?>" class="mt-2">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-outline-primary w-100">Switch to this team</button>
        </form>
      </div></div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
