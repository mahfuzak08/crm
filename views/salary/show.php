<?php App\Core\View::start('actions'); ?>
<a href="<?= url('/salary/' . $p['id'] . '/payslip') ?>" target="_blank" class="btn btn-brand"><i class="bi bi-printer me-1"></i>Payslip</a>
<?php if ($p['status'] === 'paid' && can('salary.pay')): ?><?= post_button('/salary/' . $p['id'] . '/cancel', '', ['html' => 'Cancel payment', 'class' => 'btn btn-outline-danger', 'confirm' => 'Cancel this salary payment?']) ?><?php endif; ?>
<?php App\Core\View::end(); ?>
<div class="card" style="max-width:760px"><div class="card-body">
  <div class="d-flex justify-content-between mb-3"><div><div class="fw-semibold fs-5"><?= e($p['employee_name']) ?></div><div class="small text-muted"><?= e($p['employee_code']) ?> · <?= e($p['designation']) ?> · <?= e($p['department']) ?></div></div><?= status_badge($p['status']) ?></div>
  <table class="table">
    <tr><td>Month</td><td class="text-end"><?= e(date('F Y', strtotime($p['salary_month'] . '-01'))) ?></td></tr>
    <tr><td>Basic</td><td class="text-end"><?= money($p['basic']) ?></td></tr>
    <?php foreach ($p['lines'] as $l): ?><tr><td><?= e($l['name']) ?> <span class="badge text-bg-light text-dark"><?= e($l['type']) ?></span></td><td class="text-end"><?= $l['type'] === 'deduction' ? '−' : '' ?><?= money($l['amount']) ?></td></tr><?php endforeach; ?>
    <tr class="fw-bold"><td>Net salary</td><td class="text-end fs-5"><?= money($p['net_salary']) ?></td></tr>
  </table>
  <dl class="dl-grid"><dt>Paid on</dt><dd><?= e(fdate($p['payment_date'])) ?></dd><dt>Method</dt><dd><?= e($p['method_name'] ?? '—') ?></dd><dt>Reference</dt><dd><?= e($p['reference'] ?: '—') ?></dd><dt>By</dt><dd><?= e($p['created_by_name']) ?></dd></dl>
</div></div>
