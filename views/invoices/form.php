<?php
$old = App\Core\Session::getFlash('_old');
$rows = [];
if ($old && isset($old['items']['desc'])) {
    foreach ($old['items']['desc'] as $k => $d) {
        $rows[] = ['description' => $d, 'quantity' => $old['items']['qty'][$k] ?? '', 'unit_price' => $old['items']['price'][$k] ?? ''];
    }
} else {
    $rows = $items ?: [['description' => '', 'quantity' => '1', 'unit_price' => '']];
}
$row = static fn(array $r) => '<tr>'
    . '<td><input name="items[desc][]" class="form-control form-control-sm" value="' . e($r['description']) . '" maxlength="255" required></td>'
    . '<td style="width:110px"><input name="items[qty][]" class="form-control form-control-sm text-end" inputmode="decimal" value="' . e($r['quantity']) . '" required></td>'
    . '<td style="width:160px"><input name="items[price][]" class="form-control form-control-sm text-end" inputmode="decimal" value="' . e($r['unit_price']) . '" required></td>'
    . '<td class="text-end amt text-nowrap" style="width:140px">0.00</td>'
    . '<td style="width:40px"><button type="button" class="btn btn-sm btn-light text-danger" data-remove-row><i class="bi bi-x"></i></button></td></tr>';
?>
<form method="post" action="<?= url($inv ? '/invoices/' . $inv['id'] : '/invoices') ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card" data-invoice-items>
        <div class="card-header">Line items</div>
        <div class="table-responsive"><table class="table mb-0">
          <thead><tr><th>Description</th><th class="text-end">Qty</th><th class="text-end">Unit price</th><th class="text-end">Amount</th><th></th></tr></thead>
          <tbody><?php foreach ($rows as $r) echo $row($r); ?></tbody>
        </table></div>
        <template id="item-row-template"><?= $row(['description' => '', 'quantity' => '1', 'unit_price' => '']) ?></template>
        <div class="card-body border-top">
          <button type="button" class="btn btn-sm btn-light" data-add-row><i class="bi bi-plus-lg"></i> Add line</button>
          <?= error_for('items') ?>
          <div class="row g-2 mt-2 justify-content-end">
            <div class="col-md-7">
              <table class="table table-sm mb-0">
                <tr><td class="text-muted">Subtotal</td><td class="text-end" id="inv-subtotal">0.00</td></tr>
                <tr><td class="text-muted">Discount</td><td><input name="discount" class="form-control form-control-sm text-end<?= invalid('discount') ?>" inputmode="decimal" value="<?= e(fv($rec, 'discount', '0.00')) ?>"><?= error_for('discount') ?></td></tr>
                <tr><td class="text-muted">Tax / VAT %</td><td><input name="tax_percent" class="form-control form-control-sm text-end<?= invalid('tax_percent') ?>" inputmode="decimal" value="<?= e(fv($rec, 'tax_percent', '0')) ?>"><?= error_for('tax_percent') ?></td></tr>
                <tr><td class="text-muted">Tax amount</td><td class="text-end" id="inv-tax">0.00</td></tr>
                <tr><td class="text-muted">Other charge</td><td><input name="other_charge" class="form-control form-control-sm text-end<?= invalid('other_charge') ?>" inputmode="decimal" value="<?= e(fv($rec, 'other_charge', '0.00')) ?>"></td></tr>
                <tr><td class="fw-bold">Grand total</td><td class="text-end fw-bold fs-5" id="inv-total">0.00</td></tr>
              </table>
              <div class="small text-muted text-end">Preview — the server recalculates every amount.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card"><div class="card-header">Invoice</div><div class="card-body"><div class="row g-3">
        <div class="col-12">
          <label class="form-label" for="f_customer_id">Customer <span class="req">*</span></label>
          <input type="search" class="form-control form-control-sm mb-1" placeholder="Search customers…" data-lookup="/api/lookup/customers" data-lookup-target="#f_customer_id">
          <select name="customer_id" id="f_customer_id" class="form-select<?= invalid('customer_id') ?>" required><option value="">— select —</option>
            <?php foreach ($customers as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, fv($rec, 'customer_id')) ?>><?= e($n) ?></option><?php endforeach; ?></select>
          <?= error_for('customer_id') ?>
        </div>
        <?= form_select('project_id', 'Project', $projects, $rec, ['empty' => '—', 'col' => 'col-12']) ?>
        <?= form_input('invoice_date', 'Invoice date', $rec, ['type' => 'date', 'required' => true, 'col' => 'col-6']) ?>
        <?= form_input('due_date', 'Due date', $rec, ['type' => 'date', 'col' => 'col-6', 'help' => 'Blank = default terms']) ?>
        <?= form_textarea('notes', 'Notes', $rec, ['rows' => 2]) ?>
        <?= form_textarea('terms', 'Terms', $rec, ['rows' => 3]) ?>
      </div></div></div>
    </div>
  </div>
  <div class="mt-3 d-flex gap-2">
    <button class="btn btn-brand" name="save_as" value="issue"><i class="bi bi-check2 me-1"></i><?= $inv ? 'Save & issue' : 'Create invoice' ?></button>
    <button class="btn btn-light" name="save_as" value="draft">Save as draft</button>
    <a href="<?= url($inv ? '/invoices/' . $inv['id'] : '/invoices') ?>" class="btn btn-light">Cancel</a>
  </div>
</form>
