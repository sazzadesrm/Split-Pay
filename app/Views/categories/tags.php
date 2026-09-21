<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Tags</h5>
  <a href="<?= url('/categories') ?>" class="btn btn-outline-secondary btn-sm">Manage Categories</a>
</div>

<?php if (in_array($role,['owner','admin'],true)): ?>
<div class="card card-body mb-3">
  <form method="post" action="<?= url('/tags') ?>" class="row g-2 align-items-end">
    <?= csrf_field() ?>
    <div class="col-md-6"><label class="form-label small">Tag name</label><input type="text" name="name" class="form-control form-control-sm" required maxlength="80"></div>
    <div class="col-md-3"><button class="btn btn-sm btn-primary w-100" type="submit">Add Tag</button></div>
  </form>
</div>
<?php endif; ?>

<div class="d-flex flex-wrap gap-2">
  <?php foreach ($tags as $t): ?>
    <span class="badge bg-light text-dark border d-flex align-items-center gap-2 py-2 px-3">
      #<?= e($t['name']) ?>
      <?php if (in_array($role,['owner','admin'],true)): ?>
      <form method="post" action="<?= url('/tags/'.$t['id'].'/delete') ?>" data-confirm="Delete tag #<?= e($t['name']) ?>?">
        <?= csrf_field() ?><button class="btn btn-sm btn-link p-0 text-danger"><i class="bi bi-x"></i></button>
      </form>
      <?php endif; ?>
    </span>
  <?php endforeach; ?>
  <?php if (empty($tags)): ?><p class="text-muted small">No tags yet.</p><?php endif; ?>
</div>
