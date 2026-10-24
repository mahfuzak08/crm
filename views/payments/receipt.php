<?php $m = static fn($v) => money_c($v, $company); ?>
<?php if ($p['status'] === 'cancelled'): ?><div class="stamp cancelled">CANCELLED</div><?php endif; ?>
<div class="meta-grid">
  <div class="box"><h4>Received from</h4>
    <div class="name"><?= e($p['customer_name']) ?></div>
    <div><?= e($p['customer_code']) ?> · <?= e($p['customer_phone']) ?></div>
    <div style="white-space:pre-line"><?= e($p['customer_address']) ?></div>
  </div>
  <div class="box"><table class="kv">
    <tr><td>Receipt no.</td><td><strong><?= e($p['payment_number']) ?></strong></td></tr>
    <tr><td>Payment date</td><td><?= e(fdate($p['payment_date'])) ?></td></tr>
    <tr><td>Invoice</td><td><?= e($p['invoice_number']) ?></td></tr>
    <?php if ($p['project_name']): ?><tr><td>Project</td><td><?= e($p['project_name']) ?></td></tr><?php endif; ?>
  </table></div>
</div>
<table class="items">
  <thead><tr><th>Payment method</th><th>Reference</th><th class="r">Amount</th></tr></thead>
  <tbody><tr><td><?= e($p['method_name']) ?></td><td><?= e($p['reference'] ?: '—') ?></td><td class="r receipt-amount nowrap"><?= $m($p['amount']) ?></td></tr></tbody>
</table>
<div class="words">Received with thanks the sum of <?= e(amount_in_words((string) $p['amount'], $company['currency'] === 'BDT' ? 'Taka' : (string) $company['currency'])) ?>.</div>
<table class="totals">
  <tr><td>Invoice total</td><td><?= $m($p['invoice_total']) ?></td></tr>
  <tr><td>Total paid to date</td><td><?= $m($p['invoice_paid']) ?></td></tr>
  <tr class="due"><td>Balance due</td><td><?= $m($p['invoice_due']) ?></td></tr>
</table>
<?php if ($p['notes']): ?><div class="terms"><h4>Notes</h4><?= e($p['notes']) ?></div><?php endif; ?>
<div class="signs"><div>Received by<br><?= e($p['received_by_name']) ?></div><div>Authorized signature</div></div>
<?php if ($company['receipt_footer']): ?><div class="doc-foot"><?= e($company['receipt_footer']) ?></div><?php endif; ?>
