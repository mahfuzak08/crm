<?php App\Core\View::start('actions'); ?>
<?php if (can('employees.create')): ?><a href="<?= url('/employees/create') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i>New Employee</a><?php endif; ?>
<?php App\Core\View::end(); ?>
<div class="card">
  <form class="filters" method="get" action="<?= url('/employees') ?>" data-live-filter data-target="#list">
    <input type="search" name="q" class="form-control search" placeholder="Search name, phone, code…" value="<?= e($f['q']) ?>">
    <select name="department" class="form-select"><option value="">All departments</option><?php foreach ($departments as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['department']) ?>><?= e($n) ?></option><?php endforeach; ?></select>
    <select name="designation" class="form-select"><option value="">All designations</option><?php foreach ($designations as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['designation']) ?>><?= e($n) ?></option><?php endforeach; ?></select>
    <select name="status" class="form-select"><option value="">All statuses</option><?php foreach (['active', 'inactive', 'resigned'] as $s): ?><option value="<?= $s ?>"<?= selected($s, $f['status']) ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select>
  </form>
  <div id="list"><?= partial('employees/_list', ['p' => $p]) ?></div>
</div>
