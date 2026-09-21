<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Settlement History</h5>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#recordModal2"><i class="bi bi-plus-lg"></i> Record Payment</button>
</div>

<div class="card card-body mb-3">
  <form method="get" class="row g-2">
    <div class="col-6 col-md-2"><input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>" class="form-control form-control-sm"></div>
    <div class="col-6 col-md-2"><input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>" class="form-control form-control-sm"></div>
    <div class="col-6 col-md-3">
      <select name="payer_id" class="form-select form-select-sm"><option value="">Any payer</option>
        <?php foreach ($members as $m): ?><option value="<?= $m['user_id'] ?>" <?= (string)($filters['payer_id']??'')===(string)$m['user_id']?'selected':'' ?>><?= e($m['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-3">
      <select name="status" class="form-select form-select-sm"><option value="">Any status</option>
        <?php foreach (['pending','completed','cancelled'] as $s): ?><option value="<?= $s ?>" <?= ($filters['status']??'')===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2"><button class="btn btn-sm btn-outline-secondary w-100" type="submit">Filter</button></div>
  </form>
</div>

<div class="export-section">
<?php \App\Core\View::partial('partials/export-toolbar', ['csvUrl' => url('/reports/settlements/export'), 'exportTarget' => '.export-section', 'exportName' => 'settlements']); ?>
<?php if (empty($settlements)): ?>
  <?php \App\Core\View::partial('partials/empty-state', ['icon' => 'bi-cash-stack', 'title' => 'No settlements recorded', 'message' => 'When team members repay shared costs, record the payment here.']); ?>
<?php else: ?>
  <?php foreach ($settlements as $s): \App\Core\View::partial('partials/settlement-card', ['s' => $s, 'role' => $role, 'userId' => $auth['id']]); endforeach; ?>
  <?php \App\Core\View::partial('partials/pagination', ['meta' => $meta]); ?>
<?php endif; ?>
</div>

<div class="modal fade" id="recordModal2" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" action="<?= url('/settlements') ?>" class="modal-content">
      <?= csrf_field() ?>
      <div class="modal-header"><h5 class="modal-title">Record a settlement</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label small">From (payer)</label>
          <select name="payer_id" class="form-select" required>
            <?php foreach ($members as $m): ?><option value="<?= $m['user_id'] ?>"><?= e($m['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label small">To (receiver)</label>
          <select name="receiver_id" class="form-select" required>
            <?php foreach ($members as $m): ?><option value="<?= $m['user_id'] ?>"><?= e($m['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label small">Amount</label><input type="text" name="amount" class="form-control" pattern="^\d+(\.\d{1,2})?$" required></div>
        <div class="mb-2"><label class="form-label small">Payment method</label>
          <select name="payment_method" class="form-select">
            <option value="bank_transfer">Bank transfer</option><option value="cash">Cash</option><option value="paypal">PayPal</option><option value="card">Card</option><option value="other">Other</option>
          </select>
        </div>
        <div class="mb-2"><label class="form-label small">Date</label><input type="date" name="settlement_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
        <div class="mb-2"><label class="form-label small">Reference note</label><input type="text" name="reference_note" class="form-control" maxlength="255"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Settlement</button>
      </div>
    </form>
  </div>
</div>
