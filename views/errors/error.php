<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= (int) $status ?> · Error</title>
<link rel="icon" href="<?= asset('images/favicon.svg') ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= asset('vendor/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="guest">
<div class="guest-wrap text-center">
  <div class="display-4 fw-bold text-muted"><?= (int) $status ?></div>
  <p class="lead my-3"><?= e($message) ?></p>
  <a href="<?= url('/') ?>" class="btn btn-brand">Go to Dashboard</a>
  <?php if (!empty($debugInfo)): ?>
    <pre class="text-start small bg-light border p-3 mt-4" style="white-space:pre-wrap"><?= e($debugInfo) ?></pre>
  <?php endif; ?>
</div>
</body>
</html>
