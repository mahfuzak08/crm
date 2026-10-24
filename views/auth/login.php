<h1 class="h5 mb-1">Welcome back</h1>
<p class="text-muted small mb-4">Sign in to continue</p>
<form method="post" action="<?= url('/login') ?>" autocomplete="on">
  <?= csrf_field() ?>
  <div class="mb-3">
    <label class="form-label" for="identifier">Email, username or phone</label>
    <input id="identifier" name="identifier" class="form-control form-control-lg<?= invalid('identifier') ?>" value="<?= e(old('identifier')) ?>" autocomplete="username" required autofocus>
    <?= error_for('identifier') ?>
  </div>
  <div class="mb-3">
    <div class="d-flex justify-content-between">
      <label class="form-label" for="password">Password</label>
      <a href="<?= url('/forgot-password') ?>" class="small">Forgot password?</a>
    </div>
    <input id="password" name="password" type="password" class="form-control form-control-lg<?= invalid('password') ?>" autocomplete="current-password" required>
    <?= error_for('password') ?>
  </div>
  <button class="btn btn-brand btn-lg w-100" type="submit">Sign in</button>
</form>
