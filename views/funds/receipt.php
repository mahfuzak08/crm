<?php $m = static fn($v) => money_c($v, $company); ?>
<?php if ($x['status'] === 'cancelled'): ?><div class="stamp cancelled">CANCELLED</div><?php endif; ?>
<div class="meta-grid">
  <div class="box"><h4>Received from</h4>
    <div class="name"><?= e($x['contributor_name']) ?></div>
    <?php if ($x['contributor_phone']): ?><div><?= e($x['contributor_phone']) ?></div><?php endif; ?>
  </div>
  <div class="box"><table class="kv">
    <tr><td>Receipt no.</td><td><strong><?= e($x['fund_number']) ?></strong></td></tr>
    <tr><td>Date</td><td><?= e(fdate($x['contribution_date'])) ?></td></tr>
    <tr><td>Fund type</td><td><?= e(App\Services\FundService::TYPES[$x['fund_type']]) ?></td></tr>
    <?php if ($x['project_name']): ?><tr><td>Project</td><td><?= e($x['project_name']) ?></td></tr><?php endif; ?>
  </table></div>
</div>
<table class="items">
  <thead><tr><th>Purpose</th><th>Payment method</th><th>Reference</th><th class="r">Amount</th></tr></thead>
  <tbody><tr><td><?= e($x['purpose'] ?: 'Fund contribution') ?></td><td><?= e($x['method_name'] ?? '—') ?></td><td><?= e($x['reference'] ?: '—') ?></td><td class="r receipt-amount nowrap"><?= $m($x['amount']) ?></td></tr></tbody>
</table>
<div class="words">Received with thanks the sum of <?= e(amount_in_words((string) $x['amount'], $company['currency'] === 'BDT' ? 'Taka' : (string) $company['currency'])) ?>.</div>
<?php if ($x['notes']): ?><div class="terms"><h4>Notes</h4><?= e($x['notes']) ?></div><?php endif; ?>
<div class="signs"><div>Received by<br><?= e($x['created_by_name']) ?></div><div>Contributor</div><div>Authorized signature</div></div>
