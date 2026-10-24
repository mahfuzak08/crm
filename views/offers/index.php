<?php App\Core\View::start('actions'); ?>
<?php if (can('offers.create')): ?><a href="<?= url('/offers/create') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i>New Offer</a><?php endif; ?>
<?php App\Core\View::end(); ?>
<div class="card">
  <form class="filters" method="get" action="<?= url('/offers') ?>" data-live-filter data-target="#list">
    <input type="search" name="q" class="form-control search" placeholder="Search number, name, phone…" value="<?= e($f['q']) ?>">
    <select name="status" class="form-select"><option value="">All statuses</option><?php foreach (App\Services\OfferService::STATUSES as $s): ?><option value="<?= $s ?>"<?= selected($s, $f['status']) ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select>
    <select name="project" class="form-select"><option value="">All projects</option><?php foreach ($projects as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['project']) ?>><?= e($n) ?></option><?php endforeach; ?></select>
    <input type="date" name="from" class="form-control" value="<?= e($f['from']) ?>"><input type="date" name="to" class="form-control" value="<?= e($f['to']) ?>">
  </form>
  <div id="list"><?= partial('offers/_list', ['p' => $p]) ?></div>
</div>
