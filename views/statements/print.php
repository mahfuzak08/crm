<div class="meta-grid">
  <div class="box"><h4>Customer</h4>
    <div class="name"><?= e($customer['name']) ?></div>
    <div><?= e($customer['customer_code']) ?> · <?= e($customer['phone']) ?></div>
    <div style="white-space:pre-line"><?= e($customer['address']) ?></div>
  </div>
  <div class="box"><table class="kv">
    <tr><td>Period</td><td><?= e(fdate($f['from'])) ?> — <?= e(fdate($f['to'])) ?></td></tr>
    <tr><td>Generated</td><td><?= e(fdate(now(), true)) ?></td></tr>
  </table></div>
</div>
<?= partial('statements/_table', compact('opening', 'rows', 'totals', 'company')) ?>
<div class="signs"><div>Prepared by</div><div>Authorized signature</div></div>
<?php if ($company['invoice_footer']): ?><div class="doc-foot"><?= e($company['invoice_footer']) ?></div><?php endif; ?>
