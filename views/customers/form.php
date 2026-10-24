<form method="post" action="<?= url($c ? '/customers/' . $c['id'] : '/customers') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card mb-3"><div class="card-header">Personal information</div><div class="card-body"><div class="row g-3">
        <?= form_input('name', 'Full name', $c, ['required' => true, 'col' => 'col-md-8']) ?>
        <?= form_select('customer_type_id', 'Customer type', $types, $c, ['empty' => '—', 'col' => 'col-md-4']) ?>
        <?= form_input('father_name', "Father's name", $c, ['col' => 'col-md-4']) ?>
        <?= form_input('mother_name', "Mother's name", $c, ['col' => 'col-md-4']) ?>
        <?= form_input('spouse_name', 'Spouse name', $c, ['col' => 'col-md-4']) ?>
        <?= form_input('date_of_birth', 'Date of birth', $c, ['type' => 'date', 'col' => 'col-md-4']) ?>
        <?= form_input('occupation', 'Occupation', $c, ['col' => 'col-md-4']) ?>
        <?= form_select('status', 'Status', ['active' => 'Active', 'inactive' => 'Inactive'], $c, ['col' => 'col-md-4']) ?>
        <?= form_input('nid', 'NID number', $c, ['col' => 'col-md-6']) ?>
        <?= form_input('passport', 'Passport number', $c, ['col' => 'col-md-6']) ?>
      </div></div></div>
      <div class="card"><div class="card-header">Contact &amp; address</div><div class="card-body"><div class="row g-3">
        <?= form_input('phone', 'Phone', $c, ['col' => 'col-md-4']) ?>
        <?= form_input('alternate_phone', 'Alternate phone', $c, ['col' => 'col-md-4']) ?>
        <?= form_input('email', 'Email', $c, ['type' => 'email', 'col' => 'col-md-4']) ?>
        <?= form_textarea('address', 'Address', $c, ['rows' => 2]) ?>
        <?= form_input('city', 'City / Upazila', $c, ['col' => 'col-md-4']) ?>
        <?= form_input('district', 'District', $c, ['col' => 'col-md-4']) ?>
        <?= form_input('country', 'Country', $c, ['col' => 'col-md-4', 'default' => 'Bangladesh']) ?>
        <?= form_textarea('notes', 'Notes', $c, ['rows' => 3]) ?>
      </div></div></div>
    </div>
    <div class="col-lg-4">
      <div class="card"><div class="card-header">Photo &amp; NID</div><div class="card-body">
        <?php foreach (App\Services\CustomerService::FILE_FIELDS as $field => $label): ?>
          <div class="mb-3">
            <label class="form-label"><?= e($label) ?></label>
            <?php if (!empty($c[$field])): ?>
              <div class="d-flex align-items-center gap-2 mb-1">
                <img src="<?= url('/customers/' . $c['id'] . '/file/' . $field) ?>" alt="" class="rounded border" style="width:64px;height:48px;object-fit:cover">
                <div class="form-check small"><input class="form-check-input" type="checkbox" name="remove_<?= $field ?>" value="1" id="rm<?= $field ?>"><label class="form-check-label" for="rm<?= $field ?>">Remove</label></div>
              </div>
            <?php endif; ?>
            <input type="file" name="<?= $field ?>" accept=".jpg,.jpeg,.png,.webp" class="form-control form-control-sm<?= invalid($field) ?>"><?= error_for($field) ?>
          </div>
        <?php endforeach; ?>
        <div class="form-text">JPG/PNG/WEBP up to 5 MB. Stored privately.</div>
      </div></div>
    </div>
  </div>
  <div class="mt-3 d-flex gap-2"><button class="btn btn-brand"><i class="bi bi-check2 me-1"></i>Save customer</button>
    <a href="<?= url($c ? '/customers/' . $c['id'] : '/customers') ?>" class="btn btn-light">Cancel</a></div>
</form>
