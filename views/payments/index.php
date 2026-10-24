<?php App\Core\View::start('actions'); ?>
<?php if (can('reports.export')): ?><a href="<?= e(url_with(['export' => 'csv'])) ?>" class="btn btn-light"><i class="bi bi-download me-1"></i>CSV</a><?php endif; ?>
<?php if (can('payments.create')): ?><a href="<?= url('/payments/create') ?>" class="btn btn-brand"><i class="bi bi-cash me-1"></i>Receive Payment</a><?php endif; ?>
<?php App\Core\View::end(); ?>
<div class="card">
  <form class="filters" method="get" action="<?= url('/payments') ?>" data-live-filter data-target="#list">
    <input type="hidden" name="view" value="<?= e($f['view']) ?>">
    <input type="search" name="q" class="form-control search" placeholder="Search receipt, reference, customer, invoice…" value="<?= e($f['q']) ?>">
    <select name="method" class="form-select"><option value="">All methods</option><?php foreach ($methods as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['method']) ?>><?= e($n) ?></option><?php endforeach; ?></select>
    <select name="status" class="form-select"><option value="">All statuses</option><option value="confirmed"<?= selected('confirmed', $f['status']) ?>>Confirmed</option><option value="cancelled"<?= selected('cancelled', $f['status']) ?>>Cancelled</option></select>
    <select name="project" class="form-select"><option value="">All projects</option><?php foreach ($projects as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['project']) ?>><?= e($n) ?></option><?php endforeach; ?></select>
    <input type="date" name="from" class="form-control" value="<?= e($f['from']) ?>"><input type="date" name="to" class="form-control" value="<?= e($f['to']) ?>">
  </form>
  <div id="list"><?= partial('payments/_list', ['p' => $p, 'total' => $total]) ?></div>
</div>
