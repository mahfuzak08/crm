<h1 class="h4 mb-1">Installation</h1>
<p class="text-muted small mb-4">Step <?= (int) $step ?> of 2 — <?= $step === 1 ? 'Database connection' : 'Company & Super Admin' ?></p>

<div class="mb-4">
  <h2 class="h6">Server requirements</h2>
  <div class="row row-cols-1 row-cols-md-2 g-1 small">
    <?php foreach ($requirements as [$label, $ok, $blocking]): ?>
      <div class="col"><i class="bi <?= $ok ? 'bi-check-circle-fill text-success' : ($blocking ? 'bi-x-circle-fill text-danger' : 'bi-exclamation-circle-fill text-warning') ?> me-1"></i><?= e($label) ?></div>
    <?php endforeach; ?>
  </div>
  <?php if (!$allOk): ?><div class="alert alert-warning small mt-2 mb-0">Fix the failed requirements before continuing.</div><?php endif; ?>
</div>

<?php if ($envContent): ?>
  <div class="mb-4">
    <label class="form-label">Create <code>.env</code> in the project root with:</label>
    <textarea class="form-control font-monospace small" rows="12" readonly><?= e($envContent) ?></textarea>
  </div>
<?php endif; ?>

<?php if ($step === 1): ?>
<form method="post" action="<?= url('/install/database') ?>" autocomplete="off">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-8"><label class="form-label">Database host <span class="req">*</span></label>
      <input name="db_host" class="form-control<?= invalid('db_host') ?>" value="<?= e(old('db_host', '127.0.0.1')) ?>" required><?= error_for('db_host') ?></div>
    <div class="col-md-4"><label class="form-label">Port <span class="req">*</span></label>
      <input name="db_port" class="form-control<?= invalid('db_port') ?>" value="<?= e(old('db_port', '3306')) ?>" required><?= error_for('db_port') ?></div>
    <div class="col-md-4"><label class="form-label">Database name <span class="req">*</span></label>
      <input name="db_name" class="form-control<?= invalid('db_name') ?>" value="<?= e(old('db_name', 'lake_view_crm')) ?>" required><?= error_for('db_name') ?></div>
    <div class="col-md-4"><label class="form-label">Username <span class="req">*</span></label>
      <input name="db_user" class="form-control<?= invalid('db_user') ?>" value="<?= e(old('db_user')) ?>" required><?= error_for('db_user') ?></div>
    <div class="col-md-4"><label class="form-label">Password</label>
      <input name="db_pass" type="password" class="form-control" autocomplete="new-password"></div>
    <div class="col-12"><label class="form-label">Application URL <span class="req">*</span></label>
      <input name="app_url" class="form-control<?= invalid('app_url') ?>" value="<?= e(old('app_url', app_url())) ?>" required>
      <div class="form-text">Full URL without trailing slash, e.g. https://crm.example.com</div><?= error_for('app_url') ?></div>
  </div>
  <button class="btn btn-brand mt-4 w-100" <?= $allOk ? '' : 'disabled' ?>>Connect &amp; continue</button>
</form>
<?php else: ?>
<form method="post" action="<?= url('/install/setup') ?>" enctype="multipart/form-data" autocomplete="off">
  <?= csrf_field() ?>
  <h2 class="h6 mt-2">Company</h2>
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label">Company name <span class="req">*</span></label>
      <input name="company_name" class="form-control<?= invalid('name') ?>" value="<?= e(old('company_name', 'Lake View Iconic Tower')) ?>" required><?= error_for('name') ?></div>
    <div class="col-md-6"><label class="form-label">Phone</label>
      <input name="company_phone" class="form-control<?= invalid('phone') ?>" value="<?= e(old('company_phone')) ?>"><?= error_for('phone') ?></div>
    <div class="col-md-6"><label class="form-label">Email</label>
      <input name="company_email" type="email" class="form-control<?= invalid('email') ?>" value="<?= e(old('company_email')) ?>"><?= error_for('email') ?></div>
    <div class="col-md-6"><label class="form-label">Website</label>
      <input name="company_website" class="form-control<?= invalid('website') ?>" value="<?= e(old('company_website')) ?>" placeholder="https://"><?= error_for('website') ?></div>
    <div class="col-md-8"><label class="form-label">Address</label>
      <input name="company_address" class="form-control" value="<?= e(old('company_address')) ?>"></div>
    <div class="col-md-4"><label class="form-label">Logo</label>
      <input name="company_logo" type="file" accept=".jpg,.jpeg,.png,.webp" class="form-control<?= invalid('company_logo') ?>"><?= error_for('company_logo') ?></div>
  </div>

  <h2 class="h6 mt-4">Super Admin</h2>
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label">Full name <span class="req">*</span></label>
      <input name="admin_name" class="form-control<?= invalid('admin_name') ?>" value="<?= e(old('admin_name')) ?>" required><?= error_for('admin_name') ?></div>
    <div class="col-md-6"><label class="form-label">Email <span class="req">*</span></label>
      <input name="admin_email" type="email" class="form-control<?= invalid('admin_email') ?>" value="<?= e(old('admin_email')) ?>" required><?= error_for('admin_email') ?></div>
    <div class="col-md-6"><label class="form-label">Phone</label>
      <input name="admin_phone" class="form-control<?= invalid('admin_phone') ?>" value="<?= e(old('admin_phone')) ?>"><?= error_for('admin_phone') ?></div>
    <div class="col-md-6"><label class="form-label">Username <span class="req">*</span></label>
      <input name="admin_username" class="form-control<?= invalid('admin_username') ?>" value="<?= e(old('admin_username', 'superadmin')) ?>" required><?= error_for('admin_username') ?></div>
    <div class="col-md-6"><label class="form-label">Password <span class="req">*</span></label>
      <input name="admin_password" type="password" class="form-control<?= invalid('admin_password') ?>" autocomplete="new-password" required><?= error_for('admin_password') ?>
      <div class="form-text">Min 8 characters with letters and numbers.</div></div>
    <div class="col-md-6"><label class="form-label">Confirm password <span class="req">*</span></label>
      <input name="admin_password_confirmation" type="password" class="form-control<?= invalid('admin_password_confirmation') ?>" autocomplete="new-password" required><?= error_for('admin_password_confirmation') ?></div>
  </div>
  <button class="btn btn-brand mt-4 w-100">Install</button>
</form>
<?php endif; ?>
