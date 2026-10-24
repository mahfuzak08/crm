<?php App\Core\View::start('actions'); ?>
<?php if (can('users.create')): ?><a href="<?= url('/users/create') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i>New User</a><?php endif; ?>
<?php App\Core\View::end(); ?>
<div class="card">
  <form class="filters" method="get" action="<?= url('/users') ?>" data-live-filter data-target="#list">
    <input type="search" name="q" class="form-control search" placeholder="Search name, email, phone…" value="<?= e($f['q']) ?>">
    <select name="status" class="form-select"><option value="">All statuses</option>
      <option value="active"<?= selected('active', $f['status']) ?>>Active</option><option value="inactive"<?= selected('inactive', $f['status']) ?>>Inactive</option></select>
    <select name="role" class="form-select"><option value="">All roles</option>
      <?php foreach ($roles as $r): ?><option value="<?= (int) $r['id'] ?>"<?= selected($r['id'], $f['role']) ?>><?= e($r['name']) ?></option><?php endforeach; ?></select>
  </form>
  <div id="list"><?= partial('users/_list', get_defined_vars()) ?></div>
</div>
