<?php App\Core\View::start('actions'); ?>
<?php if (can('salary.view')): ?><a href="<?= url('/salary/components') ?>" class="btn btn-light"><i class="bi bi-sliders me-1"></i>Components</a><?php endif; ?>
<?php if (can('salary.pay')): ?><a href="<?= url('/salary/create') ?>" class="btn btn-brand"><i class="bi bi-cash me-1"></i>Pay salary</a><?php endif; ?>
<?php App\Core\View::end(); ?>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card">
      <form class="filters" method="get" action="<?= url('/salary') ?>" data-live-filter data-target="#list">
        <input type="search" name="q" class="form-control search" placeholder="Search employee, number…" value="<?= e($f['q']) ?>">
        <input type="month" name="month" class="form-control" value="<?= e($f['month']) ?>">
        <select name="employee" class="form-select"><option value="">All employees</option><?php foreach ($employees as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['employee']) ?>><?= e($n) ?></option><?php endforeach; ?></select>
        <select name="status" class="form-select"><option value="">All</option><option value="paid"<?= selected('paid', $f['status']) ?>>Paid</option><option value="cancelled"<?= selected('cancelled', $f['status']) ?>>Cancelled</option></select>
      </form>
      <div id="list"><?= partial('salary/_list', ['p' => $p, 'total' => $total]) ?></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card"><div class="card-header">Unpaid for <?= e(date('F Y', strtotime($month . '-01'))) ?> <span class="badge text-bg-warning"><?= count($unpaid) ?></span></div>
      <ul class="list-group list-group-flush">
        <?php foreach ($unpaid as $u): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center small">
            <span><?= e($u['name']) ?> <span class="text-muted"><?= e($u['employee_code']) ?></span></span>
            <?php if (can('salary.pay')): ?><a class="btn btn-sm btn-outline-success" href="<?= url('/salary/create', ['employee' => $u['id'], 'month' => $month]) ?>">Pay</a><?php endif; ?>
          </li>
        <?php endforeach; ?>
        <?php if (!$unpaid): ?><li class="list-group-item small text-muted">Everyone is paid for this month.</li><?php endif; ?>
      </ul>
    </div>
  </div>
</div>
