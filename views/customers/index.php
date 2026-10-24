<?php App\Core\View::start('actions'); ?>
<?php if (can('reports.export')): ?><a href="<?= e(url_with(['export' => 'csv'])) ?>" class="btn btn-light"><i class="bi bi-download me-1"></i>CSV</a><?php endif; ?>
<?php if (can('customers.create')): ?><a href="<?= url('/customers/create') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i>New Customer</a><?php endif; ?>
<?php App\Core\View::end(); ?>
<div class="card">
  <form class="filters" method="get" action="<?= url('/customers') ?>" data-live-filter data-target="#list">
    <input type="search" name="q" class="form-control search" placeholder="Search name, phone, email, NID, code…" value="<?= e($f['q']) ?>">
    <select name="type" class="form-select"><option value="">All types</option><?php foreach ($types as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['type']) ?>><?= e($n) ?></option><?php endforeach; ?></select>
    <select name="status" class="form-select"><option value="">All statuses</option><option value="active"<?= selected('active', $f['status']) ?>>Active</option><option value="inactive"<?= selected('inactive', $f['status']) ?>>Inactive</option></select>
    <input type="date" name="from" class="form-control" value="<?= e($f['from']) ?>" title="Created from">
    <input type="date" name="to" class="form-control" value="<?= e($f['to']) ?>" title="Created to">
  </form>
  <div id="list"><?= partial('customers/_list', ['p' => $p]) ?></div>
</div>
