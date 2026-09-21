<?php /** Vars: $icon, $title, $message, $actionUrl, $actionLabel */ ?>
<div class="empty-state">
  <i class="bi <?= e($icon ?? 'bi-inbox') ?>"></i>
  <h5 class="mt-3"><?= e($title ?? 'Nothing here yet') ?></h5>
  <p class="mb-3"><?= e($message ?? '') ?></p>
  <?php if (!empty($actionUrl)): ?>
    <a href="<?= e($actionUrl) ?>" class="btn btn-primary btn-sm"><?= e($actionLabel ?? 'Get started') ?></a>
  <?php endif; ?>
</div>
