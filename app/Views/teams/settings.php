<ul class="nav nav-tabs mb-3">
  <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#general">General</a></li>
  <li class="nav-item"><a class="nav-link" href="<?= url('/teams/'.$team['id'].'/members') ?>">Members</a></li>
  <li class="nav-item"><a class="nav-link" href="<?= url('/categories') ?>">Categories</a></li>
  <li class="nav-item"><a class="nav-link" href="<?= url('/tags') ?>">Tags</a></li>
  <li class="nav-item"><a class="nav-link" href="<?= url('/projects') ?>">Projects</a></li>
  <?php if ($role === 'owner'): ?><li class="nav-item"><a class="nav-link text-danger" data-bs-toggle="tab" href="#danger">Danger Zone</a></li><?php endif; ?>
</ul>

<div class="tab-content">
  <div class="tab-pane fade show active" id="general">
    <div class="card"><div class="card-body">
      <form method="post" action="<?= url('/teams/'.$team['id'].'/update') ?>">
        <?= csrf_field() ?>
        <div class="mb-3"><label class="form-label">Team name</label><input type="text" name="name" class="form-control" value="<?= e($team['name']) ?>" required></div>
        <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"><?= e($team['description']) ?></textarea></div>
        <?php if ($role === 'owner'): ?>
        <div class="mb-3">
          <label class="form-label">Default currency</label>
          <select name="default_currency" class="form-select" style="max-width:160px">
            <?php foreach (['USD','EUR','GBP','BDT'] as $c): ?><option value="<?= $c ?>" <?= $team['default_currency']===$c?'selected':'' ?>><?= $c ?></option><?php endforeach; ?>
          </select>
          <div class="form-text">Currency cannot be changed once approved financial records exist.</div>
        </div>
        <?php endif; ?>
        <button class="btn btn-primary" type="submit">Save Changes</button>
      </form>
    </div></div>
  </div>

  <?php if ($role === 'owner'): ?>
  <div class="tab-pane fade" id="danger">
    <div class="card border-danger"><div class="card-body">
      <h6 class="text-danger">Delete this team</h6>
      <p class="small text-muted">This will soft-delete the team and all its data. Type the team name to confirm.</p>
      <form method="post" action="<?= url('/teams/'.$team['id'].'/delete') ?>" data-confirm="Are you absolutely sure? This cannot be easily undone.">
        <?= csrf_field() ?>
        <input type="text" name="confirm_name" class="form-control mb-2" placeholder="Type &quot;<?= e($team['name']) ?>&quot; to confirm" required>
        <button class="btn btn-danger" type="submit">Delete Team</button>
      </form>
    </div></div>
  </div>
  <?php endif; ?>
</div>
