<div class="card card-body mb-3">
  <form method="get" class="row g-2">
    <div class="col-6 col-md-2"><input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>" class="form-control form-control-sm"></div>
    <div class="col-6 col-md-2"><input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>" class="form-control form-control-sm"></div>
    <div class="col-6 col-md-3">
      <select name="user_id" class="form-select form-select-sm"><option value="">Any user</option>
        <?php foreach ($members as $m): ?><option value="<?= $m['user_id'] ?>" <?= (string)($filters['user_id']??'')===(string)$m['user_id']?'selected':'' ?>><?= e($m['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-3"><input type="text" name="entity_type" value="<?= e($filters['entity_type'] ?? '') ?>" placeholder="Entity type (e.g. expense)" class="form-control form-control-sm"></div>
    <div class="col-md-2"><button class="btn btn-sm btn-outline-secondary w-100" type="submit">Filter</button></div>
  </form>
</div>

<div class="export-section">
<?php \App\Core\View::partial('partials/export-toolbar', ['exportTarget' => '.export-section', 'exportName' => 'activity-log']); ?>
<div class="card"><div class="card-body">
  <?php if (empty($logs)): ?>
    <p class="text-muted small mb-0">No activity recorded yet.</p>
  <?php else: ?>
  <ul class="list-unstyled mb-0">
    <?php foreach ($logs as $l): ?>
      <li class="border-bottom py-2">
        <div><strong><?= e($l['user_name'] ?? 'System') ?></strong> — <?= e($l['description']) ?></div>
        <div class="small text-muted"><?= e($l['action']) ?> · <?= e(format_date($l['created_at'], 'M j, Y g:i A')) ?></div>
      </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</div></div>
<?php \App\Core\View::partial('partials/pagination', ['meta' => $meta]); ?>
</div>
