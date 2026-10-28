<?php App\Core\View::start('actions'); ?>
<a href="<?= url('/sales/' . $s['id'] . '/agreement') ?>" target="_blank" class="btn btn-light"><i class="bi bi-file-earmark-text me-1"></i>Agreement</a>
<?php if ($s['director_id']): ?><a href="<?= url('/sales/' . $s['id'] . '/agreement', ['copy' => 'office']) ?>" target="_blank" class="btn btn-light" title="Agreement at package price"><i class="bi bi-building me-1"></i>Office agreement</a><?php endif; ?>
<?php if ($s['invoice_id'] && can('invoices.view')): ?><a href="<?= url('/invoices/' . $s['invoice_id']) ?>" class="btn btn-light"><i class="bi bi-receipt me-1"></i>Invoice</a><?php endif; ?>
<?php if ($s['invoice_id'] && $s['status'] === 'confirmed' && (float) $s['due_amount'] > 0 && can('payments.create')): ?><a href="<?= url('/payments/create', ['invoice' => $s['invoice_id']]) ?>" class="btn btn-success"><i class="bi bi-cash me-1"></i>Receive payment</a><?php endif; ?>
<?php if ($s['status'] === 'reserved' && can('sales.create')): ?><?= post_button('/sales/' . $s['id'] . '/confirm', '', ['html' => '<i class="bi bi-check2-circle me-1"></i>Confirm sale', 'class' => 'btn btn-brand', 'confirm' => 'Confirm this reservation and create the invoice?']) ?><?php endif; ?>
<?php if ($s['status'] !== 'cancelled' && can('sales.cancel')): ?><?= post_button('/sales/' . $s['id'] . '/cancel', '', ['html' => '<i class="bi bi-x-circle me-1"></i>Cancel sale', 'class' => 'btn btn-outline-danger', 'confirm' => 'Cancel this sale? Shares go back to inventory. (Payments must be cancelled first.)', 'reason' => true]) ?><?php endif; ?>
<?php App\Core\View::end(); ?>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3"><div class="card-body">
      <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
        <div><div class="text-muted small">Customer</div><a href="<?= url('/customers/' . $s['customer_id']) ?>" class="fw-semibold fs-5"><?= e($s['customer_name']) ?></a><div class="small text-muted"><?= e($s['customer_code']) ?> · <?= e($s['customer_phone']) ?></div></div>
        <div class="text-end"><?= status_badge($s['status']) ?><div class="small text-muted mt-1"><?= e(fdate($s['sale_date'])) ?></div></div>
      </div>
      <div class="table-responsive"><table class="table">
        <thead><tr><th>Description</th><th class="text-end">Qty</th><th class="text-end">Unit price</th><th class="text-end">Amount</th></tr></thead>
        <tbody>
          <tr><td><a href="<?= url('/projects/' . $s['project_id']) ?>"><?= e($s['project_name']) ?></a> — <?= $s['package_name'] ? e($s['package_name']) . ' (' . (int) $s['shares_count'] . ' shares each)' : 'per share' ?><div class="small text-muted"><?= (int) $s['shares'] ?> shares<?= $s['share_unit'] ? ' · ' . e($s['share_unit']) : '' ?></div></td>
            <td class="text-end"><?= (int) $s['quantity'] ?></td><td class="text-end"><?= money($s['unit_price']) ?></td><td class="text-end"><?= money($s['subtotal']) ?></td></tr>
          <?php foreach (['discount' => 'Discount', 'registration_fee' => 'Registration fee', 'development_fee' => 'Development fee', 'other_fee' => 'Other fee'] as $k => $l): if ((float) $s[$k] == 0) continue; ?>
            <tr><td colspan="3" class="text-end text-muted"><?= $l ?></td><td class="text-end"><?= $k === 'discount' ? '−' : '' ?><?= money($s[$k]) ?></td></tr>
          <?php endforeach; ?>
          <tr><td colspan="3" class="text-end fw-bold">Grand total</td><td class="text-end fw-bold"><?= money($s['grand_total']) ?></td></tr>
          <tr><td colspan="3" class="text-end text-success">Paid</td><td class="text-end text-success"><?= money($s['paid_amount']) ?></td></tr>
          <tr><td colspan="3" class="text-end text-danger fw-semibold">Due</td><td class="text-end text-danger fw-semibold"><?= money($s['due_amount']) ?></td></tr>
        </tbody>
      </table></div>
    </div></div>
    <?php if ($s['director_id']): $cf = App\Services\SaleService::customerFigures($s, (string) $s['paid_amount'], (string) $s['grand_total']); ?>
    <div class="card mb-3 border-success-subtle"><div class="card-header">Director referral <span class="small text-muted fw-normal">figures above are the office copy (package price)</span></div>
      <div class="card-body"><div class="row g-3 text-center">
        <div class="col-6 col-md-3"><div class="stat-label">Office (package) total</div><div class="fw-semibold"><?= money($s['grand_total']) ?></div></div>
        <div class="col-6 col-md-3"><div class="stat-label">Customer price</div><div class="fw-semibold"><?= money($s['customer_unit_price']) ?> × <?= (int) $s['quantity'] ?></div></div>
        <div class="col-6 col-md-3"><div class="stat-label">Customer copy total</div><div class="fw-semibold"><?= money($s['customer_total']) ?></div><div class="small text-muted">paid <?= money($cf['paid']) ?> · due <?= money($cf['due']) ?></div></div>
        <div class="col-6 col-md-3"><div class="stat-label">Director profit</div><div class="fw-bold text-success"><?= money($s['director_profit']) ?></div></div>
      </div></div></div>
    <?php endif; ?>
    <?php if ($payments): ?>
    <div class="card"><div class="card-header">Payments</div><div class="table-responsive"><table class="table">
      <thead><tr><th>Receipt</th><th>Date</th><th>Method</th><th>Reference</th><th class="text-end">Amount</th><th>Status</th></tr></thead><tbody>
      <?php foreach ($payments as $p): ?><tr><td><a href="<?= url('/payments/' . $p['id']) ?>"><?= e($p['payment_number']) ?></a></td><td class="small"><?= e(fdate($p['payment_date'])) ?></td><td class="small"><?= e($p['method']) ?></td><td class="small"><?= e($p['reference']) ?></td><td class="text-end"><?= money($p['amount']) ?></td><td><?= status_badge($p['status']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
    <?php endif; ?>
  </div>
  <div class="col-lg-4">
    <div class="card"><div class="card-body"><dl class="dl-grid">
      <dt>Sale no.</dt><dd><?= e($s['sale_number']) ?></dd>
      <dt>Invoice</dt><dd><?= $s['invoice_id'] ? '<a href="' . url('/invoices/' . $s['invoice_id']) . '">' . e($s['invoice_number']) . '</a>' : '—' ?></dd>
      <?php if ($s['offer_id']): ?><dt>Offer</dt><dd><a href="<?= url('/offers/' . $s['offer_id']) ?>"><?= e($s['offer_number']) ?></a></dd><?php endif; ?>
      <dt>Sales person</dt><dd><?= e($s['sales_person'] ?? '—') ?></dd>
      <?php if ($s['director_id']): ?><dt>Referred by</dt><dd><?= can('directors.view') ? '<a href="' . url('/directors/' . $s['director_id']) . '">' . e($s['director_name']) . '</a>' : e($s['director_name']) ?> <span class="badge text-bg-light text-dark">Director</span></dd><?php endif; ?>
      <dt>Created by</dt><dd><?= e($s['created_by_name']) ?>, <?= e(fdate($s['created_at'], true)) ?></dd>
      <?php if ($s['status'] === 'cancelled'): ?><dt>Cancelled</dt><dd class="text-danger"><?= e(fdate($s['cancelled_at'], true)) ?><br><?= e($s['cancel_reason']) ?></dd><?php endif; ?>
      <?php if ($s['notes']): ?><dt>Notes</dt><dd style="white-space:pre-line"><?= e($s['notes']) ?></dd><?php endif; ?>
    </dl></div></div>
  </div>
</div>
