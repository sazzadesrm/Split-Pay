<?php \App\Core\View::setLayout('layouts/guest'); ?>
<h4 class="mb-4 text-center">Create your account</h4>
<form method="post" action="<?= url('/register') ?>">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label">Full name</label>
    <input type="text" name="name" class="form-control" value="<?= old('name') ?>" required minlength="2" maxlength="120" autofocus>
  </div>
  <div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" value="<?= old('email') ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Password</label>
    <input type="password" name="password" class="form-control" required minlength="8">
    <div class="form-text">At least 8 characters.</div>
  </div>
  <div class="mb-3">
    <label class="form-label">Confirm password</label>
    <input type="password" name="password_confirmation" class="form-control" required minlength="8">
  </div>
  <button class="btn btn-primary w-100" type="submit">Create account</button>
</form>
<p class="text-center small text-muted mt-3 mb-0">Already have an account? <a href="<?= url('/login') ?>">Log in</a></p>
