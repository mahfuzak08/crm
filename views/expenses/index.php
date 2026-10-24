<?php App\Core\View::start('actions'); ?>
<?php if (can('reports.export')): ?><a href="<?= e(url_with(['export' => 'csv'])) ?>" class="btn btn-light"><i class="bi bi-download me-1"></i>CSV</a><?php endif; ?>
<?php if (can('expenses.create')): ?><a href="<?= url('/expenses/create') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i>New Expense</a><?php endif; ?>
<?php App\Core\View::end(); ?>
<div class="card">
  <form class="filters" method="get" action="<?= url('/expenses') ?>" data-live-filter data-target="#list">
    <input type="search" name="q" class="form-control search" placeholder="Search number, description, reference…" value="<?= e($f['q']) ?>">
    <select name="category" class="form-select"><option value="">All categories</option><?php foreach ($categories as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['category']) ?>><?= e($n) ?></option><?php endforeach; ?></select>
    <select name="status" class="form-select"><option value="">All statuses</option><?php foreach (App\Services\ExpenseService::STATUSES as $s): ?><option value="<?= $s ?>"<?= selected($s, $f['status']) ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select>
    <select name="project" class="form-select"><option value="">All projects</option><?php foreach ($projects as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['project']) ?>><?= e($n) ?></option><?php endforeach; ?></select>
    <input type="date" name="from" class="form-control" value="<?= e($f['from']) ?>"><input type="date" name="to" class="form-control" value="<?= e($f['to']) ?>">
  </form>
  <div id="list"><?= partial('expenses/_list', ['p' => $p, 'total' => $total]) ?></div>
</div>
