<?php \App\Core\View::setLayout('layouts/guest'); ?>
<h4 class="mb-3 text-center">Forgot your password?</h4>
<p class="text-muted small text-center mb-4">Enter your email and we'll send you a reset link.</p>
<form method="post" action="<?= url('/forgot-password') ?>">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" required autofocus>
  </div>
  <button class="btn btn-primary w-100" type="submit">Send reset link</button>
</form>
<p class="text-center small text-muted mt-3 mb-0"><a href="<?= url('/login') ?>">Back to login</a></p>
