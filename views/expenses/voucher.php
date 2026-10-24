<?php $m = static fn($v) => money_c($v, $company); ?>
<?php if ($x['status'] !== 'approved'): ?><div class="stamp <?= $x['status'] === 'cancelled' ? 'cancelled' : 'draft' ?>"><?= e(strtoupper($x['status'])) ?></div><?php endif; ?>
<div class="meta-grid">
  <div class="box"><table class="kv"><tr><td>Voucher no.</td><td><strong><?= e($x['expense_number']) ?></strong></td></tr><tr><td>Date</td><td><?= e(fdate($x['expense_date'])) ?></td></tr><tr><td>Category</td><td><?= e($x['category_name']) ?></td></tr></table></div>
  <div class="box"><table class="kv"><tr><td>Payment method</td><td><?= e($x['method_name'] ?? '—') ?></td></tr><tr><td>Reference</td><td><?= e($x['reference'] ?: '—') ?></td></tr><?php if ($x['project_name']): ?><tr><td>Project</td><td><?= e($x['project_name']) ?></td></tr><?php endif; ?></table></div>
</div>
<table class="items"><thead><tr><th>Particulars</th><th class="r">Amount</th></tr></thead>
  <tbody><tr><td style="white-space:pre-line"><?= e($x['description'] ?: $x['category_name']) ?></td><td class="r nowrap receipt-amount"><?= $m($x['amount']) ?></td></tr></tbody></table>
<div class="words">In words: <?= e(amount_in_words((string) $x['amount'], $company['currency'] === 'BDT' ? 'Taka' : (string) $company['currency'])) ?></div>
<div class="signs"><div>Prepared by<br><?= e($x['created_by_name']) ?></div><div>Received by</div><div>Approved by<br><?= e($x['approved_by_name'] ?? '') ?></div></div>
