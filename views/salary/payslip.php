<?php $m = static fn($v) => money_c($v, $company); ?>
<?php if ($p['status'] === 'cancelled'): ?><div class="stamp cancelled">CANCELLED</div><?php endif; ?>
<div class="meta-grid">
  <div class="box"><h4>Employee</h4><div class="name"><?= e($p['employee_name']) ?></div><div><?= e($p['employee_code']) ?></div><div><?= e($p['designation']) ?> · <?= e($p['department']) ?></div></div>
  <div class="box"><table class="kv"><tr><td>Payslip no.</td><td><strong><?= e($p['payment_number']) ?></strong></td></tr><tr><td>Salary month</td><td><?= e(date('F Y', strtotime($p['salary_month'] . '-01'))) ?></td></tr><tr><td>Paid on</td><td><?= e(fdate($p['payment_date'])) ?></td></tr><tr><td>Method</td><td><?= e($p['method_name'] ?? '—') ?></td></tr></table></div>
</div>
<table class="items">
  <thead><tr><th>Earnings</th><th class="r">Amount</th><th>Deductions</th><th class="r">Amount</th></tr></thead>
  <tbody>
  <?php
  $earn = [['Basic salary', $p['basic']]];
  $ded = [];
  foreach ($p['lines'] as $l) { if ($l['type'] === 'deduction') $ded[] = [$l['name'], $l['amount']]; else $earn[] = [$l['name'], $l['amount']]; }
  for ($i = 0, $n = max(count($earn), count($ded)); $i < $n; $i++): ?>
    <tr><td><?= e($earn[$i][0] ?? '') ?></td><td class="r"><?= isset($earn[$i]) ? $m($earn[$i][1]) : '' ?></td><td><?= e($ded[$i][0] ?? '') ?></td><td class="r"><?= isset($ded[$i]) ? $m($ded[$i][1]) : '' ?></td></tr>
  <?php endfor; ?>
  </tbody>
</table>
<table class="totals"><tr><td>Total deductions</td><td><?= $m($p['deductions']) ?></td></tr><tr class="grand"><td>Net pay</td><td><?= $m($p['net_salary']) ?></td></tr></table>
<div class="words">In words: <?= e(amount_in_words((string) $p['net_salary'], $company['currency'] === 'BDT' ? 'Taka' : (string) $company['currency'])) ?></div>
<div class="signs"><div>Employee signature</div><div>Accounts</div><div>Authorized signature</div></div>
