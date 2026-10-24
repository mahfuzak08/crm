<div class="card" style="max-width:520px">
  <div class="card-body">
    <form method="post" action="<?= url('/profile/password') ?>">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Current password <span class="req">*</span></label>
        <input name="current_password" type="password" class="form-control<?= invalid('current_password') ?>" autocomplete="current-password" required><?= error_for('current_password') ?></div>
      <div class="mb-3"><label class="form-label">New password <span class="req">*</span></label>
        <input name="password" type="password" class="form-control<?= invalid('password') ?>" autocomplete="new-password" required><?= error_for('password') ?>
        <div class="form-text">Min 8 characters with letters and numbers.</div></div>
      <div class="mb-3"><label class="form-label">Confirm new password <span class="req">*</span></label>
        <input name="password_confirmation" type="password" class="form-control<?= invalid('password_confirmation') ?>" autocomplete="new-password" required><?= error_for('password_confirmation') ?></div>
      <button class="btn btn-brand">Update password</button>
    </form>
  </div>
</div>
