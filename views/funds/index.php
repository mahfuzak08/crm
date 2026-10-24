<?php App\Core\View::start('actions'); ?>
<?php if (can('reports.export')): ?><a href="<?= e(url_with(['export' => 'csv'])) ?>" class="btn btn-light"><i class="bi bi-download me-1"></i>CSV</a><?php endif; ?>
<?php if (can('funds.create')): ?><a href="<?= url('/funds/create') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i>New Contribution</a><?php endif; ?>
<?php App\Core\View::end(); ?>
<div class="card">
  <form class="filters" method="get" action="<?= url('/funds') ?>" data-live-filter data-target="#list">
    <input type="search" name="q" class="form-control search" placeholder="Search number, contributor, purpose…" value="<?= e($f['q']) ?>">
    <select name="contributor" class="form-select"><option value="">All directors / users</option><?php foreach ($contributors as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['contributor']) ?>><?= e($n) ?></option><?php endforeach; ?></select>
    <select name="type" class="form-select"><option value="">All types</option><?php foreach (App\Services\FundService::TYPES as $k => $l): ?><option value="<?= $k ?>"<?= selected($k, $f['type']) ?>><?= e($l) ?></option><?php endforeach; ?></select>
    <select name="status" class="form-select"><option value="">All statuses</option><?php foreach (App\Services\FundService::STATUSES as $s): ?><option value="<?= $s ?>"<?= selected($s, $f['status']) ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select>
    <select name="project" class="form-select"><option value="">All projects</option><?php foreach ($projects as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['project']) ?>><?= e($n) ?></option><?php endforeach; ?></select>
    <input type="date" name="from" class="form-control" value="<?= e($f['from']) ?>"><input type="date" name="to" class="form-control" value="<?= e($f['to']) ?>">
  </form>
  <div id="list"><?= partial('funds/_list', ['p' => $p, 'total' => $total, 'byContributor' => $byContributor]) ?></div>
</div>
