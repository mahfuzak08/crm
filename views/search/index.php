<div class="card">
  <div class="card-body border-bottom">
    <form method="get" action="<?= url('/search') ?>"><input type="search" name="q" class="form-control form-control-lg" value="<?= e($q) ?>" placeholder="Search leads, customers, invoices, receipts…" autofocus></form>
  </div>
  <ul class="list-group list-group-flush">
    <?php foreach ($results as $r): ?>
      <li class="list-group-item"><span class="badge text-bg-light text-dark me-2" style="width:80px"><?= e($r['type']) ?></span><a href="<?= e($r['url']) ?>"><?= e($r['text']) ?></a></li>
    <?php endforeach; ?>
    <?php if (!$results): ?><li class="list-group-item text-muted"><?= mb_strlen($q) >= 2 ? 'No results.' : 'Type at least 2 characters.' ?></li><?php endif; ?>
  </ul>
</div>
