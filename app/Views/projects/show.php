<?php
$pct = $percentUsed;
$barClass = $pct >= 100 ? 'bg-danger' : ($pct >= 80 ? 'bg-warning' : 'bg-success');
?>
<div class="export-section">
<?php \App\Core\View::partial('partials/export-toolbar', ['csvUrl' => url('/projects/'.$project['id'].'/export'), 'exportTarget' => '.export-section', 'exportName' => 'project-'.$project['id']]); ?>

<div class="card mb-3"><div class="card-body">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
      <h4 class="mb-1"><?= e($project['name']) ?></h4>
      <p class="text-muted mb-1"><?= e($project['client_name']) ?> <?= $project['project_code'] ? '· '.e($project['project_code']) : '' ?></p>
      <span class="badge <?= status_badge_class($project['status']) ?>"><?= ucfirst($project['status']) ?></span>
    </div>
    <?php if (in_array($role,['owner','admin'],true)): ?>
    <a href="<?= url('/projects/'.$project['id'].'/edit') ?>" class="btn btn-outline-secondary btn-sm no-print">Edit</a>
    <?php endif; ?>
  </div>
  <?php if ($project['budget_amount']): ?>
  <div class="mt-3">
    <div class="d-flex justify-content-between small mb-1"><span><?= money_fmt((string)$spent) ?> spent of <?= money_fmt($project['budget_amount']) ?></span><span><?= $pct ?>%</span></div>
    <div class="progress progress-thin"><div class="progress-bar <?= $barClass ?>" style="width:<?= min(100,$pct) ?>%"></div></div>
  </div>
  <?php endif; ?>
</div></div>

<div class="row g-3 mb-3">
  <div class="col-lg-6">
    <div class="card"><div class="card-body"><h6 class="card-title">Spending by category</h6><canvas id="projCategoryChart" height="200"></canvas></div></div>
  </div>
  <div class="col-lg-6">
    <div class="card"><div class="card-body">
      <h6 class="card-title">Member contributions</h6>
      <table class="table table-sm mb-0">
        <?php foreach ($memberTotals as $m): ?><tr><td><?= e($m['name']) ?></td><td class="text-end"><?= money_fmt((string)$m['total']) ?></td></tr><?php endforeach; ?>
      </table>
    </div></div>
  </div>
</div>

<div class="card"><div class="card-body">
  <h6 class="card-title">Linked expenses</h6>
  <?php if (empty($expenses)): ?>
    <p class="text-muted small mb-0">No expenses linked to this project yet.</p>
  <?php else: foreach ($expenses as $e): \App\Core\View::partial('partials/expense-card', ['e' => $e, 'role' => $role]); endforeach; endif; ?>
</div></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const labels = <?= json_encode(array_column($categoryTotals, 'name')) ?>;
  const data = <?= json_encode(array_map('floatval', array_column($categoryTotals, 'total'))) ?>;
  if (data.length) {
    new Chart(document.getElementById('projCategoryChart'), {
      type: 'doughnut',
      data: { labels, datasets: [{ data, backgroundColor: ['#4F46E5','#0EA5E9','#16A34A','#F59E0B','#DC2626','#3730A3','#64748B'] }] }
    });
  }
});
</script>
