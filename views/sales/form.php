<form method="post" action="<?= url('/sales') ?>" data-share-calc data-fee-override="<?= $feeOverride ? '1' : '0' ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="idempotency_key" value="<?= e(fv($rec, 'idempotency_key')) ?>">
  <?php if ($offer): ?><input type="hidden" name="offer_id" value="<?= (int) $offer['id'] ?>">
    <div class="alert alert-info small">Converting offer <strong><?= e($offer['offer_number']) ?></strong>. Prices are re-validated by the server against current project settings.</div><?php endif; ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card mb-3"><div class="card-header">Shares</div><div class="card-body">
        <?= partial('partials/share_calc', ['rec' => $rec, 'projects' => $projects, 'feeOverride' => $feeOverride]) ?>
      </div></div>
      <?php if (can('payments.create')): ?>
      <div class="card"><div class="card-header">Initial payment <span class="small text-muted fw-normal">(optional, confirmed sales only)</span></div><div class="card-body"><div class="row g-3">
        <?= form_input('initial_payment', 'Amount received', $rec, ['type' => 'money', 'col' => 'col-md-4', 'default' => '']) ?>
        <?= form_select('payment_method_id', 'Method', $methods, $rec, ['empty' => '—', 'col' => 'col-md-4']) ?>
        <?= form_input('payment_reference', 'Reference', $rec, ['col' => 'col-md-4', 'placeholder' => 'Txn ID / cheque no.']) ?>
        <div class="col-12 small">Due after this payment: <strong id="calc-due">—</strong></div>
      </div></div></div>
      <?php endif; ?>
    </div>
    <div class="col-lg-4">
      <div class="card"><div class="card-header">Sale</div><div class="card-body"><div class="row g-3">
        <div class="col-12">
          <label class="form-label" for="f_customer_id">Customer <span class="req">*</span></label>
          <input type="search" class="form-control form-control-sm mb-1" placeholder="Search customers…" data-lookup="/api/lookup/customers" data-lookup-target="#f_customer_id">
          <select name="customer_id" id="f_customer_id" class="form-select<?= invalid('customer_id') ?>" required><option value="">— select —</option>
            <?php foreach ($customers as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, fv($rec, 'customer_id')) ?>><?= e($n) ?></option><?php endforeach; ?></select>
          <?= error_for('customer_id') ?>
          <?php if (can('customers.create')): ?><div class="form-text"><a href="<?= url('/customers/create') ?>" target="_blank">+ New customer</a></div><?php endif; ?>
        </div>
        <?= form_input('sale_date', 'Sale date', $rec, ['type' => 'date', 'required' => true, 'col' => 'col-12']) ?>
        <?= form_select('status', 'Status', ['confirmed' => 'Confirmed (create invoice)', 'reserved' => 'Reserved (hold shares)'], $rec, ['col' => 'col-12']) ?>
        <?= form_select('sales_person_id', 'Sales person', $users, $rec, ['empty' => '—', 'col' => 'col-12']) ?>
        <?= form_select('director_id', 'Referred by director', $directors, $rec, ['empty' => '— none (direct sale) —', 'col' => 'col-12', 'help' => 'Only when a director sells this share to their own customer. A director buying for themselves is a direct sale.']) ?>
        <div class="col-12" data-show-when="director_id">
          <?= form_input('customer_unit_price', 'Customer price (per unit)', $rec, ['type' => 'money', 'col' => 'w-100', 'help' => 'The price the director sold each package/share at. Office copy & accounts keep the package price; the customer copy shows this price.']) ?>
          <div class="small mt-1">Customer pays <strong id="calc-cust-total">—</strong> · Director profit <strong class="text-success" id="calc-profit">—</strong></div>
        </div>
        <?= form_textarea('notes', 'Notes', $rec, ['rows' => 3]) ?>
      </div></div></div>
    </div>
  </div>
  <div class="mt-3 d-flex gap-2"><button class="btn btn-brand"><i class="bi bi-check2 me-1"></i>Save sale</button><a href="<?= url('/sales') ?>" class="btn btn-light">Cancel</a></div>
</form>
