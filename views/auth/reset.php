<h1 class="h5 mb-1">Set a new password</h1>
<p class="text-muted small mb-4">Choose a strong password (min 8 characters, letters and numbers).</p>
<form method="post" action="<?= url('/reset-password/' . $token) ?>">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label" for="password">New password</label>
    <input id="password" name="password" type="password" class="form-control<?= invalid('password') ?>" autocomplete="new-password" required>
    <?= error_for('password') ?>
  </div>
  <div class="mb-3">
    <label class="form-label" for="password_confirmation">Confirm password</label>
    <input id="password_confirmation" name="password_confirmation" type="password" class="form-control<?= invalid('password_confirmation') ?>" autocomplete="new-password" required>
    <?= error_for('password_confirmation') ?>
  </div>
  <button class="btn btn-brand w-100" type="submit">Update password</button>
</form>
