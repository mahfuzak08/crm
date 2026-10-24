<?php
$open = in_array($o['status'], ['draft', 'sent', 'viewed'], true);
$to = $o['customer_name'] ?? $o['lead_name'];
App\Core\View::start('actions'); ?>
<a href="<?= url('/offers/' . $o['id'] . '/print') ?>" class="btn btn-light" target="_blank"><i class="bi bi-printer me-1"></i>Print / PDF</a>
<?php if ($open && can('offers.send')): ?><?= post_button('/offers/' . $o['id'] . '/send', '', ['html' => '<i class="bi bi-send me-1"></i>' . ($canEmail ? 'Send by email' : 'Mark as sent'), 'class' => 'btn btn-outline-brand']) ?><?php endif; ?>
<?php if (in_array($o['status'], ['sent', 'viewed', 'accepted'], true) && can('sales.create')): ?><?= post_button('/offers/' . $o['id'] . '/convert', '', ['html' => '<i class="bi bi-cart-check me-1"></i>Convert to sale', 'class' => 'btn btn-success']) ?><?php endif; ?>
<?php if ($open && can('offers.edit')): ?><a href="<?= url('/offers/' . $o['id'] . '/edit') ?>" class="btn btn-brand"><i class="bi bi-pencil me-1"></i>Edit</a><?php endif; ?>
<?php App\Core\View::end(); ?>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card"><div class="card-body">
      <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
        <div><div class="text-muted small">Offer for</div><div class="fw-semibold fs-5"><?= e($to) ?></div>
          <div class="small text-muted"><?= $o['customer_id'] ? '<a href="' . url('/customers/' . $o['customer_id']) . '">' . e($o['customer_code']) . '</a> · customer' : '<a href="' . url('/leads/' . $o['lead_id']) . '">' . e($o['lead_code']) . '</a> · lead' ?></div></div>
        <div class="text-end"><?= status_badge($o['status']) ?><div class="small text-muted mt-1">Valid until <?= e(fdate($o['valid_until'])) ?></div></div>
      </div>
      <div class="table-responsive"><table class="table">
        <thead><tr><th>Description</th><th class="text-end">Qty</th><th class="text-end">Unit price</th><th class="text-end">Amount</th></tr></thead>
        <tbody>
          <tr><td><?= e($o['project_name']) ?> — <?= $o['package_name'] ? e($o['package_name']) . ' (' . (int) $o['shares_count'] . ' shares each)' : 'shares' ?><div class="small text-muted"><?= (int) $o['shares'] ?> shares total<?= $o['share_unit'] ? ' · ' . e($o['share_unit']) : '' ?></div></td>
            <td class="text-end"><?= (int) $o['quantity'] ?></td><td class="text-end"><?= money($o['unit_price']) ?></td><td class="text-end"><?= money($o['subtotal']) ?></td></tr>
          <?php foreach (['discount' => 'Discount', 'registration_fee' => 'Registration fee', 'development_fee' => 'Development fee', 'other_fee' => 'Other fee'] as $k => $l): if ((float) $o[$k] == 0) continue; ?>
            <tr><td colspan="3" class="text-end text-muted"><?= $l ?></td><td class="text-end"><?= $k === 'discount' ? '−' : '' ?><?= money($o[$k]) ?></td></tr>
          <?php endforeach; ?>
          <tr><td colspan="3" class="text-end fw-bold">Total</td><td class="text-end fw-bold fs-5"><?= money($o['total']) ?></td></tr>
        </tbody>
      </table></div>
      <?php if ($o['notes']): ?><div class="small"><strong>Notes:</strong> <?= nl2br(e($o['notes'])) ?></div><?php endif; ?>
      <?php if ($o['terms']): ?><div class="small text-muted mt-2" style="white-space:pre-line"><strong>Terms:</strong> <?= e($o['terms']) ?></div><?php endif; ?>
    </div></div>
  </div>
  <div class="col-lg-4">
    <div class="card mb-3"><div class="card-body">
      <dl class="dl-grid">
        <dt>Number</dt><dd><?= e($o['offer_number']) ?></dd>
        <dt>Created</dt><dd><?= e(fdate($o['created_at'])) ?> by <?= e($o['created_by_name']) ?></dd>
        <dt>Sent</dt><dd><?= e(fdate($o['sent_at'], true) ?: '—') ?></dd>
        <dt>Viewed</dt><dd><?= e(fdate($o['viewed_at'], true) ?: '—') ?></dd>
        <?php if ($o['sale_id']): ?><dt>Sale</dt><dd><a href="<?= url('/sales/' . $o['sale_id']) ?>"><?= e($o['sale_number']) ?></a></dd><?php endif; ?>
      </dl>
    </div></div>
    <?php if ($publicLink && can('offers.send')): ?>
    <div class="card mb-3"><div class="card-header">Share</div><div class="card-body d-grid gap-2">
      <button type="button" class="btn btn-light" data-copy="<?= e($publicLink) ?>"><i class="bi bi-link-45deg me-1"></i>Copy offer link</button>
      <?php if ($whatsapp): ?><a href="<?= e($whatsapp) ?>" target="_blank" rel="noopener" class="btn btn-success"><i class="bi bi-whatsapp me-1"></i>Send via WhatsApp</a><?php endif; ?>
      <div class="small text-muted">The customer can view the offer without logging in. Opening it marks the offer as "viewed".</div>
    </div></div>
    <?php endif; ?>
    <?php if (can('offers.edit') && $o['status'] !== 'converted'): ?>
    <div class="card"><div class="card-header">Status</div><div class="card-body d-flex flex-wrap gap-2">
      <?php foreach (['accepted' => 'success', 'rejected' => 'danger'] as $s => $c): if ($o['status'] === $s) continue; ?>
        <?= post_button('/offers/' . $o['id'] . '/status', '', ['fields' => ['status' => $s], 'html' => 'Mark ' . $s, 'class' => 'btn btn-sm btn-outline-' . $c]) ?>
      <?php endforeach; ?>
      <?php if (can('offers.delete')): ?><?= post_button('/offers/' . $o['id'] . '/delete', '', ['html' => '<i class="bi bi-trash"></i> Delete', 'class' => 'btn btn-sm btn-outline-secondary', 'confirm' => 'Delete this offer?']) ?><?php endif; ?>
    </div></div>
    <?php endif; ?>
  </div>
</div>
