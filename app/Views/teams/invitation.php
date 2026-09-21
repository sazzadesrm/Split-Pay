<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card"><div class="card-body text-center">
      <?php if (!$invitation): ?>
        <h5>Invitation not found</h5>
        <p class="text-muted">This invitation link is invalid.</p>
      <?php else: ?>
        <h5>You've been invited to join a Split Pay team</h5>
        <p class="text-muted">Role: <?= e(ucfirst($invitation['role'])) ?> · Status: <?= e(ucfirst($invitation['status'])) ?></p>
        <?php if ($invitation['status'] === 'pending'): ?>
        <div class="d-flex gap-2 justify-content-center">
          <form method="post" action="<?= url('/invitations/'.$token.'/accept') ?>">
            <?= csrf_field() ?><button class="btn btn-primary">Accept Invitation</button>
          </form>
          <form method="post" action="<?= url('/invitations/'.$token.'/decline') ?>">
            <?= csrf_field() ?><button class="btn btn-outline-secondary">Decline</button>
          </form>
        </div>
        <?php else: ?>
          <p>This invitation is no longer pending.</p>
        <?php endif; ?>
      <?php endif; ?>
    </div></div>
  </div>
</div>
