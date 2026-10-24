<?php if ($customer): App\Core\View::start('actions'); ?>
<a href="<?= e(url_with(['print' => 1])) ?>" target="_blank" class="btn btn-brand"><i class="bi bi-printer me-1"></i>Print / PDF</a>
<?php App\Core\View::end(); endif; ?>
<div class="card mb-3">
  <form class="filters" method="get" action="<?= url('/statements') ?>">
    <select name="customer" class="form-select" required style="max-width:none;flex:2 1 260px"><option value="">— select customer —</option>
      <?php foreach ($customers as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['customer']) ?>><?= e($n) ?></option><?php endforeach; ?></select>
    <select name="project" class="form-select"><option value="">All projects</option><?php foreach ($projects as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['project']) ?>><?= e($n) ?></option><?php endforeach; ?></select>
    <input type="date" name="from" class="form-control" value="<?= e($f['from']) ?>"><input type="date" name="to" class="form-control" value="<?= e($f['to']) ?>">
    <button class="btn btn-brand">Show</button>
  </form>
</div>
<?php if ($customer): ?>
<div class="card">
  <div class="card-header"><span><?= e($customer['name']) ?> <span class="text-muted fw-normal small"><?= e($customer['customer_code']) ?></span></span><span class="small text-muted fw-normal"><?= e(fdate($f['from'])) ?> — <?= e(fdate($f['to'])) ?></span></div>
  <?= partial('statements/_table', compact('opening', 'rows', 'totals')) ?>
</div>
<?php else: ?>
<div class="card"><div class="empty"><i class="bi bi-journal-text"></i>Select a customer to view the statement.</div></div>
<?php endif; ?>
