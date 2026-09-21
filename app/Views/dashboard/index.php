<div class="export-section">
<?php \App\Core\View::partial('partials/export-toolbar', ['exportTarget' => '.export-section', 'exportName' => 'dashboard']); ?>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card kpi-card h-100">
      <div class="kpi-label">Total spending this month</div>
      <div class="kpi-value"><?= money_fmt((string) $monthTotal) ?></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card kpi-card h-100">
      <div class="kpi-label">Pending reimbursement</div>
      <div class="kpi-value"><?= money_fmt((string) $pendingTotal) ?></div>
      <div class="small text-muted"><?= (int) $pendingCount ?> awaiting approval</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card kpi-card h-100">
      <div class="kpi-label">Active projects</div>
      <div class="kpi-value"><?= (int) $activeProjects ?></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card kpi-card h-100">
      <div class="kpi-label">Your balance</div>
      <div class="kpi-value <?= $yourBalance['balance_cents'] >= 0 ? 'text-success' : 'text-danger' ?>">
        <?= money_fmt($yourBalance['balance_cents']) ?>
      </div>
      <div class="small text-muted"><?= $yourBalance['status'] === 'owed_money' ? 'You are owed' : ($yourBalance['status'] === 'owes_money' ? 'You owe' : 'Settled up') ?></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-body">
        <h6 class="card-title">Monthly spending trend</h6>
        <canvas id="trendChart" height="220"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-body">
        <h6 class="card-title">Spending by category</h6>
        <canvas id="categoryChart" height="220"></canvas>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title d-flex justify-content-between">Quick actions</h6>
        <div class="d-flex flex-wrap gap-2">
          <a href="<?= url('/expenses/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add Expense</a>
          <a href="<?= url('/settlements') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left-right"></i> Record Settlement</a>
          <?php if (in_array($role, ['owner', 'admin'], true)): ?>
          <a href="<?= url('/teams/' . $teamId . '/members') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-person-plus"></i> Invite Member</a>
          <?php endif; ?>
          <a href="<?= url('/expenses/export') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download"></i> Export Expenses</a>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title">Your balance breakdown</h6>
        <table class="table table-sm mb-0">
          <tr><td>Total paid</td><td class="text-end"><?= money_fmt($yourBalance['total_paid_cents']) ?></td></tr>
          <tr><td>Total owed</td><td class="text-end"><?= money_fmt($yourBalance['total_owed_cents']) ?></td></tr>
          <tr><td>Settlement adjustment</td><td class="text-end"><?= money_fmt($yourBalance['settlement_adjustment_cents']) ?></td></tr>
          <tr class="fw-bold border-top"><td>Balance</td><td class="text-end"><?= money_fmt($yourBalance['balance_cents']) ?></td></tr>
        </table>
      </div>
    </div>
  </div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function () {
  const trend = await window.spFetch(window.APP_URL + '/dashboard/chart/monthly-trend');
  if (trend.success) {
    new Chart(document.getElementById('trendChart'), {
      type: 'line',
      data: {
        labels: trend.data.map(r => r.ym),
        datasets: [{ label: 'Spending', data: trend.data.map(r => r.total), borderColor: '#4F46E5', backgroundColor: 'rgba(79,70,229,.1)', fill: true, tension: .3 }]
      },
      options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
  }
  const byCategory = await window.spFetch(window.APP_URL + '/dashboard/chart/by-category');
  if (byCategory.success && byCategory.data.length) {
    new Chart(document.getElementById('categoryChart'), {
      type: 'doughnut',
      data: {
        labels: byCategory.data.map(r => r.name),
        datasets: [{ data: byCategory.data.map(r => r.total), backgroundColor: ['#4F46E5','#0EA5E9','#16A34A','#F59E0B','#DC2626','#3730A3','#64748B','#94A3B8','#0F172A','#A78BFA'] }]
      }
    });
  } else {
    document.getElementById('categoryChart').parentElement.innerHTML += '<p class="text-muted small text-center mt-3">No spending data yet.</p>';
  }
});
</script>
