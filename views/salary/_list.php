<div class="px-3 py-2 small border-bottom">Total paid (filtered): <strong><?= money($total) ?></strong></div>
<div class="table-responsive"><table class="table table-hover">
  <thead><tr><th>Number</th><th>Month</th><th>Employee</th><th class="text-end">Gross</th><th class="text-end">Deductions</th><th class="text-end">Net</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($p['rows'] as $r):
      $gross = App\Core\Money::fromCents(App\Core\Money::toCents($r['basic']) + App\Core\Money::toCents($r['allowances']) + App\Core\Money::toCents($r['bonus']) + App\Core\Money::toCents($r['commission'])); ?>
    <tr>
      <td class="small text-nowrap"><a href="<?= url('/salary/' . $r['id']) ?>"><?= e($r['payment_number']) ?></a></td>
      <td><?= e($r['salary_month']) ?></td>
      <td><?= e($r['employee_name']) ?><div class="small text-muted"><?= e($r['department']) ?></div></td>
      <td class="text-end text-nowrap"><?= money($gross) ?></td>
      <td class="text-end text-nowrap text-danger"><?= money($r['deductions']) ?></td>
      <td class="text-end text-nowrap fw-semibold"><?= money($r['net_salary']) ?></td>
      <td><?= status_badge($r['status']) ?></td>
      <td class="actions"><a href="<?= url('/salary/' . $r['id'] . '/payslip') ?>" target="_blank" class="btn btn-sm btn-light"><i class="bi bi-printer"></i></a></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$p['rows']) echo empty_row(8, 'No salary payments.'); ?>
  </tbody>
</table></div>
<?= partial('partials/pagination', ['p' => $p]) ?>
