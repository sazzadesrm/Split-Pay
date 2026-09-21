<?php
/** Vars: $e (expense row with joined names), $role */
$status = $e['status'];
?>
<div class="txn-card">
  <div class="txn-top">
    <div class="d-flex align-items-start gap-2">
      <span class="txn-avatar" title="<?= e($e['payer_name']) ?>"><?= e(initials($e['payer_name'])) ?></span>
      <div>
        <a href="<?= url('/expenses/' . $e['id']) ?>" class="txn-title text-decoration-none text-body"><?= e($e['title']) ?></a>
        <div class="txn-meta">
          <span><i class="bi bi-calendar3"></i> <?= e(format_date($e['expense_date'])) ?></span>
          <span><i class="bi bi-person"></i> Paid by <?= e($e['payer_name']) ?></span>
          <?php if (!empty($e['category_name'])): ?>
            <span><span class="badge" style="background: <?= e($e['category_color'] ?? '#94A3B8') ?>"><?= e($e['category_name']) ?></span></span>
          <?php endif; ?>
          <?php if (!empty($e['project_name'])): ?>
            <span><i class="bi bi-kanban"></i> <?= e($e['project_name']) ?></span>
          <?php endif; ?>
          <?php if (!empty($e['receipt_count'])): ?>
            <span><i class="bi bi-paperclip"></i> <?= (int) $e['receipt_count'] ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="text-end">
      <div class="txn-amount"><?= money_fmt($e['amount'], $e['currency']) ?></div>
      <span class="badge <?= status_badge_class($status) ?>"><?= e(ucfirst($status)) ?></span>
    </div>
  </div>
  <div class="txn-actions">
    <a href="<?= url('/expenses/' . $e['id']) ?>" class="btn btn-sm btn-outline-secondary">View</a>
  </div>
</div>
