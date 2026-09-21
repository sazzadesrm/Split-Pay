<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <form class="d-flex gap-2" method="get">
    <input type="search" name="search" value="<?= e($filters['search']) ?>" class="form-control form-control-sm" placeholder="Search expenses (min 2 chars)..." style="min-width:220px">
    <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filterPanel"><i class="bi bi-funnel"></i> Filters</button>
  </form>
  <a href="<?= url('/expenses/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add Expense</a>
</div>

<div class="collapse mb-3" id="filterPanel">
  <div class="card card-body">
    <form method="get" class="row g-2">
      <input type="hidden" name="search" value="<?= e($filters['search']) ?>">
      <div class="col-6 col-md-2"><label class="form-label small">From</label><input type="date" name="date_from" value="<?= e($filters['date_from']) ?>" class="form-control form-control-sm"></div>
      <div class="col-6 col-md-2"><label class="form-label small">To</label><input type="date" name="date_to" value="<?= e($filters['date_to']) ?>" class="form-control form-control-sm"></div>
      <div class="col-6 col-md-2">
        <label class="form-label small">Category</label>
        <select name="category_id" class="form-select form-select-sm">
          <option value="">All</option>
          <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= (string)$filters['category_id']===(string)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label small">Project</label>
        <select name="project_id" class="form-select form-select-sm">
          <option value="">All</option>
          <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= (string)$filters['project_id']===(string)$p['id']?'selected':'' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label small">Status</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">All</option>
          <?php foreach (['draft','submitted','approved','rejected','reimbursed'] as $s): ?><option value="<?= $s ?>" <?= $filters['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label small">Paid by</label>
        <select name="paid_by_user_id" class="form-select form-select-sm">
          <option value="">Anyone</option>
          <?php foreach ($members as $m): ?><option value="<?= $m['user_id'] ?>" <?= (string)$filters['paid_by_user_id']===(string)$m['user_id']?'selected':'' ?>><?= e($m['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-12"><button class="btn btn-sm btn-primary" type="submit">Apply filters</button> <a href="<?= url('/expenses') ?>" class="btn btn-sm btn-link">Clear</a></div>
    </form>
  </div>
</div>

<div class="export-section">
<?php \App\Core\View::partial('partials/export-toolbar', ['csvUrl' => url('/expenses/export?' . $queryString), 'exportTarget' => '.export-section', 'exportName' => 'expenses']); ?>

<div class="d-flex justify-content-between align-items-center mb-2">
  <span class="text-muted small"><?= (int) $meta['total'] ?> expense<?= $meta['total'] === 1 ? '' : 's' ?></span>
  <form method="get" class="d-flex align-items-center gap-2">
    <?php foreach ($_GET as $k => $v) { if ($k !== 'per_page') echo '<input type="hidden" name="'.e($k).'" value="'.e($v).'">'; } ?>
    <label class="small text-muted mb-0">Per page</label>
    <select name="per_page" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
      <?php foreach ([10,20,50,100] as $pp): ?><option value="<?= $pp ?>" <?= (int)($meta['per_page'])===$pp?'selected':'' ?>><?= $pp ?></option><?php endforeach; ?>
    </select>
  </form>
</div>

<?php if (empty($expenses)): ?>
  <?php \App\Core\View::partial('partials/empty-state', ['icon' => 'bi-receipt', 'title' => 'No expenses found', 'message' => 'Start tracking shared team spending by adding your first expense.', 'actionUrl' => url('/expenses/create'), 'actionLabel' => 'Create your first expense']); ?>
<?php else: ?>
  <div class="expense-cards">
    <?php foreach ($expenses as $e): \App\Core\View::partial('partials/expense-card', ['e' => $e, 'role' => $role]); endforeach; ?>
  </div>
  <?php \App\Core\View::partial('partials/pagination', ['meta' => $meta]); ?>
<?php endif; ?>
</div>
