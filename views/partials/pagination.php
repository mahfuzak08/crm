<?php
/** @var array $p paginate() result */
if (($p['total'] ?? 0) === 0) return;
$page = (int) $p['page'];
$pages = (int) $p['pages'];
$from = ($page - 1) * $p['per_page'] + 1;
$to = min($p['total'], $page * $p['per_page']);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 py-2 border-top small">
  <span class="text-muted">Showing <?= $from ?>–<?= $to ?> of <?= number_format($p['total']) ?></span>
  <?php if ($pages > 1): ?>
  <nav><ul class="pagination pagination-sm mb-0">
    <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>"><a class="page-link" href="<?= e(url_with(['page' => $page - 1])) ?>">&lsaquo;</a></li>
    <?php
    $window = array_unique(array_filter([1, $page - 2, $page - 1, $page, $page + 1, $page + 2, $pages], static fn($n) => $n >= 1 && $n <= $pages));
    sort($window);
    $prev = 0;
    foreach ($window as $n):
        if ($n - $prev > 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
      <li class="page-item<?= $n === $page ? ' active' : '' ?>"><a class="page-link" href="<?= e(url_with(['page' => $n])) ?>"><?= $n ?></a></li>
    <?php $prev = $n; endforeach; ?>
    <li class="page-item<?= $page >= $pages ? ' disabled' : '' ?>"><a class="page-link" href="<?= e(url_with(['page' => $page + 1])) ?>">&rsaquo;</a></li>
  </ul></nav>
  <?php endif; ?>
</div>
