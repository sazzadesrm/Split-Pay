<?php $teamId = \App\Core\Auth::activeTeamId(); ?>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card"><div class="card-body">
      <h6 class="card-title">Members</h6>
      <div class="table-responsive">
      <table class="table align-middle">
        <thead><tr><th>Name</th><th>Role</th><th>Joined</th><th>Status</th><?php if (in_array($role,['owner','admin'],true)): ?><th></th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($members as $m): ?>
          <tr>
            <td><span class="avatar-circle me-2" style="width:28px;height:28px;font-size:.65rem"><?= e(initials($m['name'])) ?></span><?= e($m['name']) ?><div class="small text-muted"><?= e($m['email']) ?></div></td>
            <td>
              <?php if ($role === 'owner' && $m['role'] !== 'owner'): ?>
                <form method="post" action="<?= url('/teams/'.$teamId.'/members/'.$m['id'].'/role') ?>" class="d-inline">
                  <?= csrf_field() ?>
                  <select name="role" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                    <?php foreach (['admin','member','viewer'] as $r): ?><option value="<?= $r ?>" <?= $m['role']===$r?'selected':'' ?>><?= ucfirst($r) ?></option><?php endforeach; ?>
                  </select>
                </form>
              <?php else: ?>
                <span class="badge <?= role_badge_class($m['role']) ?>"><?= ucfirst($m['role']) ?></span>
              <?php endif; ?>
            </td>
            <td class="small text-muted"><?= e(format_date($m['joined_at'])) ?></td>
            <td><?= $m['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Former member</span>' ?></td>
            <?php if (in_array($role,['owner','admin'],true)): ?>
            <td>
              <?php if ($m['role'] !== 'owner' && $m['is_active']): ?>
                <form method="post" action="<?= url('/teams/'.$teamId.'/members/'.$m['id'].'/remove') ?>" data-confirm="Remove this member?">
                  <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Remove</button>
                </form>
              <?php endif; ?>
            </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div></div>
  </div>

  <div class="col-lg-4">
    <?php if (in_array($role,['owner','admin'],true)): ?>
    <div class="card mb-3"><div class="card-body">
      <h6 class="card-title">Invite a member</h6>
      <form method="post" action="<?= url('/teams/'.$teamId.'/members/invite') ?>">
        <?= csrf_field() ?>
        <div class="mb-2"><input type="email" name="email" class="form-control form-control-sm" placeholder="email@example.com" required></div>
        <div class="mb-2">
          <select name="role" class="form-select form-select-sm">
            <option value="member">Member</option><option value="admin">Admin</option><option value="viewer">Viewer</option>
          </select>
        </div>
        <button class="btn btn-sm btn-primary w-100" type="submit">Send Invitation</button>
      </form>
    </div></div>
    <div class="card"><div class="card-body">
      <h6 class="card-title">Pending invitations</h6>
      <?php if (empty($invitations)): ?><p class="small text-muted mb-0">No invitations sent yet.</p><?php endif; ?>
      <?php foreach ($invitations as $inv): ?>
        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
          <div>
            <div class="small fw-semibold"><?= e($inv['email']) ?></div>
            <div class="small text-muted"><?= e(ucfirst($inv['role'])) ?> · <?= e(ucfirst($inv['status'])) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div></div>
    <?php endif; ?>
  </div>
</div>
