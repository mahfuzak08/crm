<?php
$ro = !can('settings.edit');
$tz = DateTimeZone::listIdentifiers();
$dfOpts = [];
foreach ($dateFormats as $fmt => $example) {
    $dfOpts[$fmt] = $example;
}
?>
<form method="post" action="<?= url('/settings/company') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <fieldset<?= $ro ? ' disabled' : '' ?>>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card mb-3"><div class="card-header">Company profile</div><div class="card-body"><div class="row g-3">
        <?= form_input('name', 'Company name', $company, ['required' => true, 'col' => 'col-md-8']) ?>
        <?= form_input('code', 'Short code', $company, ['col' => 'col-md-4', 'help' => 'Optional, unique']) ?>
        <?= form_input('phone', 'Phone', $company) ?>
        <?= form_input('email', 'Email', $company, ['type' => 'email']) ?>
        <?= form_input('website', 'Website', $company, ['placeholder' => 'https://']) ?>
        <?= form_input('tax_info', 'Tax / VAT / BIN information', $company) ?>
        <?= form_textarea('address', 'Address', $company, ['rows' => 2]) ?>
      </div></div></div>
      <div class="card"><div class="card-header">Documents</div><div class="card-body"><div class="row g-3">
        <?= form_textarea('invoice_footer', 'Invoice footer', $company, ['rows' => 2, 'help' => 'Shown at the bottom of printed invoices and offers.']) ?>
        <?= form_textarea('receipt_footer', 'Receipt footer', $company, ['rows' => 2, 'help' => 'Shown at the bottom of payment receipts.']) ?>
      </div></div></div>
    </div>
    <div class="col-lg-4">
      <div class="card mb-3"><div class="card-header">Logo</div><div class="card-body">
        <?php if (!empty($company['logo'])): ?>
          <img src="<?= url('/media/logo/' . (int) $company['id']) ?>?t=<?= time() ?>" alt="Logo" class="img-fluid border rounded p-2 mb-2" style="max-height:110px">
          <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="rmlogo"><label class="form-check-label small" for="rmlogo">Remove logo</label></div>
        <?php endif; ?>
        <input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp" class="form-control<?= invalid('logo') ?>"><?= error_for('logo') ?>
        <div class="form-text">JPG, PNG or WEBP, max 2 MB.</div>
      </div></div>
      <div class="card"><div class="card-header">Regional &amp; theme</div><div class="card-body"><div class="row g-3">
        <?= form_input('currency', 'Currency code', $company, ['col' => 'col-6']) ?>
        <?= form_input('currency_symbol', 'Symbol', $company, ['col' => 'col-6']) ?>
        <?= form_select('timezone', 'Timezone', array_combine($tz, $tz), $company, ['col' => 'col-12']) ?>
        <?= form_select('date_format', 'Date format', $dfOpts, $company, ['col' => 'col-12']) ?>
        <?= form_input('theme_color', 'Theme colour', $company, ['type' => 'color', 'col' => 'col-12', 'attrs' => ['class' => 'form-control form-control-color w-100']]) ?>
      </div></div></div>
    </div>
  </div>
  <?php if (!$ro): ?><div class="mt-3"><button class="btn btn-brand"><i class="bi bi-check2 me-1"></i>Save settings</button></div><?php endif; ?>
  </fieldset>
</form>
