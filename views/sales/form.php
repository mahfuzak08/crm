<?php
$isRef = (string) fv($rec, 'director_id') !== '';
$feeRo = $feeOverride ? [] : ['readonly' => true, 'tabindex' => -1];
$adjOpen = (float) fv($rec, 'discount') > 0 || (float) fv($rec, 'other_fee') > 0 || invalid('discount') !== '' || invalid('other_fee') !== '' || invalid('registration_fee') !== '' || invalid('development_fee') !== '';
$status = fv($rec, 'status') ?: 'confirmed';
?>
<form method="post" action="<?= url('/sales') ?>" class="sale-form" data-share-calc data-fee-override="<?= $feeOverride ? '1' : '0' ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="idempotency_key" value="<?= e(fv($rec, 'idempotency_key')) ?>">
  <input type="hidden" name="unit_price" value="<?= e(fv($rec, 'unit_price')) ?>">
  <?php if ($offer): ?><input type="hidden" name="offer_id" value="<?= (int) $offer['id'] ?>">
    <div class="alert alert-info small"><i class="bi bi-send-check me-1"></i>Converting offer <strong><?= e($offer['offer_number']) ?></strong>. Prices are re-checked against the current project settings.</div><?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-8">

      <!-- 1. Buyer -->
      <section class="card step-card mb-3"><div class="card-body">
        <h2 class="step-title"><span class="step-no">1</span>Buyer</h2>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label" for="f_customer_id">Customer <span class="req">*</span></label>
            <div class="input-group lookup-group">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input type="search" class="form-control" placeholder="Type name, phone or code to search…" data-lookup="/api/lookup/customers" data-lookup-target="#f_customer_id" aria-label="Search customers">
            </div>
            <select name="customer_id" id="f_customer_id" class="form-select mt-2<?= invalid('customer_id') ?>" required><option value="">— select customer —</option>
              <?php foreach ($customers as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, fv($rec, 'customer_id')) ?>><?= e($n) ?></option><?php endforeach; ?></select>
            <?= error_for('customer_id') ?>
            <?php if (can('customers.create')): ?><div class="form-text"><a href="<?= url('/customers/create') ?>" target="_blank"><i class="bi bi-person-plus"></i> New customer</a> (opens in a new tab, then search for them here)</div><?php endif; ?>
          </div>

          <?php if ($directors): ?>
          <div class="col-12">
            <label class="form-label d-block">Sale type</label>
            <div class="seg" role="radiogroup" aria-label="Sale type">
              <input type="radio" class="btn-check" name="sale_type" id="st_direct" value="direct"<?= $isRef ? '' : ' checked' ?>>
              <label class="seg-btn" for="st_direct"><i class="bi bi-building"></i><span><strong>Direct sale</strong><small>Customer pays the package price</small></span></label>
              <input type="radio" class="btn-check" name="sale_type" id="st_ref" value="referral"<?= $isRef ? ' checked' : '' ?>>
              <label class="seg-btn" for="st_ref"><i class="bi bi-person-up"></i><span><strong>Director referral</strong><small>Director sells at their own price</small></span></label>
            </div>
          </div>
          <div class="col-12" data-show-when="sale_type=referral">
            <div class="referral-box">
              <div class="row g-3">
                <?= form_select('director_id', 'Referred by director', $directors, $rec, ['empty' => '— select director —', 'col' => 'col-md-6']) ?>
                <?= form_input('customer_unit_price', 'Customer price (per unit)', $rec, ['type' => 'money', 'col' => 'col-md-6', 'placeholder' => 'e.g. 800000']) ?>
                <div class="col-12 form-text mt-1">The company books the package price (office copy). The customer copy and history show this price, and the difference is the director's profit.</div>
              </div>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div></section>

      <!-- 2. Shares -->
      <section class="card step-card mb-3"><div class="card-body">
        <h2 class="step-title"><span class="step-no">2</span>Shares</h2>
        <div class="row g-3">
          <?= form_select('project_id', 'Project', $projects, $rec, ['required' => true, 'empty' => '— select project —', 'col' => 'col-12']) ?>
          <div class="col-12"><div id="share-availability" class="small"></div></div>
          <div class="col-12">
            <label class="form-label">Package</label>
            <select name="package_id" id="f_package_id" class="form-select visually-hidden<?= invalid('package_id') ?>" data-selected="<?= e(fv($rec, 'package_id')) ?>" tabindex="-1" aria-hidden="true"><option value="">— none (per share) —</option></select>
            <div id="pkg-cards" class="pkg-cards" role="radiogroup" aria-label="Package"><div class="text-muted small">Select a project to see its packages.</div></div>
            <?= error_for('package_id') ?>
          </div>
          <div class="col-sm-6 col-md-4">
            <label class="form-label" for="f_quantity">Quantity <span class="req">*</span></label>
            <div class="input-group qty-stepper">
              <button class="btn btn-light" type="button" data-step="-1" aria-label="Decrease"><i class="bi bi-dash-lg"></i></button>
              <input type="number" name="quantity" id="f_quantity" class="form-control text-center<?= invalid('quantity') ?>" value="<?= e(fv($rec, 'quantity', 1)) ?>" min="1" required inputmode="numeric">
              <button class="btn btn-light" type="button" data-step="1" aria-label="Increase"><i class="bi bi-plus-lg"></i></button>
            </div>
            <div class="form-text" id="qty-help">Packages, or shares if no package</div>
            <?= error_for('quantity') ?>
          </div>
          <div class="col-12">
            <div id="calc-warning" class="alert alert-warning py-2 px-3 small mb-0 d-none"><i class="bi bi-exclamation-triangle me-1"></i>Requested shares exceed availability. The sale will be rejected.</div>
          </div>
        </div>

        <details class="adjust mt-3"<?= $adjOpen ? ' open' : '' ?>>
          <summary><i class="bi bi-sliders me-1"></i>Discount &amp; fees</summary>
          <div class="row g-3 pt-3">
            <?= form_input('discount', 'Discount', $rec, ['type' => 'money', 'col' => 'col-6 col-md-3', 'default' => '0.00']) ?>
            <?= form_input('other_fee', 'Other fee', $rec, ['type' => 'money', 'col' => 'col-6 col-md-3', 'default' => '0.00']) ?>
            <?= form_input('registration_fee', 'Registration fee', $rec, ['type' => 'money', 'col' => 'col-6 col-md-3', 'default' => '0.00', 'attrs' => $feeRo]) ?>
            <?= form_input('development_fee', 'Development fee', $rec, ['type' => 'money', 'col' => 'col-6 col-md-3', 'default' => '0.00', 'attrs' => $feeRo]) ?>
            <?php if (!$feeOverride): ?><div class="col-12 form-text mt-1">Registration &amp; development fees are calculated from the project (fee × shares).</div><?php endif; ?>
          </div>
        </details>
      </div></section>

      <!-- 3. Payment -->
      <?php if (can('payments.create')): ?>
      <section class="card step-card mb-3" data-show-when="status=confirmed"><div class="card-body">
        <h2 class="step-title"><span class="step-no">3</span>Initial payment <span class="text-muted fw-normal small ms-1">optional</span></h2>
        <div class="row g-3">
          <?= form_input('initial_payment', 'Amount received', $rec, ['type' => 'money', 'col' => 'col-md-4', 'default' => '', 'placeholder' => '0.00']) ?>
          <?= form_select('payment_method_id', 'Method', $methods, $rec, ['empty' => '— select —', 'col' => 'col-md-4']) ?>
          <?= form_input('payment_reference', 'Reference', $rec, ['col' => 'col-md-4', 'placeholder' => 'Txn ID / cheque no.']) ?>
        </div>
      </div></section>
      <?php endif; ?>

      <section class="card step-card mb-3"><div class="card-body">
        <h2 class="step-title"><span class="step-no"><i class="bi bi-info-lg"></i></span>Details</h2>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label d-block">Status</label>
            <div class="seg seg-sm" role="radiogroup" aria-label="Status">
              <input type="radio" class="btn-check" name="status" id="ss_conf" value="confirmed"<?= $status === 'confirmed' ? ' checked' : '' ?>>
              <label class="seg-btn" for="ss_conf"><i class="bi bi-check2-circle"></i><span><strong>Confirmed</strong><small>Creates the invoice</small></span></label>
              <input type="radio" class="btn-check" name="status" id="ss_res" value="reserved"<?= $status === 'reserved' ? ' checked' : '' ?>>
              <label class="seg-btn" for="ss_res"><i class="bi bi-hourglass-split"></i><span><strong>Reserved</strong><small>Holds the shares only</small></span></label>
            </div>
            <?= error_for('status') ?>
          </div>
          <?= form_input('sale_date', 'Sale date', $rec, ['type' => 'date', 'required' => true, 'col' => 'col-md-6']) ?>
          <?= form_select('sales_person_id', 'Sales person', $users, $rec, ['empty' => '—', 'col' => 'col-md-6']) ?>
          <?= form_textarea('notes', 'Notes', $rec, ['rows' => 2, 'placeholder' => 'Anything the office should know about this sale']) ?>
        </div>
      </div></section>
    </div>

    <!-- Summary -->
    <div class="col-lg-4">
      <aside class="card sale-summary">
        <div class="card-header">Summary</div>
        <div class="card-body">
          <div class="sum-item"><div class="fw-semibold" id="sum-item">No project selected</div><div class="small text-muted" id="sum-line">—</div></div>
          <dl class="sum-rows">
            <dt>Shares</dt><dd id="calc-shares">0</dd>
            <dt>Subtotal</dt><dd id="calc-subtotal">0.00</dd>
            <dt>Discount</dt><dd id="sum-discount">0.00</dd>
            <dt>Fees</dt><dd id="sum-fees">0.00</dd>
          </dl>
          <div class="sum-total"><span>Grand total</span><strong id="calc-total">0.00</strong></div>
          <div class="sum-ref" data-show-when="sale_type=referral">
            <div><span>Customer pays</span><strong id="calc-cust-total">—</strong></div>
            <div class="text-success"><span>Director profit</span><strong id="calc-profit">—</strong></div>
          </div>
          <?php if (can('payments.create')): ?>
          <dl class="sum-rows mt-2" data-show-when="initial_payment">
            <dt>Paid now</dt><dd id="sum-paid">0.00</dd>
            <dt>Due after</dt><dd class="text-danger fw-semibold" id="calc-due">0.00</dd>
          </dl>
          <?php endif; ?>
          <button class="btn btn-brand btn-lg w-100 mt-3 d-none d-lg-block"><i class="bi bi-check2 me-1"></i>Save sale</button>
          <a href="<?= url('/sales') ?>" class="btn btn-link w-100 text-muted d-none d-lg-block">Cancel</a>
          <div class="small text-muted text-center">Totals are recalculated by the server.</div>
        </div>
      </aside>
    </div>
  </div>

  <!-- phone: sticky total + save -->
  <div class="sale-bar d-lg-none">
    <div class="min-w-0"><div class="small text-muted">Grand total</div><div class="fw-bold fs-5 text-truncate" id="bar-total">0.00</div><div class="small text-success text-truncate" id="bar-sub"></div></div>
    <button class="btn btn-brand btn-lg px-4"><i class="bi bi-check2 me-1"></i>Save</button>
  </div>
</form>
