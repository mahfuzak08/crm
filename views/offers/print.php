<?php
/** Vars: $o, $company, optional $public. Rendered inside layouts/print (or standalone for public link). */
$m = static fn($v) => money_c($v, $company);
$standalone = !empty($public);
if ($standalone) {
    // Public view uses the print layout directly.
    ob_start();
}
?>
<div class="meta-grid">
  <div class="box"><h4>Offer to</h4>
    <div class="name"><?= e($o['to_name']) ?></div>
    <div><?= e($o['to_phone']) ?><?= $o['to_email'] ? ' · ' . e($o['to_email']) : '' ?></div>
    <div style="white-space:pre-line"><?= e($o['to_address']) ?></div>
  </div>
  <div class="box"><table class="kv">
    <tr><td>Offer no.</td><td><strong><?= e($o['offer_number']) ?></strong></td></tr>
    <tr><td>Date</td><td><?= e(date('d M Y', strtotime($o['created_at']))) ?></td></tr>
    <tr><td>Valid until</td><td><?= e($o['valid_until'] ? date('d M Y', strtotime($o['valid_until'])) : '—') ?></td></tr>
    <tr><td>Project</td><td><?= e($o['project_name']) ?> (<?= e($o['project_code']) ?>)</td></tr>
    <?php if ($o['location']): ?><tr><td>Location</td><td><?= e($o['location']) ?></td></tr><?php endif; ?>
  </table></div>
</div>
<table class="items">
  <thead><tr><th>#</th><th>Description</th><th class="r">Qty</th><th class="r">Unit price</th><th class="r">Amount</th></tr></thead>
  <tbody>
    <tr><td>1</td><td><strong><?= e($o['project_name']) ?></strong> — <?= $o['package_name'] ? e($o['package_name']) . ' package (' . (int) $o['shares_count'] . ' shares each)' : 'Shares' ?>
      <br><small><?= (int) $o['shares'] ?> shares total<?= $o['share_unit'] ? ' · ' . e($o['share_unit']) : '' ?></small></td>
      <td class="r"><?= (int) $o['quantity'] ?></td><td class="r nowrap"><?= $m($o['unit_price']) ?></td><td class="r nowrap"><?= $m($o['subtotal']) ?></td></tr>
  </tbody>
</table>
<table class="totals">
  <tr><td>Subtotal</td><td><?= $m($o['subtotal']) ?></td></tr>
  <?php if ((float) $o['discount'] > 0): ?><tr><td>Discount</td><td>− <?= $m($o['discount']) ?></td></tr><?php endif; ?>
  <?php if ((float) $o['registration_fee'] > 0): ?><tr><td>Registration fee</td><td><?= $m($o['registration_fee']) ?></td></tr><?php endif; ?>
  <?php if ((float) $o['development_fee'] > 0): ?><tr><td>Development fee</td><td><?= $m($o['development_fee']) ?></td></tr><?php endif; ?>
  <?php if ((float) $o['other_fee'] > 0): ?><tr><td>Other fee</td><td><?= $m($o['other_fee']) ?></td></tr><?php endif; ?>
  <tr class="grand"><td>Total</td><td><?= $m($o['total']) ?></td></tr>
</table>
<div class="words">In words: <?= e(amount_in_words((string) $o['total'], $company['currency'] === 'BDT' ? 'Taka' : (string) $company['currency'])) ?></div>
<?php if ($o['notes'] && !$standalone): ?><div class="terms"><h4>Notes</h4><?= e($o['notes']) ?></div><?php endif; ?>
<?php if ($o['terms']): ?><div class="terms"><h4>Terms &amp; conditions</h4><?= e($o['terms']) ?></div><?php endif; ?>
<div class="signs"><div>Prepared by<br><?= e($o['created_by_name']) ?></div><div>Authorized signature</div></div>
<?php if ($company['invoice_footer']): ?><div class="doc-foot"><?= e($company['invoice_footer']) ?></div><?php endif; ?>
<?php
if ($standalone) {
    $content = ob_get_clean();
    echo App\Core\View::partial('layouts/print', ['content' => $content, 'company' => $company, 'title' => $title, 'docTitle' => 'Offer', 'public' => true]);
}
