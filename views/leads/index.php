<?php App\Core\View::start('actions'); ?>
<?php if (can('reports.export')): ?><a href="<?= e(url_with(['export' => 'csv'])) ?>" class="btn btn-light"><i class="bi bi-download me-1"></i>CSV</a><?php endif; ?>
<?php if (can('leads.create')): ?><a href="<?= url('/leads/create') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i>New Lead</a><?php endif; ?>
<?php App\Core\View::end(); ?>
<div class="d-flex flex-wrap gap-2 mb-3">
  <a href="<?= url('/leads') ?>" class="btn btn-sm <?= $f['status'] === '' ? 'btn-brand' : 'btn-light' ?>">All</a>
  <?php foreach ($statuses as $s): if (!$s['is_active']) continue; ?>
    <a href="<?= url('/leads', ['status' => $s['id']]) ?>" class="btn btn-sm <?= (string) $f['status'] === (string) $s['id'] ? 'btn-brand' : 'btn-light' ?>"><span class="d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:<?= e($s['color'] ?: '#6c757d') ?>"></span><?= e($s['name']) ?></a>
  <?php endforeach; ?>
</div>
<div class="card">
  <form class="filters" method="get" action="<?= url('/leads') ?>" data-live-filter data-target="#list">
    <input type="hidden" name="status" value="<?= e($f['status']) ?>">
    <input type="search" name="q" class="form-control search" placeholder="Search name, phone, email, code…" value="<?= e($f['q']) ?>">
    <select name="source" class="form-select"><option value="">All sources</option><?php foreach ($sources as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['source']) ?>><?= e($n) ?></option><?php endforeach; ?></select>
    <select name="project" class="form-select"><option value="">All projects</option><?php foreach ($projects as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['project']) ?>><?= e($n) ?></option><?php endforeach; ?></select>
    <select name="assigned" class="form-select"><option value="">Anyone</option><option value="me"<?= selected('me', $f['assigned']) ?>>Assigned to me</option><option value="none"<?= selected('none', $f['assigned']) ?>>Unassigned</option>
      <?php if (can('leads.view_all')): foreach ($users as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['assigned']) ?>><?= e($n) ?></option><?php endforeach; endif; ?></select>
    <select name="followup" class="form-select"><option value="">Any follow-up</option><option value="today"<?= selected('today', $f['followup']) ?>>Due today</option><option value="overdue"<?= selected('overdue', $f['followup']) ?>>Overdue</option><option value="upcoming"<?= selected('upcoming', $f['followup']) ?>>Upcoming</option></select>
    <input type="date" name="from" class="form-control" value="<?= e($f['from']) ?>" title="Created from">
    <input type="date" name="to" class="form-control" value="<?= e($f['to']) ?>" title="Created to">
  </form>
  <div id="list"><?= partial('leads/_list', ['p' => $p]) ?></div>
</div>
