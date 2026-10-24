<?php
$roleNames = array_column(array_filter($roles, static fn($r) => in_array((int) $r['id'], $user['role_ids'], true)), 'name');
App\Core\View::start('actions'); ?>
<?php if (can('users.create')): ?><a href="<?= url('/users/create') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i>New User</a><?php endif; ?>
<?php if (can('users.edit')): ?><a href="<?= url('/users/' . $user['id'] . '/edit') ?>" class="btn btn-brand"><i class="bi bi-pencil me-1"></i>Edit</a><?php endif; ?>
<?php if (can('users.delete') && $user['id'] !== auth_id()): ?>
  <?= $user['status'] === 'active'
      ? post_button('/users/' . $user['id'] . '/status', 'Deactivate', ['fields' => ['status' => 'inactive'], 'confirm' => 'Deactivate this user? They will be signed out immediately.', 'class' => 'btn btn-outline-warning'])
      : post_button('/users/' . $user['id'] . '/status', 'Activate', ['fields' => ['status' => 'active'], 'class' => 'btn btn-outline-success']) ?>
  <?= post_button('/users/' . $user['id'] . '/delete', 'Delete', ['confirm' => 'Delete this user permanently from the list?', 'class' => 'btn btn-outline-danger']) ?>
<?php endif; ?>
<?php App\Core\View::end(); ?>
<div class="row g-3">
  <div class="col-lg-5">
    <div class="card mb-3"><div class="card-body">
      <div class="d-flex align-items-center gap-3 mb-3">
        <span class="avatar lg"><?= e(initials($user['name'])) ?></span>
        <div><div class="fw-semibold fs-5"><?= e($user['name']) ?></div><div class="text-muted">@<?= e($user['username']) ?></div></div>
        <div class="ms-auto"><?= status_badge($user['status']) ?></div>
      </div>
      <dl class="dl-grid">
        <dt>Email</dt><dd><?= e($user['email'] ?: '—') ?></dd>
        <dt>Phone</dt><dd><?= e($user['phone'] ?: '—') ?></dd>
        <dt>Roles</dt><dd><?= e(implode(', ', $roleNames)) ?></dd>
        <?php if ($customer): ?><dt>Customer</dt><dd><?= can('customers.view') ? '<a href="' . url('/customers/' . $customer['id']) . '">' . e($customer['customer_code']) . '</a>' : e($customer['customer_code']) ?></dd><?php endif; ?>
        <dt>Last login</dt><dd><?= $user['last_login_at'] ? e(fdate($user['last_login_at'], true) . ' · ' . $user['last_login_ip']) : 'Never' ?></dd>
        <dt>Created</dt><dd><?= e(fdate($user['created_at'])) ?></dd>
      </dl>
    </div></div>
    <?php if ($funds !== null): ?>
    <div class="card mb-3"><div class="card-header d-flex justify-content-between align-items-center">Fund contributions
        <?php if (can('funds.create')): ?><a href="<?= url('/funds/create', ['contributor' => $user['id']]) ?>" class="btn btn-sm btn-outline-brand"><i class="bi bi-plus-lg"></i> Record</a><?php endif; ?></div>
      <div class="card-body">
        <div class="stat-label">Total received</div><div class="fs-4 fw-semibold mb-2"><?= money($funds['total']) ?></div>
        <?php foreach ($funds['by_type'] as $type => $amt): ?><div class="d-flex justify-content-between small"><span><?= e(App\Services\FundService::TYPES[$type]) ?></span><span><?= money($amt) ?></span></div><?php endforeach; ?>
        <a href="<?= url('/funds', ['contributor' => $user['id']]) ?>" class="small d-inline-block mt-2">View all</a>
      </div></div>
    <?php endif; ?>
    <?php if (can('users.edit')): ?>
    <div class="card"><div class="card-header">Reset password</div><div class="card-body">
      <form method="post" action="<?= url('/users/' . $user['id'] . '/password') ?>" autocomplete="off" data-confirm="Reset this user's password? They will be signed out.">
        <?= csrf_field() ?>
        <div class="row g-2">
          <?= form_input('password', 'New password', null, ['type' => 'password', 'required' => true, 'col' => 'col-sm-6', 'attrs' => ['autocomplete' => 'new-password']]) ?>
          <?= form_input('password_confirmation', 'Confirm', null, ['type' => 'password', 'required' => true, 'col' => 'col-sm-6', 'attrs' => ['autocomplete' => 'new-password']]) ?>
        </div>
        <button class="btn btn-outline-brand btn-sm mt-3">Reset password</button>
      </form>
    </div></div>
    <?php endif; ?>
  </div>
  <div class="col-lg-7">
    <div class="card"><div class="card-header">Login history</div>
      <div class="table-responsive"><table class="table">
        <thead><tr><th>Date</th><th>IP</th><th>Device</th><th>Result</th></tr></thead>
        <tbody>
        <?php foreach ($logins as $l): ?>
          <tr><td class="text-nowrap small"><?= e(fdate($l['created_at'], true)) ?></td><td class="small"><?= e($l['ip']) ?></td>
            <td class="small text-muted text-truncate" style="max-width:240px"><?= e($l['user_agent']) ?></td>
            <td><?= $l['success'] ? '<span class="badge text-bg-success">Success</span>' : '<span class="badge text-bg-danger">Failed</span>' ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$logins) echo empty_row(4, 'No sign-ins yet.'); ?>
        </tbody>
      </table></div>
    </div>
  </div>
</div>
