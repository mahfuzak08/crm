<?php
$payable = in_array($inv['status'], ['unpaid', 'partial', 'overdue'], true);
App\Core\View::start('actions'); ?>
<?php if (can('invoices.print')): ?>
  <div class="btn-group"><a href="<?= url('/invoices/' . $inv['id'] . '/print') ?>" target="_blank" class="btn btn-light"><i class="bi bi-printer me-1"></i>Print both copies</a>
    <button type="button" class="btn btn-light dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false"><span class="visually-hidden">Copies</span></button>
    <ul class="dropdown-menu dropdown-menu-end">
      <li><a class="dropdown-item" href="<?= url('/invoices/' . $inv['id'] . '/print', ['copy' => 'office']) ?>" target="_blank">Office copy</a></li>
      <li><a class="dropdown-item" href="<?= url('/invoices/' . $inv['id'] . '/print', ['copy' => 'customer']) ?>" target="_blank">Customer copy</a></li>
    </ul></div>
<?php endif; ?>
<?php if ($payable && can('payments.create')): ?><a href="<?= url('/payments/create', ['invoice' => $inv['id']]) ?>" class="btn btn-success"><i class="bi bi-cash me-1"></i>Receive payment</a><?php endif; ?>
<?php if ($inv['status'] === 'draft' && can('invoices.edit')): ?>
  <a href="<?= url('/invoices/' . $inv['id'] . '/edit') ?>" class="btn btn-light"><i class="bi bi-pencil me-1"></i>Edit</a>
  <?= post_button('/invoices/' . $inv['id'] . '/issue', '', ['html' => '<i class="bi bi-send-check me-1"></i>Issue', 'class' => 'btn btn-brand']) ?>
<?php endif; ?>
<?php if ($inv['status'] !== 'cancelled' && !$inv['sale_id'] && can('invoices.delete')): ?><?= post_button('/invoices/' . $inv['id'] . '/cancel', '', ['html' => '<i class="bi bi-x-circle me-1"></i>Cancel', 'class' => 'btn btn-outline-danger', 'confirm' => 'Cancel this invoice?', 'reason' => true]) ?><?php endif; ?>
<?php App\Core\View::end(); ?>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3"><div class="card-body">
      <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
        <div><div class="text-muted small">Bill to</div><a href="<?= url('/customers/' . $inv['customer_id']) ?>" class="fw-semibold fs-5"><?= e($inv['customer_name']) ?></a>
          <div class="small text-muted"><?= e($inv['customer_code']) ?> · <?= e($inv['customer_phone']) ?></div></div>
        <div class="text-end"><?= status_badge($inv['status']) ?>
          <div class="small text-muted mt-1">Issued <?= e(fdate($inv['invoice_date'])) ?> · Due <?= e(fdate($inv['due_date'])) ?></div></div>
      </div>
      <div class="table-responsive"><table class="table">
        <thead><tr><th>Description</th><th class="text-end">Qty</th><th class="text-end">Unit price</th><th class="text-end">Amount</th></tr></thead>
        <tbody>
          <?php foreach ($inv['items'] as $it): ?><tr><td><?= e($it['description']) ?></td><td class="text-end"><?= e(rtrim(rtrim((string) $it['quantity'], '0'), '.')) ?></td><td class="text-end"><?= money($it['unit_price']) ?></td><td class="text-end"><?= money($it['amount']) ?></td></tr><?php endforeach; ?>
          <tr><td colspan="3" class="text-end text-muted">Subtotal</td><td class="text-end"><?= money($inv['subtotal']) ?></td></tr>
          <?php if ((float) $inv['discount'] > 0): ?><tr><td colspan="3" class="text-end text-muted">Discount</td><td class="text-end">−<?= money($inv['discount']) ?></td></tr><?php endif; ?>
          <?php if ((float) $inv['tax'] > 0): ?><tr><td colspan="3" class="text-end text-muted">Tax (<?= e((string) (float) $inv['tax_percent']) ?>%)</td><td class="text-end"><?= money($inv['tax']) ?></td></tr><?php endif; ?>
          <?php if ((float) $inv['other_charge'] > 0): ?><tr><td colspan="3" class="text-end text-muted">Other charge</td><td class="text-end"><?= money($inv['other_charge']) ?></td></tr><?php endif; ?>
          <tr><td colspan="3" class="text-end fw-bold">Grand total</td><td class="text-end fw-bold"><?= money($inv['grand_total']) ?></td></tr>
          <tr><td colspan="3" class="text-end text-success">Paid</td><td class="text-end text-success"><?= money($inv['paid_amount']) ?></td></tr>
          <tr><td colspan="3" class="text-end text-danger fw-semibold">Due</td><td class="text-end text-danger fw-semibold"><?= money($inv['due_amount']) ?></td></tr>
        </tbody>
      </table></div>
      <?php if ($inv['notes']): ?><div class="small"><strong>Notes:</strong> <?= nl2br(e($inv['notes'])) ?></div><?php endif; ?>
      <?php if ($inv['director_id']): $cc = App\Services\InvoiceService::customerCopy($inv); ?>
        <div class="alert alert-info small mt-3 mb-0"><i class="bi bi-person-up me-1"></i>Referral sale by director <strong><?= e($inv['director_name']) ?></strong>.
          These are the <strong>office</strong> figures (package price). The customer copy shows <strong><?= money($cc['grand_total']) ?></strong>
          (paid <?= money($cc['paid_amount']) ?>, due <?= money($cc['due_amount']) ?>); director profit <strong><?= money($inv['director_profit']) ?></strong>.</div>
      <?php endif; ?>
    </div></div>
    <div class="card"><div class="card-header">Payments</div><div class="table-responsive"><table class="table">
      <thead><tr><th>Receipt</th><th>Date</th><th>Method</th><th>Reference</th><th>Received by</th><th class="text-end">Amount</th><th>Status</th></tr></thead><tbody>
      <?php foreach ($payments as $p): ?><tr><td><a href="<?= url('/payments/' . $p['id']) ?>"><?= e($p['payment_number']) ?></a></td><td class="small"><?= e(fdate($p['payment_date'])) ?></td><td class="small"><?= e($p['method']) ?></td><td class="small"><?= e($p['reference']) ?></td><td class="small"><?= e($p['received_by_name']) ?></td><td class="text-end"><?= money($p['amount']) ?></td><td><?= status_badge($p['status']) ?></td></tr><?php endforeach; ?>
      <?php if (!$payments) echo empty_row(7, 'No payments yet.'); ?>
    </tbody></table></div></div>
  </div>
  <div class="col-lg-4">
    <div class="card"><div class="card-body"><dl class="dl-grid">
      <dt>Invoice no.</dt><dd><?= e($inv['invoice_number']) ?></dd>
      <dt>Project</dt><dd><?= e($inv['project_name'] ?? '—') ?></dd>
      <?php if ($inv['sale_id']): ?><dt>Share sale</dt><dd><a href="<?= url('/sales/' . $inv['sale_id']) ?>"><?= e($inv['sale_number']) ?></a></dd><?php endif; ?>
      <dt>Created by</dt><dd><?= e($inv['created_by_name']) ?>, <?= e(fdate($inv['created_at'], true)) ?></dd>
      <?php if ($inv['status'] === 'cancelled'): ?><dt>Cancelled</dt><dd class="text-danger"><?= e(fdate($inv['cancelled_at'], true)) ?><br><?= e($inv['cancel_reason']) ?></dd><?php endif; ?>
    </dl>
    <?php if ($inv['terms']): ?><hr><div class="small text-muted" style="white-space:pre-line"><?= e($inv['terms']) ?></div><?php endif; ?>
    </div></div>
  </div>
</div>
