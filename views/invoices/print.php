<?php $m = static fn($v) => money_c($v, $company); ?>
<?php if (in_array($inv['status'], ['paid', 'cancelled', 'draft'], true)): ?><div class="stamp <?= e($inv['status']) ?>"><?= e(strtoupper($inv['status'])) ?></div><?php endif; ?>
<div class="meta-grid">
  <div class="box"><h4>Bill to</h4>
    <div class="name"><?= e($inv['customer_name']) ?></div>
    <div><?= e($inv['customer_code']) ?> · <?= e($inv['customer_phone']) ?></div>
    <?php if ($inv['customer_email']): ?><div><?= e($inv['customer_email']) ?></div><?php endif; ?>
    <div style="white-space:pre-line"><?= e($inv['customer_address']) ?></div>
  </div>
  <div class="box"><table class="kv">
    <tr><td>Invoice no.</td><td><strong><?= e($inv['invoice_number']) ?></strong></td></tr>
    <tr><td>Invoice date</td><td><?= e(fdate($inv['invoice_date'])) ?></td></tr>
    <tr><td>Due date</td><td><?= e(fdate($inv['due_date'])) ?></td></tr>
    <?php if ($inv['project_name']): ?><tr><td>Project</td><td><?= e($inv['project_name']) ?></td></tr><?php endif; ?>
    <?php if ($inv['sale_number']): ?><tr><td>Sale ref.</td><td><?= e($inv['sale_number']) ?></td></tr><?php endif; ?>
  </table></div>
</div>
<table class="items">
  <thead><tr><th>#</th><th>Description</th><th class="r">Qty</th><th class="r">Unit price</th><th class="r">Amount</th></tr></thead>
  <tbody>
  <?php foreach ($inv['items'] as $n => $it): ?>
    <tr><td><?= $n + 1 ?></td><td><?= e($it['description']) ?></td><td class="r"><?= e(rtrim(rtrim((string) $it['quantity'], '0'), '.')) ?></td><td class="r nowrap"><?= $m($it['unit_price']) ?></td><td class="r nowrap"><?= $m($it['amount']) ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table>
<table class="totals">
  <tr><td>Subtotal</td><td><?= $m($inv['subtotal']) ?></td></tr>
  <?php if ((float) $inv['discount'] > 0): ?><tr><td>Discount</td><td>− <?= $m($inv['discount']) ?></td></tr><?php endif; ?>
  <?php if ((float) $inv['tax'] > 0): ?><tr><td>Tax / VAT (<?= e((string) (float) $inv['tax_percent']) ?>%)</td><td><?= $m($inv['tax']) ?></td></tr><?php endif; ?>
  <?php if ((float) $inv['other_charge'] > 0): ?><tr><td>Other charge</td><td><?= $m($inv['other_charge']) ?></td></tr><?php endif; ?>
  <tr class="grand"><td>Grand total</td><td><?= $m($inv['grand_total']) ?></td></tr>
  <tr><td>Paid</td><td><?= $m($inv['paid_amount']) ?></td></tr>
  <tr class="due"><td>Amount due</td><td><?= $m($inv['due_amount']) ?></td></tr>
</table>
<div class="words">In words: <?= e(amount_in_words((string) $inv['grand_total'], $company['currency'] === 'BDT' ? 'Taka' : (string) $company['currency'])) ?></div>
<?php if ($inv['notes']): ?><div class="terms"><h4>Notes</h4><?= e($inv['notes']) ?></div><?php endif; ?>
<?php if ($inv['terms']): ?><div class="terms"><h4>Terms &amp; conditions</h4><?= e($inv['terms']) ?></div><?php endif; ?>
<div class="signs"><div>Customer signature</div><div>Authorized signature</div></div>
<?php if ($company['invoice_footer']): ?><div class="doc-foot"><?= e($company['invoice_footer']) ?></div><?php endif; ?>
