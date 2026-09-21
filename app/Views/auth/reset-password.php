<?php \App\Core\View::setLayout('layouts/guest'); ?>
<h4 class="mb-4 text-center">Reset your password</h4>
<form method="post" action="<?= url('/reset-password') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="token" value="<?= e($token) ?>">
  <div class="mb-3">
    <label class="form-label">New password</label>
    <input type="password" name="password" class="form-control" required minlength="8" autofocus>
  </div>
  <div class="mb-3">
    <label class="form-label">Confirm new password</label>
    <input type="password" name="password_confirmation" class="form-control" required minlength="8">
  </div>
  <button class="btn btn-primary w-100" type="submit">Reset password</button>
</form>
