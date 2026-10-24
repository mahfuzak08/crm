<?php App\Core\View::start('actions'); ?>
<a href="<?= url('/payments/' . $p['id'] . '/receipt') ?>" target="_blank" class="btn btn-brand"><i class="bi bi-printer me-1"></i>Print receipt</a>
<?php if ($p['status'] === 'confirmed' && can('payments.delete')): ?><?= post_button('/payments/' . $p['id'] . '/cancel', '', ['html' => '<i class="bi bi-arrow-counterclockwise me-1"></i>Cancel (reverse)', 'class' => 'btn btn-outline-danger', 'confirm' => 'Reverse this payment? The invoice due will increase again.', 'reason' => true]) ?><?php endif; ?>
<?php App\Core\View::end(); ?>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="card"><div class="card-body">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div><div class="stat-label">Amount</div><div class="display-6 fw-semibold<?= $p['status'] === 'cancelled' ? ' text-decoration-line-through text-muted' : '' ?>"><?= money($p['amount']) ?></div></div>
        <?= status_badge($p['status']) ?>
      </div>
      <dl class="dl-grid">
        <dt>Receipt no.</dt><dd><?= e($p['payment_number']) ?></dd>
        <dt>Date</dt><dd><?= e(fdate($p['payment_date'])) ?></dd>
        <dt>Customer</dt><dd><a href="<?= url('/customers/' . $p['customer_id']) ?>"><?= e($p['customer_name']) ?></a> (<?= e($p['customer_code']) ?>)</dd>
        <dt>Invoice</dt><dd><a href="<?= url('/invoices/' . $p['invoice_id']) ?>"><?= e($p['invoice_number']) ?></a> · due now <?= money($p['invoice_due']) ?></dd>
        <dt>Project</dt><dd><?= e($p['project_name'] ?? '—') ?></dd>
        <dt>Method</dt><dd><?= e($p['method_name']) ?></dd>
        <dt>Reference</dt><dd><?= e($p['reference'] ?: '—') ?></dd>
        <dt>Received by</dt><dd><?= e($p['received_by_name']) ?>, <?= e(fdate($p['created_at'], true)) ?></dd>
        <?php if ($p['status'] === 'cancelled'): ?><dt>Cancelled</dt><dd class="text-danger"><?= e(fdate($p['cancelled_at'], true)) ?> by <?= e($p['cancelled_by_name']) ?><br><?= e($p['cancel_reason']) ?></dd><?php endif; ?>
        <dt>Notes</dt><dd style="white-space:pre-line"><?= e($p['notes'] ?: '—') ?></dd>
      </dl>
    </div></div>
  </div>
  <?php if (can('payments.edit')): ?>
  <div class="col-lg-5">
    <div class="card"><div class="card-header">Edit reference / notes</div><div class="card-body">
      <form method="post" action="<?= url('/payments/' . $p['id']) ?>">
        <?= csrf_field() ?>
        <div class="row g-3">
          <?= form_input('reference', 'Reference', $p, ['col' => 'col-12']) ?>
          <?= form_textarea('notes', 'Notes', $p, ['rows' => 3]) ?>
        </div>
        <button class="btn btn-sm btn-brand mt-3">Save</button>
        <div class="small text-muted mt-2">Amounts cannot be edited. Cancel and re-enter instead.</div>
      </form>
    </div></div>
  </div>
  <?php endif; ?>
</div>
