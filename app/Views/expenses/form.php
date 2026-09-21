<?php
$isEdit = $expense !== null;
$action = $isEdit ? url('/expenses/' . $expense['id'] . '/update') : url('/expenses');
$existingSplits = [];
foreach ($splits as $s) { $existingSplits[$s['user_id']] = $s; }
?>
<form method="post" action="<?= $action ?>" enctype="multipart/form-data" id="expenseForm">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card mb-3"><div class="card-body">
        <h6 class="card-title">Details</h6>
        <div class="mb-3">
          <label class="form-label">Title</label>
          <input type="text" name="title" class="form-control" maxlength="180" required value="<?= e($expense['title'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" maxlength="5000" rows="2"><?= e($expense['description'] ?? '') ?></textarea>
        </div>
        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label">Amount</label>
            <input type="text" name="amount" id="amountInput" class="form-control" required pattern="^\d+(\.\d{1,2})?$" value="<?= e($expense['amount'] ?? '') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Expense date</label>
            <input type="date" name="expense_date" class="form-control" required value="<?= e($expense['expense_date'] ?? date('Y-m-d')) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Paid by</label>
            <select name="paid_by_user_id" class="form-select" required>
              <?php foreach ($members as $m): if (!$m['is_active']) continue; ?>
                <option value="<?= $m['user_id'] ?>" <?= (string)($expense['paid_by_user_id'] ?? $auth['id']) === (string)$m['user_id'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div></div>

      <div class="card mb-3"><div class="card-body">
        <h6 class="card-title">Classification</h6>
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select">
              <option value="">None</option>
              <?php foreach ($categories as $c): if ($c['is_archived']) continue; ?>
                <option value="<?= $c['id'] ?>" <?= (string)($expense['category_id'] ?? '') === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Project / Client</label>
            <select name="project_id" class="form-select">
              <option value="">None</option>
              <?php foreach ($projects as $p): ?>
                <option value="<?= $p['id'] ?>" <?= (string)($expense['project_id'] ?? '') === (string)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?><?= $p['client_name'] ? ' — '.e($p['client_name']) : '' ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="mt-2">
          <label class="form-label">Tags (up to 10)</label>
          <input type="text" id="tagsInput" class="form-control" placeholder="Comma-separated tags, e.g. client-a, urgent" value="<?= e(implode(', ', $tags)) ?>">
          <input type="hidden" name="tags_json" id="tagsHidden">
        </div>
      </div></div>

      <div class="card mb-3"><div class="card-body">
        <h6 class="card-title">Split configuration</h6>
        <div class="mb-2">
          <label class="form-label">Split type</label>
          <select name="split_type" id="splitType" class="form-select">
            <?php foreach (['equal'=>'Equal','exact'=>'Exact amounts','percentage'=>'Percentage','shares'=>'Shares'] as $val=>$label): ?>
              <option value="<?= $val ?>" <?= ($expense['split_type'] ?? 'equal') === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <label class="form-label">Participants</label>
        <div id="participantsList">
          <?php foreach ($members as $m): if (!$m['is_active']) continue; $sp = $existingSplits[$m['user_id']] ?? null; ?>
            <div class="d-flex align-items-center gap-2 mb-2 participant-row" data-user-id="<?= $m['user_id'] ?>">
              <div class="form-check flex-shrink-0" style="width:180px">
                <input class="form-check-input participant-check" type="checkbox" name="participants[]" value="<?= $m['user_id'] ?>" id="p<?= $m['user_id'] ?>" <?= $sp ? 'checked' : '' ?>>
                <label class="form-check-label" for="p<?= $m['user_id'] ?>"><?= e($m['name']) ?></label>
              </div>
              <input type="text" class="form-control form-control-sm split-exact" name="exact_amount_<?= $m['user_id'] ?>" placeholder="Amount" value="<?= $sp['owed_amount'] ?? '' ?>" style="display:none;max-width:120px">
              <input type="text" class="form-control form-control-sm split-percentage" name="percentage_<?= $m['user_id'] ?>" placeholder="%" value="<?= $sp['split_percentage'] ?? '' ?>" style="display:none;max-width:100px">
              <input type="number" min="1" class="form-control form-control-sm split-shares" name="shares_<?= $m['user_id'] ?>" placeholder="Shares" value="<?= $sp['shares'] ?? '' ?>" style="display:none;max-width:100px">
              <span class="small text-muted split-owed-preview flex-shrink-0" style="width:90px"></span>
            </div>
          <?php endforeach; ?>
        </div>
        <div id="splitWarning" class="alert alert-warning small py-2 d-none mt-2"></div>
        <div id="splitTotal" class="small text-muted mt-1"></div>
      </div></div>

      <div class="card mb-3"><div class="card-body">
        <h6 class="card-title">Receipts and notes</h6>
        <div class="mb-2">
          <label class="form-label">Attach receipts (JPG, PNG, WEBP, PDF — up to 10 files, 10 MB each)</label>
          <input type="file" name="receipts[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.webp,.pdf">
        </div>
        <?php if ($isEdit && !empty($expense['id'])): $receiptsList = (new \App\Models\Expense())->receipts((int) $expense['id']); ?>
          <?php foreach ($receiptsList as $r): ?>
            <div class="d-flex align-items-center gap-2 mb-1">
              <i class="bi <?= str_contains($r['mime_type'],'pdf') ? 'bi-file-earmark-pdf' : 'bi-file-earmark-image' ?>"></i>
              <a href="<?= url('/receipts/'.$r['id'].'/view') ?>" target="_blank"><?= e($r['original_filename']) ?></a>
              <form method="post" action="<?= url('/receipts/'.$r['id'].'/delete') ?>" data-confirm="Delete this receipt?">
                <?= csrf_field() ?><button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div></div>
    </div>

    <div class="col-lg-4">
      <div class="card position-sticky" style="top:80px">
        <div class="card-body d-grid gap-2">
          <?php if (!$isEdit): ?>
            <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">Save Draft</button>
            <button type="submit" name="action" value="submit" class="btn btn-primary">Submit for Approval</button>
          <?php else: ?>
            <button type="submit" class="btn btn-primary">Save Changes</button>
          <?php endif; ?>
          <a href="<?= url($isEdit ? '/expenses/'.$expense['id'] : '/expenses') ?>" class="btn btn-link">Cancel</a>
        </div>
      </div>
    </div>
  </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const splitType = document.getElementById('splitType');
  const rows = document.querySelectorAll('.participant-row');
  const warning = document.getElementById('splitWarning');
  const totalEl = document.getElementById('splitTotal');
  const tagsInput = document.getElementById('tagsInput');

  function toggleFields() {
    const type = splitType.value;
    rows.forEach(row => {
      row.querySelector('.split-exact').style.display = type === 'exact' ? 'block' : 'none';
      row.querySelector('.split-percentage').style.display = type === 'percentage' ? 'block' : 'none';
      row.querySelector('.split-shares').style.display = type === 'shares' ? 'block' : 'none';
    });
  }
  splitType.addEventListener('change', () => { toggleFields(); preview(); });
  toggleFields();

  async function preview() {
    const amount = document.getElementById('amountInput').value || '0';
    const type = splitType.value;
    const params = new URLSearchParams();
    params.set('amount', amount);
    params.set('split_type', type);
    const participants = [];
    rows.forEach(row => {
      const uid = row.dataset.userId;
      const checked = row.querySelector('.participant-check').checked;
      if (checked) {
        participants.push(uid);
        params.append('participants[]', uid);
        if (type === 'exact') params.set('exact_amount_' + uid, row.querySelector('.split-exact').value || '0');
        if (type === 'percentage') params.set('percentage_' + uid, row.querySelector('.split-percentage').value || '0');
        if (type === 'shares') params.set('shares_' + uid, row.querySelector('.split-shares').value || '0');
      }
    });
    if (!participants.length) { warning.classList.add('d-none'); totalEl.textContent = ''; return; }

    const result = await window.spFetch(window.APP_URL + '/expenses/preview-split?' + params.toString(), { method: 'POST' });
    if (!result.success) {
      warning.textContent = result.message;
      warning.classList.remove('d-none');
      totalEl.textContent = '';
      return;
    }
    warning.classList.add('d-none');
    let sum = 0;
    result.data.splits.forEach(s => {
      const row = document.querySelector('.participant-row[data-user-id="' + s.user_id + '"]');
      if (row) row.querySelector('.split-owed-preview').textContent = '= $' + s.owed_amount;
      sum += parseFloat(s.owed_amount);
    });
    totalEl.textContent = 'Total allocated: $' + sum.toFixed(2) + ' of $' + parseFloat(result.data.total).toFixed(2);
  }

  document.getElementById('expenseForm').addEventListener('input', function (e) {
    if (e.target.matches('.split-exact,.split-percentage,.split-shares,#amountInput,.participant-check')) preview();
  });
  preview();

  document.getElementById('expenseForm').addEventListener('submit', function () {
    const tags = tagsInput.value.split(',').map(t => t.trim()).filter(Boolean).slice(0, 10);
    tags.forEach(t => {
      const el = document.createElement('input');
      el.type = 'hidden'; el.name = 'tags[]'; el.value = t;
      this.appendChild(el);
    });
  });
});
</script>
