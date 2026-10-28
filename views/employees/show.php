<?php App\Core\View::start('actions'); ?>
<?php if ($director && can('directors.view')): ?><a href="<?= url('/directors/' . $director['id']) ?>" class="btn btn-light"><i class="bi bi-person-workspace me-1"></i>Director<?= $director['status'] !== 'active' ? ' (inactive)' : '' ?></a>
<?php elseif (!$director && can('directors.manage')): ?><a href="<?= url('/directors/create', ['employee' => $e['id']]) ?>" class="btn btn-light"><i class="bi bi-person-workspace me-1"></i>Make director</a><?php endif; ?>
<?php if (can('salary.pay') && $e['status'] === 'active'): ?><a href="<?= url('/salary/create', ['employee' => $e['id']]) ?>" class="btn btn-success"><i class="bi bi-cash me-1"></i>Pay salary</a><?php endif; ?>
<?php if (can('employees.edit')): ?><a href="<?= url('/employees/' . $e['id'] . '/edit') ?>" class="btn btn-brand"><i class="bi bi-pencil me-1"></i>Edit</a><?php endif; ?>
<?php if (can('employees.delete')): ?><?= post_button('/employees/' . $e['id'] . '/delete', '', ['html' => '<i class="bi bi-trash"></i>', 'class' => 'btn btn-outline-danger', 'confirm' => 'Delete this employee?']) ?><?php endif; ?>
<?php App\Core\View::end(); ?>
<div class="row g-3">
  <div class="col-lg-5">
    <div class="card"><div class="card-body">
      <div class="d-flex align-items-center gap-3 mb-3">
        <?php if ($e['photo']): ?><img src="<?= url('/employees/' . $e['id'] . '/photo') ?>" alt="" class="rounded-circle border" style="width:64px;height:64px;object-fit:cover">
        <?php else: ?><span class="avatar lg"><?= e(initials($e['name'])) ?></span><?php endif; ?>
        <div><div class="fw-semibold fs-5"><?= e($e['name']) ?></div><div class="text-muted small"><?= e($e['employee_code']) ?> · <?= status_badge($e['status']) ?></div></div>
      </div>
      <dl class="dl-grid">
        <dt>Department</dt><dd><?= e($e['department'] ?? '—') ?></dd>
        <dt>Designation</dt><dd><?= e($e['designation'] ?? '—') ?></dd>
        <dt>Phone</dt><dd><?= e($e['phone'] ?: '—') ?></dd>
        <dt>Email</dt><dd><?= e($e['email'] ?: '—') ?></dd>
        <dt>Joined</dt><dd><?= e(fdate($e['joining_date']) ?: '—') ?></dd>
        <dt>Address</dt><dd><?= e($e['address'] ?: '—') ?></dd>
      </dl>
      <?php if ($e['notes']): ?><hr><div class="small" style="white-space:pre-line"><?= e($e['notes']) ?></div><?php endif; ?>
    </div></div>
  </div>
  <?php if ($salary): ?>
  <div class="col-lg-7">
    <div class="card mb-3"><div class="card-header">Monthly salary structure</div>
      <table class="table mb-0">
        <tr><td>Basic</td><td class="text-end"><?= money($salary['basic']) ?></td></tr>
        <?php foreach ($salary['lines'] as $l): ?><tr><td><?= e($l['name']) ?> <span class="badge text-bg-light text-dark"><?= e($l['type']) ?></span></td><td class="text-end"><?= $l['type'] === 'deduction' ? '−' : '' ?><?= money($l['amount']) ?></td></tr><?php endforeach; ?>
        <tr class="fw-bold"><td>Net salary</td><td class="text-end"><?= money($salary['net_salary']) ?></td></tr>
      </table>
    </div>
    <div class="card"><div class="card-header">Salary history</div><div class="table-responsive"><table class="table">
      <thead><tr><th>Month</th><th>Number</th><th>Paid on</th><th class="text-end">Net</th><th>Status</th></tr></thead><tbody>
      <?php foreach ($payments as $sp): ?><tr><td><?= e($sp['salary_month']) ?></td><td><a href="<?= url('/salary/' . $sp['id']) ?>"><?= e($sp['payment_number']) ?></a></td><td class="small"><?= e(fdate($sp['payment_date'])) ?></td><td class="text-end"><?= money($sp['net_salary']) ?></td><td><?= status_badge($sp['status']) ?></td></tr><?php endforeach; ?>
      <?php if (!$payments) echo empty_row(5, 'No salary paid yet.'); ?>
    </tbody></table></div></div>
  </div>
  <?php endif; ?>
</div>
