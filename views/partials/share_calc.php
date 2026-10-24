<?php
/**
 * Shared share-pricing fields for offers & sales.
 * Vars: $rec (record/prefill), $projects, $feeOverride. JS (data-share-calc) previews; server recalculates.
 */
$feeRo = $feeOverride ? [] : ['readonly' => true];
?>
<div class="row g-3">
  <?= form_select('project_id', 'Project', $projects, $rec, ['required' => true, 'empty' => '— select project —', 'col' => 'col-md-6']) ?>
  <div class="col-md-6">
    <label class="form-label" for="f_package_id">Package</label>
    <select name="package_id" id="f_package_id" class="form-select<?= invalid('package_id') ?>" data-selected="<?= e(fv($rec, 'package_id')) ?>"><option value="">— none (per share) —</option></select>
    <?= error_for('package_id') ?>
  </div>
  <div class="col-12"><div id="share-availability" class="small"></div>
    <div id="calc-warning" class="alert alert-warning py-1 px-2 small mt-1 mb-0 d-none">Requested shares exceed availability — the server will reject this.</div></div>
  <?= form_input('quantity', 'Quantity', $rec, ['type' => 'number', 'required' => true, 'col' => 'col-md-3', 'default' => 1, 'attrs' => ['min' => 1], 'help' => 'Packages, or shares if no package']) ?>
  <?= form_input('unit_price', 'Unit price', $rec, ['type' => 'money', 'col' => 'col-md-3', 'attrs' => ['readonly' => true, 'tabindex' => -1], 'help' => 'From project / package']) ?>
  <?= form_input('discount', 'Discount', $rec, ['type' => 'money', 'col' => 'col-md-3', 'default' => '0.00']) ?>
  <?= form_input('other_fee', 'Other fee', $rec, ['type' => 'money', 'col' => 'col-md-3', 'default' => '0.00']) ?>
  <?= form_input('registration_fee', 'Registration fee', $rec, ['type' => 'money', 'col' => 'col-md-6', 'default' => '0.00', 'attrs' => $feeRo, 'help' => $feeOverride ? null : 'Project fee × shares']) ?>
  <?= form_input('development_fee', 'Development fee', $rec, ['type' => 'money', 'col' => 'col-md-6', 'default' => '0.00', 'attrs' => $feeRo, 'help' => $feeOverride ? null : 'Project fee × shares']) ?>
</div>
<div class="d-flex flex-wrap gap-4 mt-3 p-3 rounded" style="background:var(--brand-soft)">
  <div><div class="stat-label">Shares</div><div class="fs-5 fw-semibold" id="calc-shares">0</div></div>
  <div><div class="stat-label">Subtotal</div><div class="fs-5 fw-semibold" id="calc-subtotal">0.00</div></div>
  <div><div class="stat-label">Grand total</div><div class="fs-4 fw-bold" style="color:var(--brand-ink)" id="calc-total">0.00</div></div>
  <div class="small text-muted align-self-end">Preview only — totals are recalculated by the server.</div>
</div>
