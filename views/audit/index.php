<?php App\Core\View::start('actions'); ?>
<a href="<?= e(url_with(['export' => 'csv'])) ?>" class="btn btn-light"><i class="bi bi-download me-1"></i>CSV</a>
<?php App\Core\View::end(); ?>
<div class="card">
  <form class="filters" method="get" action="<?= url('/audit-logs') ?>" data-live-filter data-target="#list">
    <input type="search" name="q" class="form-control search" placeholder="Search…" value="<?= e($f['q']) ?>">
    <select name="user" class="form-select"><option value="">All users</option><?php foreach ($users as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['user']) ?>><?= e($n) ?></option><?php endforeach; ?></select>
    <select name="module" class="form-select"><option value="">All modules</option><?php foreach ($modules as $m): ?><option<?= selected($m, $f['module']) ?>><?= e($m) ?></option><?php endforeach; ?></select>
    <select name="action" class="form-select"><option value="">All actions</option><?php foreach ($actions as $a): ?><option<?= selected($a, $f['action']) ?>><?= e($a) ?></option><?php endforeach; ?></select>
    <input type="date" name="from" class="form-control" value="<?= e($f['from']) ?>" title="From">
    <input type="date" name="to" class="form-control" value="<?= e($f['to']) ?>" title="To">
  </form>
  <div id="list"><?= partial('audit/_list', ['p' => $p]) ?></div>
</div>
