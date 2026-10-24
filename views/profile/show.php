<div class="row g-3">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header">Profile</div>
      <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-3">
          <span class="avatar lg"><?= e(initials($user['name'])) ?></span>
          <div><div class="fw-semibold"><?= e($user['name']) ?></div><div class="text-muted small">@<?= e($user['username']) ?></div></div>
        </div>
        <form method="post" action="<?= url('/profile') ?>">
          <?= csrf_field() ?>
          <div class="mb-3"><label class="form-label">Name <span class="req">*</span></label>
            <input name="name" class="form-control<?= invalid('name') ?>" value="<?= e(fv($user, 'name')) ?>" required><?= error_for('name') ?></div>
          <div class="mb-3"><label class="form-label">Email</label>
            <input name="email" type="email" class="form-control<?= invalid('email') ?>" value="<?= e(fv($user, 'email')) ?>"><?= error_for('email') ?></div>
          <div class="mb-3"><label class="form-label">Phone</label>
            <input name="phone" class="form-control<?= invalid('phone') ?>" value="<?= e(fv($user, 'phone')) ?>"><?= error_for('phone') ?></div>
          <button class="btn btn-brand">Save</button>
          <a href="<?= url('/profile/password') ?>" class="btn btn-light">Change password</a>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">Recent sign-ins</div>
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>Date</th><th>IP</th><th>Device</th><th>Result</th></tr></thead>
          <tbody>
          <?php foreach ($logins as $l): ?>
            <tr><td class="text-nowrap"><?= e(fdate($l['created_at'], true)) ?></td><td><?= e($l['ip']) ?></td>
              <td class="small text-muted text-truncate" style="max-width:260px"><?= e($l['user_agent']) ?></td>
              <td><?= $l['success'] ? status_badge('success', '#198754') : status_badge('failed', '#dc3545') ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
