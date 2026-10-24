<div class="card">
  <form class="filters" method="get" action="<?= url('/activities') ?>" data-live-filter data-target="#list">
    <input type="search" name="q" class="form-control search" placeholder="Search description, lead, customer…" value="<?= e($f['q']) ?>">
    <select name="type" class="form-select"><option value="">All types</option><?php foreach ($types as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['type']) ?>><?= e($n) ?></option><?php endforeach; ?></select>
    <select name="user" class="form-select"><option value="">All users</option><?php foreach ($users as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['user']) ?>><?= e($n) ?></option><?php endforeach; ?></select>
    <input type="date" name="from" class="form-control" value="<?= e($f['from']) ?>" title="From">
    <input type="date" name="to" class="form-control" value="<?= e($f['to']) ?>" title="To">
  </form>
  <div id="list"><?= partial('activities/_list', ['p' => $p]) ?></div>
</div>
