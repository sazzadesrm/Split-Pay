<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Categories</h5>
  <a href="<?= url('/tags') ?>" class="btn btn-outline-secondary btn-sm">Manage Tags</a>
</div>

<?php if (in_array($role,['owner','admin'],true)): ?>
<div class="card card-body mb-3">
  <form method="post" action="<?= url('/categories') ?>" class="row g-2 align-items-end">
    <?= csrf_field() ?>
    <div class="col-md-4"><label class="form-label small">Name</label><input type="text" name="name" class="form-control form-control-sm" required maxlength="120"></div>
    <div class="col-md-3"><label class="form-label small">Color</label><input type="color" name="color" class="form-control form-control-sm form-control-color" value="#4F46E5"></div>
    <div class="col-md-2"><label class="form-label small">Sort order</label><input type="number" name="sort_order" class="form-control form-control-sm" value="0"></div>
    <div class="col-md-3"><button class="btn btn-sm btn-primary w-100" type="submit">Add Category</button></div>
  </form>
</div>
<?php endif; ?>

<div class="row g-2">
  <?php foreach ($categories as $c): ?>
    <div class="col-md-6 col-lg-4">
      <div class="card card-body d-flex flex-row align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <span class="badge" style="background: <?= e($c['color']) ?>">&nbsp;</span>
          <span class="<?= $c['is_archived'] ? 'text-muted text-decoration-line-through' : '' ?>"><?= e($c['name']) ?></span>
          <?php if ($c['team_id'] === null): ?><span class="badge bg-light text-dark border">Global</span><?php endif; ?>
        </div>
        <?php if (in_array($role,['owner','admin'],true) && $c['team_id'] !== null): ?>
        <form method="post" action="<?= url('/categories/'.$c['id'].'/archive') ?>">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-link"><?= $c['is_archived'] ? 'Restore' : 'Archive' ?></button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
