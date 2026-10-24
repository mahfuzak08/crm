<form method="post" action="<?= url($company ? '/companies/' . $company['id'] : '/companies') ?>">
  <?= csrf_field() ?>
  <div class="card"><div class="card-body"><div class="row g-3">
    <?= form_input('name', 'Company name', $company, ['required' => true, 'col' => 'col-md-8']) ?>
    <?= form_input('code', 'Short code', $company, ['col' => 'col-md-4']) ?>
    <?= form_input('phone', 'Phone', $company) ?>
    <?= form_input('email', 'Email', $company, ['type' => 'email']) ?>
    <?= form_input('website', 'Website', $company) ?>
    <?= form_input('tax_info', 'Tax / VAT information', $company) ?>
    <?= form_input('currency', 'Currency code', $company, ['col' => 'col-md-3', 'default' => 'BDT']) ?>
    <?= form_input('currency_symbol', 'Symbol', $company, ['col' => 'col-md-3', 'default' => '৳']) ?>
    <?= form_input('timezone', 'Timezone', $company, ['col' => 'col-md-3', 'default' => 'Asia/Dhaka']) ?>
    <?= form_select('date_format', 'Date format', App\Services\CompanyService::DATE_FORMATS, $company, ['col' => 'col-md-3', 'default' => 'd M Y']) ?>
    <?php if ($company): ?><?= form_select('status', 'Status', ['active' => 'Active', 'inactive' => 'Inactive'], $company, ['col' => 'col-md-3']) ?><?php endif; ?>
    <?= form_textarea('address', 'Address', $company, ['rows' => 2]) ?>
  </div></div></div>
  <div class="mt-3 d-flex gap-2"><button class="btn btn-brand">Save</button><a href="<?= url('/companies') ?>" class="btn btn-light">Cancel</a></div>
</form>
