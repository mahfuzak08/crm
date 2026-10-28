<?php App\Core\View::start('actions'); ?>
<?php if (can('reports.export')): ?><a href="<?= e(url_with(['export' => 'csv'])) ?>" class="btn btn-light"><i class="bi bi-download me-1"></i>CSV</a><?php endif; ?>
<?php if (can('sales.create')): ?><a href="<?= url('/sales/create') ?>" class="btn btn-brand"><i class="bi bi-cart-plus me-1"></i>New Sale</a><?php endif; ?>
<?php App\Core\View::end(); ?>
<div class="row g-3 mb-3">
  <div class="col-sm-4"><div class="card stat"><span class="stat-icon violet"><i class="bi bi-grid-3x3-gap"></i></span><div><div class="stat-label">Shares sold</div><div class="stat-value"><?= number_format((int) $totals['shares']) ?></div></div></div></div>
  <div class="col-sm-4"><div class="card stat"><span class="stat-icon green"><i class="bi bi-graph-up-arrow"></i></span><div><div class="stat-label">Sales value</div><div class="stat-value"><?= money($totals['total']) ?></div></div></div></div>
  <div class="col-sm-4"><div class="card stat"><span class="stat-icon red"><i class="bi bi-hourglass-split"></i></span><div><div class="stat-label">Outstanding</div><div class="stat-value"><?= money($totals['due']) ?></div></div></div></div>
</div>
<div class="card">
  <form class="filters" method="get" action="<?= url('/sales') ?>" data-live-filter data-target="#list">
    <input type="search" name="q" class="form-control search" placeholder="Search sale no, customer…" value="<?= e($f['q']) ?>">
    <select name="status" class="form-select"><option value="">All statuses</option><?php foreach (['confirmed', 'reserved', 'cancelled'] as $st): ?><option value="<?= $st ?>"<?= selected($st, $f['status']) ?>><?= ucfirst($st) ?></option><?php endforeach; ?></select>
    <select name="project" class="form-select"><option value="">All projects</option><?php foreach ($projects as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['project']) ?>><?= e($n) ?></option><?php endforeach; ?></select>
    <select name="sales_person" class="form-select"><option value="">All sales persons</option><?php foreach ($users as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['sales_person']) ?>><?= e($n) ?></option><?php endforeach; ?></select>
    <?php if ($directors): ?><select name="director" class="form-select"><option value="">All directors</option><?php foreach ($directors as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['director']) ?>>Via <?= e($n) ?></option><?php endforeach; ?></select><?php endif; ?>
    <input type="date" name="from" class="form-control" value="<?= e($f['from']) ?>"><input type="date" name="to" class="form-control" value="<?= e($f['to']) ?>">
  </form>
  <div id="list"><?= partial('sales/_list', ['p' => $p]) ?></div>
</div>
