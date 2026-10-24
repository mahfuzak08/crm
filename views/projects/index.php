<?php App\Core\View::start('actions'); ?>
<?php if (can('projects.create')): ?><a href="<?= url('/projects/create') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i>New Project</a><?php endif; ?>
<?php App\Core\View::end(); ?>
<div class="card">
  <form class="filters" method="get" action="<?= url('/projects') ?>" data-live-filter data-target="#list">
    <input type="search" name="q" class="form-control search" placeholder="Search name, code, location…" value="<?= e($f['q']) ?>">
    <select name="status" class="form-select"><option value="">All statuses</option>
      <?php foreach ($statuses as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['status']) ?>><?= e($n) ?></option><?php endforeach; ?></select>
  </form>
  <div id="list"><?= partial('projects/_list', ['p' => $p]) ?></div>
</div>
