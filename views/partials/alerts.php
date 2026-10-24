<?php
$alert = App\Core\Session::getFlash('_alert');
$errs = errors();
if ($alert): ?>
<div class="alert alert-<?= e($alert['type']) ?> alert-dismissible fade show" role="alert">
  <?= e($alert['message']) ?>
  <?php if ($alert['type'] === 'danger' && array_diff_key($errs, ['_' => 1]) && !in_array($alert['message'], $errs, true)): ?>
    <ul class="mb-0 mt-1 small">
      <?php foreach ($errs as $k => $m): if ($k === '_') continue; ?><li><?= e($m) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif;
foreach (['success', 'warning', 'info'] as $t):
    if ($m = App\Core\Session::getFlash($t)): ?>
<div class="alert alert-<?= $t ?> alert-dismissible fade show" role="alert"><?= e($m) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; endforeach;
