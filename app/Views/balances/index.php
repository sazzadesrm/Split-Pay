<div class="export-section">
<?php \App\Core\View::partial('partials/export-toolbar', ['csvUrl' => url('/reports/balances/export'), 'exportTarget' => '.export-section', 'exportName' => 'balances']); ?>

<div class="row g-3 mb-3">
  <div class="col-6 col-lg-3"><div class="card kpi-card"><div class="kpi-label">Your balance</div><div class="kpi-value <?= $yourBalance['balance_cents']>=0?'text-success':'text-danger' ?>"><?= money_fmt($yourBalance['balance_cents']) ?></div></div></div>
  <div class="col-6 col-lg-3"><div class="card kpi-card"><div class="kpi-label">Team outstanding</div><div class="kpi-value"><?= money_fmt($teamOutstanding) ?></div></div></div>
  <div class="col-6 col-lg-3"><div class="card kpi-card"><div class="kpi-label">Who owes</div><div class="kpi-value"><?= count(array_filter($memberBalances, fn($m)=>$m['balance_cents']<0)) ?></div></div></div>
  <div class="col-6 col-lg-3"><div class="card kpi-card"><div class="kpi-label">Who is owed</div><div class="kpi-value"><?= count(array_filter($memberBalances, fn($m)=>$m['balance_cents']>0)) ?></div></div></div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card mb-3"><div class="card-body">
      <h6 class="card-title">Member balances</h6>
      <table class="table table-sm align-middle">
        <thead><tr><th>Member</th><th class="text-end">Paid</th><th class="text-end">Owed</th><th class="text-end">Balance</th></tr></thead>
        <tbody>
        <?php foreach ($memberBalances as $b): ?>
          <tr>
            <td><span class="avatar-circle me-2" style="width:26px;height:26px;font-size:.6rem"><?= e(initials($b['name'])) ?></span><?= e($b['name']) ?></td>
            <td class="text-end"><?= money_fmt($b['total_paid_cents']) ?></td>
            <td class="text-end"><?= money_fmt($b['total_owed_cents']) ?></td>
            <td class="text-end fw-semibold <?= $b['balance_cents']>=0?'text-success':'text-danger' ?>"><?= money_fmt($b['balance_cents']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div></div>

    <div class="card"><div class="card-body">
      <h6 class="card-title">Suggested settlements</h6>
      <?php if (empty($suggestions)): ?>
        <p class="text-muted small mb-0">Everyone is settled up.</p>
      <?php else: foreach ($suggestions as $s): ?>
        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
          <div><?= e($s['from_name']) ?> <i class="bi bi-arrow-right mx-1"></i> <?= e($s['to_name']) ?></div>
          <div class="d-flex align-items-center gap-2">
            <strong><?= money_fmt($s['amount_cents']) ?></strong>
            <button class="btn btn-sm btn-outline-primary" onclick="prefillSettlement('<?= $s['from_user_id'] ?>','<?= $s['to_user_id'] ?>','<?= \App\Core\Money::toDecimal($s['amount_cents']) ?>')" data-bs-toggle="modal" data-bs-target="#recordModal">Record payment</button>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div></div>
  </div>

  <div class="col-lg-5">
    <div class="card"><div class="card-body">
      <h6 class="card-title d-flex justify-content-between">Settle up <a href="<?= url('/settlements') ?>" class="small">History</a></h6>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#recordModal">Record a payment</button>
    </div></div>
  </div>
</div>
</div>

<div class="modal fade" id="recordModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" action="<?= url('/settlements') ?>" class="modal-content">
      <?= csrf_field() ?>
      <div class="modal-header"><h5 class="modal-title">Record a settlement</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label small">From (payer)</label>
          <select name="payer_id" id="payerSelect" class="form-select" required>
            <?php foreach ($memberBalances as $b): ?><option value="<?= $b['user_id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label small">To (receiver)</label>
          <select name="receiver_id" id="receiverSelect" class="form-select" required>
            <?php foreach ($memberBalances as $b): ?><option value="<?= $b['user_id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label small">Amount</label><input type="text" name="amount" id="amountField" class="form-control" pattern="^\d+(\.\d{1,2})?$" required></div>
        <div class="mb-2"><label class="form-label small">Payment method</label>
          <select name="payment_method" class="form-select">
            <option value="bank_transfer">Bank transfer</option><option value="cash">Cash</option><option value="paypal">PayPal</option><option value="card">Card</option><option value="other">Other</option>
          </select>
        </div>
        <div class="mb-2"><label class="form-label small">Date</label><input type="date" name="settlement_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
        <div class="mb-2"><label class="form-label small">Reference note</label><input type="text" name="reference_note" class="form-control" maxlength="255"></div>
        <div class="mb-2"><label class="form-label small">Status</label>
          <select name="status" class="form-select"><option value="pending">Pending</option><option value="completed">Completed</option></select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Settlement</button>
      </div>
    </form>
  </div>
</div>
<script>
function prefillSettlement(from, to, amount) {
  document.getElementById('payerSelect').value = from;
  document.getElementById('receiverSelect').value = to;
  document.getElementById('amountField').value = amount;
}
</script>
