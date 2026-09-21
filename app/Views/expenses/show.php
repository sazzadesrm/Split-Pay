<div class="export-section">
<?php \App\Core\View::partial('partials/export-toolbar', ['exportTarget' => '.export-section', 'exportName' => 'expense-' . $expense['id']]); ?>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3"><div class="card-body">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
          <h4 class="mb-1"><?= e($expense['title']) ?></h4>
          <span class="badge <?= status_badge_class($expense['status']) ?>"><?= e(ucfirst($expense['status'])) ?></span>
        </div>
        <div class="text-end">
          <div class="fs-4 fw-bold"><?= money_fmt($expense['amount'], $expense['currency']) ?></div>
          <div class="text-muted small"><?= e(format_date($expense['expense_date'])) ?></div>
        </div>
      </div>
      <hr>
      <div class="row small">
        <div class="col-6 col-md-3 mb-2"><span class="text-muted d-block">Paid by</span><?= e($expense['payer_name']) ?></div>
        <div class="col-6 col-md-3 mb-2"><span class="text-muted d-block">Category</span><?= e($expense['category_name'] ?? '—') ?></div>
        <div class="col-6 col-md-3 mb-2"><span class="text-muted d-block">Project</span><?= e($expense['project_name'] ?? '—') ?></div>
        <div class="col-6 col-md-3 mb-2"><span class="text-muted d-block">Split type</span><?= e(ucfirst($expense['split_type'])) ?></div>
      </div>
      <?php if (!empty($expense['description'])): ?>
        <p class="mt-2 mb-0"><?= nl2br(e($expense['description'])) ?></p>
      <?php endif; ?>
      <?php if (!empty($tags)): ?>
        <div class="mt-2">
          <?php foreach ($tags as $t): ?><span class="badge bg-light text-dark border me-1">#<?= e($t['name']) ?></span><?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div></div>

    <div class="card mb-3"><div class="card-body">
      <h6 class="card-title">Split breakdown</h6>
      <table class="table table-sm">
        <thead><tr><th>Member</th><th class="text-end">Owed</th></tr></thead>
        <tbody>
          <?php foreach ($splits as $s): ?>
            <tr><td><?= e($s['user_name']) ?></td><td class="text-end"><?= money_fmt($s['owed_amount'], $expense['currency']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div></div>

    <?php if (!empty($receipts)): ?>
    <div class="card mb-3"><div class="card-body">
      <h6 class="card-title">Receipts</h6>
      <div class="row g-2">
        <?php foreach ($receipts as $r): ?>
          <div class="col-6 col-md-3">
            <div class="border rounded p-2 text-center h-100">
              <?php if (str_contains($r['mime_type'], 'pdf')): ?>
                <i class="bi bi-file-earmark-pdf fs-1 text-danger"></i>
              <?php else: ?>
                <img src="<?= url('/receipts/'.$r['id'].'/view') ?>" class="img-fluid rounded mb-1" style="max-height:90px;object-fit:cover" alt="Receipt">
              <?php endif; ?>
              <div class="small text-truncate"><?= e($r['original_filename']) ?></div>
              <a href="<?= url('/receipts/'.$r['id'].'/download') ?>" class="small">Download</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div></div>
    <?php endif; ?>

    <?php if (!empty($expense['approval_notes']) || $expense['approved_at'] || $expense['rejected_at']): ?>
    <div class="card mb-3"><div class="card-body">
      <h6 class="card-title">Approval information</h6>
      <?php if ($expense['approved_at']): ?><p class="mb-1 text-success"><i class="bi bi-check-circle"></i> Approved by <?= e($expense['approver_name'] ?? '') ?> on <?= e(format_date($expense['approved_at'])) ?></p><?php endif; ?>
      <?php if ($expense['rejected_at']): ?><p class="mb-1 text-danger"><i class="bi bi-x-circle"></i> Rejected by <?= e($expense['rejecter_name'] ?? '') ?> on <?= e(format_date($expense['rejected_at'])) ?></p><?php endif; ?>
      <?php if ($expense['approval_notes']): ?><p class="mb-0 small text-muted">Note: <?= e($expense['approval_notes']) ?></p><?php endif; ?>
    </div></div>
    <?php endif; ?>
  </div>

  <div class="col-lg-4">
    <div class="card no-print"><div class="card-body d-grid gap-2">
      <?php if ($canEdit): ?><a href="<?= url('/expenses/'.$expense['id'].'/edit') ?>" class="btn btn-outline-secondary">Edit</a><?php endif; ?>

      <?php if ($expense['status'] === 'draft'): ?>
        <form method="post" action="<?= url('/expenses/'.$expense['id'].'/submit') ?>"><?= csrf_field() ?>
          <button class="btn btn-primary w-100">Submit for Approval</button></form>
      <?php endif; ?>

      <?php if ($canApprove): ?>
        <form method="post" action="<?= url('/expenses/'.$expense['id'].'/approve') ?>"><?= csrf_field() ?>
          <button class="btn btn-success w-100">Approve</button></form>
        <button class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject</button>
      <?php endif; ?>

      <?php if ($role !== 'viewer' && $expense['status'] === 'approved' && in_array($role, ['owner','admin'], true)): ?>
        <form method="post" action="<?= url('/expenses/'.$expense['id'].'/reimburse') ?>"><?= csrf_field() ?>
          <button class="btn btn-outline-primary w-100">Mark Reimbursed</button></form>
      <?php endif; ?>

      <?php if ($expense['status'] === 'submitted' && $auth['id'] == $expense['created_by']): ?>
        <form method="post" action="<?= url('/expenses/'.$expense['id'].'/withdraw') ?>"><?= csrf_field() ?>
          <button class="btn btn-outline-secondary w-100">Withdraw to Draft</button></form>
      <?php endif; ?>

      <?php if ($canDelete): ?>
        <form method="post" action="<?= url('/expenses/'.$expense['id'].'/delete') ?>" data-confirm="Delete this expense? This cannot be undone from the UI.">
          <?= csrf_field() ?><button class="btn btn-outline-danger w-100">Delete</button></form>
      <?php endif; ?>
    </div></div>
  </div>
</div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" action="<?= url('/expenses/'.$expense['id'].'/reject') ?>" class="modal-content">
      <?= csrf_field() ?>
      <div class="modal-header"><h5 class="modal-title">Reject expense</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <label class="form-label">Reason (required)</label>
        <textarea name="reason" class="form-control" required rows="3"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger">Reject Expense</button>
      </div>
    </form>
  </div>
</div>
