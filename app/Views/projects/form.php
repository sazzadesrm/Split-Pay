<?php $isEdit = $project !== null; ?>
<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="card"><div class="card-body">
      <form method="post" action="<?= $isEdit ? url('/projects/'.$project['id'].'/update') : url('/projects') ?>">
        <?= csrf_field() ?>
        <div class="mb-3"><label class="form-label">Project name</label><input type="text" name="name" class="form-control" required maxlength="150" value="<?= e($project['name'] ?? '') ?>"></div>
        <div class="row g-2">
          <div class="col-md-6"><label class="form-label">Client name</label><input type="text" name="client_name" class="form-control" value="<?= e($project['client_name'] ?? '') ?>"></div>
          <div class="col-md-6"><label class="form-label">Project code</label><input type="text" name="project_code" class="form-control" value="<?= e($project['project_code'] ?? '') ?>"></div>
        </div>
        <div class="mb-3 mt-2"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"><?= e($project['description'] ?? '') ?></textarea></div>
        <div class="row g-2">
          <div class="col-md-4"><label class="form-label">Budget amount</label><input type="text" name="budget_amount" class="form-control" pattern="^\d+(\.\d{1,2})?$" value="<?= e($project['budget_amount'] ?? '') ?>"></div>
          <div class="col-md-4"><label class="form-label">Start date</label><input type="date" name="start_date" class="form-control" value="<?= e($project['start_date'] ?? '') ?>"></div>
          <div class="col-md-4"><label class="form-label">End date</label><input type="date" name="end_date" class="form-control" value="<?= e($project['end_date'] ?? '') ?>"></div>
        </div>
        <div class="mb-3 mt-2"><label class="form-label">Status</label>
          <select name="status" class="form-select" style="max-width:200px">
            <?php foreach (['active','paused','completed','archived'] as $s): ?><option value="<?= $s ?>" <?= ($project['status']??'active')===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Save Changes' : 'Create Project' ?></button>
      </form>
    </div></div>
  </div>
</div>
