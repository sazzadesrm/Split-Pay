<?php
/** Vars: $s (settlement row), $role, $userId */
?>
<div class="txn-card">
  <div class="txn-top">
    <div class="d-flex align-items-start gap-2">
      <span class="txn-avatar" title="<?= e($s['payer_name']) ?>"><?= e(initials($s['payer_name'])) ?></span>
      <div>
        <div class="txn-title"><?= e($s['payer_name']) ?> <i class="bi bi-arrow-right text-muted mx-1"></i> <?= e($s['receiver_name']) ?></div>
        <div class="txn-meta">
          <span><i class="bi bi-calendar3"></i> <?= e(format_date($s['settlement_date'])) ?></span>
          <span><i class="bi bi-credit-card"></i> <?= e(ucwords(str_replace('_', ' ', $s['payment_method']))) ?></span>
          <?php if (!empty($s['reference_note'])): ?><span><i class="bi bi-chat-left-text"></i> <?= e($s['reference_note']) ?></span><?php endif; ?>
        </div>
      </div>
    </div>
    <div class="text-end">
      <div class="txn-amount"><?= money_fmt($s['amount'], $s['currency']) ?></div>
      <span class="badge <?= status_badge_class($s['status']) ?>"><?= e(ucfirst($s['status'])) ?></span>
    </div>
  </div>
  <?php $involved = in_array($userId ?? 0, [(int)$s['payer_id'], (int)$s['receiver_id']], true); ?>
  <?php if ($s['status'] === 'pending' && (in_array($role, ['owner','admin'], true) || $involved)): ?>
  <div class="txn-actions">
    <form method="post" action="<?= url('/settlements/' . $s['id'] . '/complete') ?>">
      <?= csrf_field() ?>
      <button class="btn btn-sm btn-success"><i class="bi bi-check2"></i> Mark Completed</button>
    </form>
    <form method="post" action="<?= url('/settlements/' . $s['id'] . '/cancel') ?>" data-confirm="Cancel this settlement?">
      <?= csrf_field() ?>
      <button class="btn btn-sm btn-outline-danger">Cancel</button>
    </form>
    <?php if (in_array($role, ['owner','admin'], true)): ?>
    <form method="post" action="<?= url('/settlements/' . $s['id'] . '/delete') ?>" data-confirm="Delete this settlement?">
      <?= csrf_field() ?>
      <button class="btn btn-sm btn-outline-secondary">Delete</button>
    </form>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>
