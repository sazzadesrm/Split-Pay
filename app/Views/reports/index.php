<ul class="nav nav-pills mb-3 flex-wrap gap-1">
  <li class="nav-item"><a class="nav-link active" data-bs-toggle="pill" href="#overview">Expense Overview</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#category">Category Analysis</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#project">Project Analysis</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#member">Member Contributions</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#outstanding">Outstanding Balances</a></li>
  <?php if (!empty($approvalQueue) || in_array($role,['owner','admin'],true)): ?>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#approval">Approval Queue</a></li>
  <?php endif; ?>
  <?php if (in_array($role,['owner','admin'],true)): ?>
  <li class="nav-item"><a class="nav-link" href="<?= url('/activity') ?>">Activity Log</a></li>
  <?php endif; ?>
</ul>

<div class="tab-content">
  <div class="tab-pane fade show active export-section" id="overview">
    <?php \App\Core\View::partial('partials/export-toolbar', ['csvUrl' => url('/reports/expenses/export'), 'exportTarget' => '#overview', 'exportName' => 'expense-overview']); ?>
    <div class="row g-3 mb-3">
      <div class="col-6 col-lg-3"><div class="card kpi-card"><div class="kpi-label">Approved spending</div><div class="kpi-value"><?= money_fmt((string)($overview['approved_total'] ?? 0)) ?></div></div></div>
      <div class="col-6 col-lg-3"><div class="card kpi-card"><div class="kpi-label">Pending</div><div class="kpi-value"><?= money_fmt((string)($overview['pending_total'] ?? 0)) ?></div></div></div>
      <div class="col-6 col-lg-3"><div class="card kpi-card"><div class="kpi-label">Reimbursed</div><div class="kpi-value"><?= money_fmt((string)($overview['reimbursed_total'] ?? 0)) ?></div></div></div>
      <div class="col-6 col-lg-3"><div class="card kpi-card"><div class="kpi-label">Average / Largest</div><div class="kpi-value fs-6"><?= money_fmt((string)($overview['avg_expense'] ?? 0)) ?> / <?= money_fmt((string)($overview['max_expense'] ?? 0)) ?></div></div></div>
    </div>
  </div>

  <div class="tab-pane fade export-section" id="category">
    <?php \App\Core\View::partial('partials/export-toolbar', ['exportTarget' => '#category', 'exportName' => 'category-analysis']); ?>
    <div class="row g-3">
      <div class="col-lg-6"><div class="card"><div class="card-body"><canvas id="catChart" height="220"></canvas></div></div></div>
      <div class="col-lg-6"><div class="card"><div class="card-body">
        <table class="table table-sm"><thead><tr><th>Category</th><th class="text-end">Total</th><th class="text-end">Count</th></tr></thead>
        <tbody><?php foreach ($byCategory as $c): ?><tr><td><?= e($c['name']) ?></td><td class="text-end"><?= money_fmt((string)$c['total']) ?></td><td class="text-end"><?= (int)$c['cnt'] ?></td></tr><?php endforeach; ?></tbody></table>
      </div></div></div>
    </div>
  </div>

  <div class="tab-pane fade export-section" id="project">
    <?php \App\Core\View::partial('partials/export-toolbar', ['exportTarget' => '#project', 'exportName' => 'project-analysis']); ?>
    <div class="card"><div class="card-body">
      <table class="table table-sm">
        <thead><tr><th>Project</th><th class="text-end">Budget</th><th class="text-end">Spent</th><th class="text-end">Remaining</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($byProject as $p): $remaining = ($p['budget_amount'] ?? 0) - $p['spent']; ?>
          <tr><td><a href="<?= url('/projects/'.$p['id']) ?>"><?= e($p['name']) ?></a></td>
            <td class="text-end"><?= $p['budget_amount'] ? money_fmt($p['budget_amount']) : '—' ?></td>
            <td class="text-end"><?= money_fmt((string)$p['spent']) ?></td>
            <td class="text-end"><?= $p['budget_amount'] ? money_fmt((string)$remaining) : '—' ?></td>
            <td><span class="badge <?= status_badge_class($p['status']) ?>"><?= ucfirst($p['status']) ?></span></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div></div>
  </div>

  <div class="tab-pane fade export-section" id="member">
    <?php \App\Core\View::partial('partials/export-toolbar', ['csvUrl' => url('/reports/balances/export'), 'exportTarget' => '#member', 'exportName' => 'member-contributions']); ?>
    <div class="card"><div class="card-body">
      <table class="table table-sm">
        <thead><tr><th>Member</th><th class="text-end">Paid</th><th class="text-end">Owed</th><th class="text-end">Adjustment</th><th class="text-end">Balance</th></tr></thead>
        <tbody>
        <?php foreach ($memberBalances as $b): ?>
          <tr><td><?= e($b['name']) ?></td><td class="text-end"><?= money_fmt($b['total_paid_cents']) ?></td><td class="text-end"><?= money_fmt($b['total_owed_cents']) ?></td>
            <td class="text-end"><?= money_fmt($b['settlement_adjustment_cents']) ?></td><td class="text-end fw-semibold"><?= money_fmt($b['balance_cents']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div></div>
  </div>

  <div class="tab-pane fade export-section" id="outstanding">
    <?php \App\Core\View::partial('partials/export-toolbar', ['exportTarget' => '#outstanding', 'exportName' => 'outstanding-balances']); ?>
    <div class="card"><div class="card-body">
      <h6 class="card-title">Suggested settlements</h6>
      <?php if (empty($suggestions)): ?><p class="text-muted small">Everyone is settled up.</p>
      <?php else: foreach ($suggestions as $s): ?>
        <div class="d-flex justify-content-between border-bottom py-2"><span><?= e($s['from_name']) ?> → <?= e($s['to_name']) ?></span><strong><?= money_fmt($s['amount_cents']) ?></strong></div>
      <?php endforeach; endif; ?>
    </div></div>
  </div>

  <?php if (!empty($approvalQueue) || in_array($role,['owner','admin'],true)): ?>
  <div class="tab-pane fade export-section" id="approval">
    <?php \App\Core\View::partial('partials/export-toolbar', ['exportTarget' => '#approval', 'exportName' => 'approval-queue']); ?>
    <div class="card"><div class="card-body">
      <?php if (empty($approvalQueue)): ?><p class="text-muted small mb-0">Nothing awaiting approval.</p><?php else: ?>
      <table class="table table-sm">
        <thead><tr><th>Expense</th><th>Submitter</th><th class="text-end">Amount</th><th>Date</th><th>Receipt</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($approvalQueue as $a): ?>
          <tr>
            <td><a href="<?= url('/expenses/'.$a['id']) ?>"><?= e($a['title']) ?></a></td>
            <td><?= e($a['submitter']) ?></td>
            <td class="text-end"><?= money_fmt((string)$a['amount']) ?></td>
            <td><?= e(format_date($a['expense_date'])) ?></td>
            <td><?= $a['receipts'] > 0 ? '<i class="bi bi-paperclip"></i> '.$a['receipts'] : '—' ?></td>
            <td><a href="<?= url('/expenses/'.$a['id']) ?>" class="btn btn-sm btn-outline-primary">Review</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div></div>
  </div>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  new Chart(document.getElementById('catChart'), {
    type: 'doughnut',
    data: {
      labels: <?= json_encode(array_column($byCategory, 'name')) ?>,
      datasets: [{ data: <?= json_encode(array_map('floatval', array_column($byCategory, 'total'))) ?>, backgroundColor: ['#4F46E5','#0EA5E9','#16A34A','#F59E0B','#DC2626','#3730A3','#64748B','#94A3B8','#0F172A','#A78BFA'] }]
    }
  });
});
</script>
