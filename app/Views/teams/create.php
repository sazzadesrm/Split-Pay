<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card"><div class="card-body">
      <h5 class="card-title mb-3">Create a Team</h5>
      <form method="post" action="<?= url('/teams') ?>">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">Team name</label>
          <input type="text" name="name" class="form-control" required minlength="2" maxlength="150">
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" maxlength="2000" rows="3"></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Default currency</label>
          <select name="default_currency" class="form-select">
            <option value="USD">USD</option><option value="EUR">EUR</option><option value="GBP">GBP</option><option value="BDT">BDT</option>
          </select>
        </div>
        <button class="btn btn-primary" type="submit">Create Team</button>
      </form>
    </div></div>
  </div>
</div>
