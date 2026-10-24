<div class="card" style="max-width:760px">
  <div class="card-body">
    <form method="post" action="<?= url('/payments') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="idempotency_key" value="<?= e(fv($rec, 'idempotency_key')) ?>">
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label" for="f_invoice_id">Invoice <span class="req">*</span></label>
          <select name="invoice_id" id="f_invoice_id" class="form-select<?= invalid('invoice_id') ?>" required data-invoice-due>
            <option value="">— select an open invoice —</option>
            <?php foreach ($invoices as $i): ?>
              <option value="<?= (int) $i['id'] ?>" data-due="<?= e($i['due_amount']) ?>"<?= selected($i['id'], fv($rec, 'invoice_id')) ?>><?= e($i['invoice_number'] . ' — ' . $i['customer_name'] . ' — due ' . money($i['due_amount'])) ?></option>
            <?php endforeach; ?>
          </select>
          <?= error_for('invoice_id') ?>
          <div class="form-text" id="invoice-due-info"></div>
        </div>
        <?= form_input('amount', 'Amount', $rec, ['type' => 'money', 'required' => true, 'col' => 'col-md-4']) ?>
        <?= form_input('payment_date', 'Payment date', $rec, ['type' => 'date', 'required' => true, 'col' => 'col-md-4', 'attrs' => ['max' => today()]]) ?>
        <?= form_select('payment_method_id', 'Method', $methods, $rec, ['required' => true, 'empty' => '— select —', 'col' => 'col-md-4']) ?>
        <?= form_input('reference', 'Reference', $rec, ['col' => 'col-12', 'placeholder' => 'bKash TrxID, cheque no., bank ref…']) ?>
        <?= form_textarea('notes', 'Notes', $rec, ['rows' => 2]) ?>
      </div>
      <div class="mt-3 d-flex gap-2"><button class="btn btn-success"><i class="bi bi-check2 me-1"></i>Receive payment</button><a href="<?= url('/payments') ?>" class="btn btn-light">Cancel</a></div>
      <p class="small text-muted mt-3 mb-0">The amount cannot exceed the invoice due. Payments are never deleted — cancel to reverse.</p>
    </form>
  </div>
</div>
