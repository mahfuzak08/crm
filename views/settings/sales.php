<?php
$ro = !can('settings.edit');
$old = App\Core\Session::getFlash('_old');
$v = static fn(string $form, string $key) => $old[$form] ?? $s[$key];
?>
<form method="post" action="<?= url('/settings/sales') ?>">
  <?= csrf_field() ?>
  <fieldset<?= $ro ? ' disabled' : '' ?>>
  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card h-100"><div class="card-header">Share sales</div><div class="card-body"><div class="row g-3">
        <?= form_input('sales_max_discount_percent', 'Max discount without approval (%)', null, ['value' => $v('sales_max_discount_percent', 'sales.max_discount_percent'), 'help' => 'Users with "Give discount above company limit" permission can exceed this.']) ?>
        <?= form_check('sales_allow_fee_override', 'Allow editing registration/development fees on sales', ['sales_allow_fee_override' => $s['sales.allow_fee_override']], ['help' => 'When off, fees are always project fee × shares (calculated by the server).']) ?>
        <?= form_check('sales_auto_invoice', 'Automatically create an invoice when a sale is confirmed', ['sales_auto_invoice' => $s['sales.auto_invoice']]) ?>
        <?= form_textarea('agreement_terms', 'Share purchase agreement terms', null, ['value' => $v('agreement_terms', 'agreement.terms'), 'rows' => 6]) ?>
      </div></div></div>
    </div>
    <div class="col-lg-6">
      <div class="card mb-3"><div class="card-header">Offers</div><div class="card-body"><div class="row g-3">
        <?= form_input('offer_valid_days', 'Default validity (days)', null, ['value' => $v('offer_valid_days', 'offer.valid_days'), 'type' => 'number']) ?>
        <?= form_textarea('offer_terms', 'Default offer terms', null, ['value' => $v('offer_terms', 'offer.terms'), 'rows' => 4]) ?>
      </div></div></div>
      <div class="card"><div class="card-header">Expenses</div><div class="card-body"><div class="row g-3">
        <?= form_check('expense_require_approval', 'New expenses require approval', ['expense_require_approval' => $s['expense.require_approval']], ['help' => 'Pending expenses are excluded from totals until approved.']) ?>
      </div></div></div>
    </div>
  </div>
  <?php if (!$ro): ?><div class="mt-3"><button class="btn btn-brand"><i class="bi bi-check2 me-1"></i>Save settings</button></div><?php endif; ?>
  </fieldset>
</form>
