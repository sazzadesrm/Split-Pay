<?php \App\Core\View::setLayout('layouts/guest'); ?>
<h4 class="mb-4 text-center">Log in to Split Pay</h4>
<form method="post" action="<?= url('/login') ?>">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" required autofocus>
  </div>
  <div class="mb-3">
    <label class="form-label">Password</label>
    <input type="password" name="password" class="form-control" required>
  </div>
  <div class="mb-3 d-flex justify-content-between align-items-center">
    <div class="form-check">
      <input type="checkbox" class="form-check-input" id="remember">
      <label class="form-check-label small" for="remember">Remember me</label>
    </div>
    <a href="<?= url('/forgot-password') ?>" class="small">Forgot password?</a>
  </div>
  <button class="btn btn-primary w-100" type="submit">Log in</button>
</form>
<p class="text-center small text-muted mt-3 mb-0">Don't have an account? <a href="<?= url('/register') ?>">Create one</a></p>
