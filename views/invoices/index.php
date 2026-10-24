<?php App\Core\View::start('actions'); ?>
<?php if (can('reports.export')): ?><a href="<?= e(url_with(['export' => 'csv'])) ?>" class="btn btn-light"><i class="bi bi-download me-1"></i>CSV</a><?php endif; ?>
<?php if (can('invoices.create')): ?><a href="<?= url('/invoices/create') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i>New Invoice</a><?php endif; ?>
<?php App\Core\View::end(); ?>
<div class="row g-3 mb-3">
  <div class="col-sm-4"><div class="card stat"><span class="stat-icon blue"><i class="bi bi-receipt"></i></span><div><div class="stat-label">Invoiced</div><div class="stat-value"><?= money($totals['total']) ?></div></div></div></div>
  <div class="col-sm-4"><div class="card stat"><span class="stat-icon green"><i class="bi bi-cash-coin"></i></span><div><div class="stat-label">Collected</div><div class="stat-value"><?= money($totals['paid']) ?></div></div></div></div>
  <div class="col-sm-4"><div class="card stat"><span class="stat-icon red"><i class="bi bi-exclamation-circle"></i></span><div><div class="stat-label">Due</div><div class="stat-value"><?= money($totals['due']) ?></div></div></div></div>
</div>
<div class="card">
  <form class="filters" method="get" action="<?= url('/invoices') ?>" data-live-filter data-target="#list">
    <input type="search" name="q" class="form-control search" placeholder="Search invoice no, customer…" value="<?= e($f['q']) ?>">
    <select name="status" class="form-select"><option value="">All statuses</option><?php foreach (App\Services\InvoiceService::STATUSES as $st): ?><option value="<?= $st ?>"<?= selected($st, $f['status']) ?>><?= ucfirst($st) ?></option><?php endforeach; ?></select>
    <select name="project" class="form-select"><option value="">All projects</option><?php foreach ($projects as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['project']) ?>><?= e($n) ?></option><?php endforeach; ?></select>
    <input type="date" name="from" class="form-control" value="<?= e($f['from']) ?>"><input type="date" name="to" class="form-control" value="<?= e($f['to']) ?>">
  </form>
  <div id="list"><?= partial('invoices/_list', ['p' => $p]) ?></div>
</div>
