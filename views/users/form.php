<?php
$roleIds = App\Core\Session::getFlash('_old')['roles'] ?? ($user['role_ids'] ?? []);
$roleIds = array_map('intval', (array) $roleIds);
?>
<form method="post" action="<?= url($user ? '/users/' . $user['id'] : '/users') ?>" autocomplete="off">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card"><div class="card-header">Account</div><div class="card-body"><div class="row g-3">
        <?= form_input('name', 'Full name', $user, ['required' => true]) ?>
        <?= form_input('username', 'Username', $user, ['required' => true, 'help' => 'Letters, numbers, dot, dash, underscore']) ?>
        <?= form_input('email', 'Email', $user, ['type' => 'email']) ?>
        <?= form_input('phone', 'Phone', $user) ?>
        <?= form_select('status', 'Status', ['active' => 'Active', 'inactive' => 'Inactive'], $user, ['default' => 'active']) ?>
        <?php if ($companies): ?>
          <?= form_select('company_id', 'Company', array_column($companies, 'name', 'id'), $user, ['default' => tenant_id()]) ?>
        <?php endif; ?>
        <?php if (!$user): ?>
          <?= form_input('password', 'Password', null, ['type' => 'password', 'required' => true, 'help' => 'Min 8 characters with letters and numbers', 'attrs' => ['autocomplete' => 'new-password']]) ?>
          <?= form_input('password_confirmation', 'Confirm password', null, ['type' => 'password', 'required' => true, 'attrs' => ['autocomplete' => 'new-password']]) ?>
        <?php endif; ?>
      </div></div></div>
    </div>
    <div class="col-lg-4">
      <div class="card"><div class="card-header">Roles <span class="req text-danger">*</span></div><div class="card-body">
        <?php foreach ($roles as $r): ?>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="roles[]" value="<?= (int) $r['id'] ?>" id="role<?= (int) $r['id'] ?>"<?= in_array((int) $r['id'], $roleIds, true) ? ' checked' : '' ?>>
            <label class="form-check-label" for="role<?= (int) $r['id'] ?>"><?= e($r['name']) ?> <?php if (!$r['company_id']): ?><span class="badge text-bg-light text-dark">system</span><?php endif; ?></label>
          </div>
        <?php endforeach; ?>
        <?= error_for('roles') ?>
        <div class="form-text">Users with the <strong>Customer</strong> role are added to the customer list automatically (linked by phone/email if they already exist there).</div>
      </div></div>
    </div>
  </div>
  <div class="mt-3 d-flex gap-2">
    <button class="btn btn-brand"><i class="bi bi-check2 me-1"></i>Save</button>
    <a href="<?= url($user ? '/users/' . $user['id'] : '/users') ?>" class="btn btn-light">Cancel</a>
  </div>
</form>
