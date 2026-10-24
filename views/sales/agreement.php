<?php $m = static fn($v) => money_c($v, $company); ?>
<?php if ($s['status'] === 'cancelled'): ?><div class="stamp cancelled">CANCELLED</div><?php endif; ?>
<table class="kv" style="margin-bottom:14px">
  <tr><td>Agreement / Sale no.</td><td><strong><?= e($s['sale_number']) ?></strong></td></tr>
  <tr><td>Date</td><td><?= e(date('d M Y', strtotime($s['sale_date']))) ?></td></tr>
</table>
<p>This Share Purchase Agreement is made between <strong><?= e($company['name']) ?></strong>, <?= e($company['address']) ?> (the "Company"), and:</p>
<div class="meta-grid">
  <div class="box"><h4>Purchaser</h4>
    <div class="name"><?= e($s['customer_name']) ?></div>
    <?php if ($s['father_name']): ?><div>Father: <?= e($s['father_name']) ?></div><?php endif; ?>
    <?php if ($s['nid']): ?><div>NID: <?= e($s['nid']) ?></div><?php endif; ?>
    <div><?= e($s['customer_phone']) ?></div>
    <div style="white-space:pre-line"><?= e($s['customer_address']) ?></div>
  </div>
  <div class="box"><h4>Project</h4>
    <div class="name"><?= e($s['project_name']) ?> (<?= e($s['project_code']) ?>)</div>
    <div><?= e($s['location']) ?></div>
    <?php if ($s['share_unit']): ?><div>Share unit: <?= e($s['share_unit']) ?></div><?php endif; ?>
  </div>
</div>
<table class="items">
  <thead><tr><th>Particulars</th><th class="r">Qty</th><th class="r">Unit price</th><th class="r">Amount</th></tr></thead>
  <tbody>
    <tr><td><?= $s['package_name'] ? e($s['package_name']) . ' package (' . (int) $s['shares_count'] . ' shares each)' : 'Shares' ?> — <?= (int) $s['shares'] ?> shares total</td><td class="r"><?= (int) $s['quantity'] ?></td><td class="r nowrap"><?= $m($s['unit_price']) ?></td><td class="r nowrap"><?= $m($s['subtotal']) ?></td></tr>
  </tbody>
</table>
<table class="totals">
  <?php if ((float) $s['discount'] > 0): ?><tr><td>Discount</td><td>− <?= $m($s['discount']) ?></td></tr><?php endif; ?>
  <?php if ((float) $s['registration_fee'] > 0): ?><tr><td>Registration fee</td><td><?= $m($s['registration_fee']) ?></td></tr><?php endif; ?>
  <?php if ((float) $s['development_fee'] > 0): ?><tr><td>Development fee</td><td><?= $m($s['development_fee']) ?></td></tr><?php endif; ?>
  <?php if ((float) $s['other_fee'] > 0): ?><tr><td>Other fee</td><td><?= $m($s['other_fee']) ?></td></tr><?php endif; ?>
  <tr class="grand"><td>Total consideration</td><td><?= $m($s['grand_total']) ?></td></tr>
  <tr><td>Paid to date</td><td><?= $m($s['paid_amount']) ?></td></tr>
  <tr class="due"><td>Balance</td><td><?= $m($s['due_amount']) ?></td></tr>
</table>
<div class="words">In words: <?= e(amount_in_words((string) $s['grand_total'], $company['currency'] === 'BDT' ? 'Taka' : (string) $company['currency'])) ?></div>
<div class="terms"><h4>Terms &amp; conditions</h4><?= e($terms) ?></div>
<div class="signs"><div>Purchaser<br><?= e($s['customer_name']) ?></div><div>Witness</div><div>For <?= e($company['name']) ?><br>Authorized signature</div></div>
<?php if ($company['invoice_footer']): ?><div class="doc-foot"><?= e($company['invoice_footer']) ?></div><?php endif; ?>
