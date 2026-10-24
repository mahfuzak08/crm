<h1 class="h5 mb-1">Forgot password</h1>
<p class="text-muted small mb-4">Enter your account email. If it exists, we will send a reset link.</p>
<?php if (!$mailEnabled): ?>
  <div class="alert alert-info small">Email delivery is not configured. Please ask your administrator to reset your password.</div>
<?php endif; ?>
<form method="post" action="<?= url('/forgot-password') ?>">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label" for="email">Email</label>
    <input id="email" name="email" type="email" class="form-control<?= invalid('email') ?>" value="<?= e(old('email')) ?>" required autofocus>
    <?= error_for('email') ?>
  </div>
  <button class="btn btn-brand w-100" type="submit"<?= $mailEnabled ? '' : ' disabled' ?>>Send reset link</button>
</form>
<p class="text-center small mt-3 mb-0"><a href="<?= url('/login') ?>">&larr; Back to sign in</a></p>
