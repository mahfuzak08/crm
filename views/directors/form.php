<?php $src = fv($rec, 'source') ?: ($d ? ($d['employee_id'] ? 'employee' : 'customer') : 'customer'); ?>
<form method="post" action="<?= url($d ? '/directors/' . $d['id'] : '/directors') ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card"><div class="card-header">Director</div><div class="card-body"><div class="row g-3">
        <?php if ($d && ($d['customer_id'] || $d['employee_id'])): ?>
          <div class="col-12"><div class="fw-semibold fs-5"><?= e($d['name']) ?></div>
            <div class="small text-muted"><?= $d['customer_id'] ? 'Customer ' . e($d['customer_code']) : '' ?><?= $d['customer_id'] && $d['employee_id'] ? ' · ' : '' ?><?= $d['employee_id'] ? 'Employee ' . e($d['employee_code']) : '' ?></div></div>
        <?php else: ?>
          <div class="col-12">
            <label class="form-label">Pick the director from</label>
            <div class="d-flex gap-3">
              <?php foreach (['customer' => 'Customer', 'employee' => 'Employee'] as $v => $l): ?>
                <div class="form-check"><input class="form-check-input" type="radio" name="source" id="src_<?= $v ?>" value="<?= $v ?>"<?= $src === $v ? ' checked' : '' ?>><label class="form-check-label" for="src_<?= $v ?>"><?= $l ?></label></div>
              <?php endforeach; ?>
            </div>
            <?php if ($d): ?><div class="form-text">Carried over from the user account <strong><?= e($d['name']) ?></strong>. Link the matching customer record so the director's own share purchases show here.</div><?php endif; ?>
            <?= error_for('source') ?>
          </div>
          <div class="col-12" data-show-when="source=customer">
            <label class="form-label" for="f_customer_id">Customer</label>
            <input type="search" class="form-control form-control-sm mb-1" placeholder="Search customers…" data-lookup="/api/lookup/customers" data-lookup-target="#f_customer_id">
            <select name="customer_id" id="f_customer_id" class="form-select<?= invalid('customer_id') ?>"><option value="">— select —</option>
              <?php foreach ($customers as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, fv($rec, 'customer_id')) ?>><?= e($n) ?></option><?php endforeach; ?></select>
            <?= error_for('customer_id') ?>
          </div>
          <?= form_select('employee_id', 'Employee', $employees, $rec, ['empty' => '— select —', 'col' => 'col-12', 'attrs' => ['data-show-when' => 'source=employee']]) ?>
        <?php endif; ?>
        <?= form_input('designation', 'Designation on the board', $rec, ['col' => 'col-md-6', 'placeholder' => 'Chairman / Managing Director / Director']) ?>
        <?= form_input('joined_on', 'Joined the board', $rec, ['type' => 'date', 'col' => 'col-md-3']) ?>
        <?= form_input('sort_order', 'Display order', $rec, ['type' => 'number', 'col' => 'col-md-3', 'default' => 0]) ?>
        <?= form_select('status', 'Status', ['active' => 'Active', 'inactive' => 'Inactive'], $rec, ['col' => 'col-md-6']) ?>
        <?= form_textarea('notes', 'Notes', $rec, ['rows' => 3]) ?>
      </div></div></div>
    </div>
    <div class="col-lg-4">
      <div class="card"><div class="card-body small text-muted">
        <p>A director is an existing <strong>customer</strong> or <strong>employee</strong>. Name, phone and email always come from that record.</p>
        <p class="mb-0">When a director buys for themselves, sell to their customer record as usual (package price). When a director refers a buyer, pick the director as <em>Referred by director</em> on the sale and enter the price they sold at: the office copy keeps the package price and the director's profit is tracked here.</p>
      </div></div>
    </div>
  </div>
  <div class="mt-3 d-flex gap-2"><button class="btn btn-brand"><i class="bi bi-check2 me-1"></i>Save director</button><a href="<?= url($d ? '/directors/' . $d['id'] : '/directors') ?>" class="btn btn-light">Cancel</a></div>
</form>
