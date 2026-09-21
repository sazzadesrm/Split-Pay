<div class="row g-3">
  <div class="col-lg-4 text-center">
    <div class="card"><div class="card-body">
      <?php if (!empty($user['avatar_path'])): ?>
        <img src="<?= url('/avatars/'.$user['id']) ?>" class="rounded-circle mb-2" style="width:96px;height:96px;object-fit:cover">
      <?php else: ?>
        <span class="avatar-circle mx-auto mb-2" style="width:96px;height:96px;font-size:1.6rem"><?= e(initials($user['name'])) ?></span>
      <?php endif; ?>
      <h6><?= e($user['name']) ?></h6>
      <p class="text-muted small"><?= e($user['email']) ?></p>
      <form method="post" action="<?= url('/profile/avatar') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="file" name="avatar" class="form-control form-control-sm mb-2" accept=".jpg,.jpeg,.png,.webp">
        <button class="btn btn-sm btn-outline-primary w-100" type="submit">Upload Avatar</button>
      </form>
    </div></div>
  </div>

  <div class="col-lg-8">
    <div class="card mb-3"><div class="card-body">
      <h6 class="card-title">Profile</h6>
      <form method="post" action="<?= url('/profile/update') ?>">
        <?= csrf_field() ?>
        <div class="mb-2"><label class="form-label small">Name</label><input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required></div>
        <div class="mb-2"><label class="form-label small">Email</label><input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required></div>
        <div class="mb-2"><label class="form-label small">Default currency</label>
          <select name="default_currency" class="form-select" style="max-width:160px">
            <?php foreach (['USD','EUR','GBP','BDT'] as $c): ?><option value="<?= $c ?>" <?= $user['default_currency']===$c?'selected':'' ?>><?= $c ?></option><?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn-primary btn-sm" type="submit">Save Changes</button>
      </form>
    </div></div>

    <div class="card"><div class="card-body">
      <h6 class="card-title">Change password</h6>
      <form method="post" action="<?= url('/profile/password') ?>">
        <?= csrf_field() ?>
        <div class="mb-2"><label class="form-label small">Current password</label><input type="password" name="current_password" class="form-control" required></div>
        <div class="mb-2"><label class="form-label small">New password</label><input type="password" name="new_password" class="form-control" required minlength="8"></div>
        <div class="mb-2"><label class="form-label small">Confirm new password</label><input type="password" name="new_password_confirmation" class="form-control" required minlength="8"></div>
        <button class="btn btn-primary btn-sm" type="submit">Update Password</button>
      </form>
    </div></div>
  </div>
</div>
