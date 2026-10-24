<form method="post" action="<?= url($e ? '/employees/' . $e['id'] : '/employees') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card mb-3"><div class="card-header">Employee</div><div class="card-body"><div class="row g-3">
        <?= form_input('name', 'Full name', $e, ['required' => true, 'col' => 'col-md-8']) ?>
        <?= form_select('status', 'Status', ['active' => 'Active', 'inactive' => 'Inactive', 'resigned' => 'Resigned'], $e, ['col' => 'col-md-4']) ?>
        <?= form_input('phone', 'Phone', $e, ['col' => 'col-md-6']) ?>
        <?= form_input('email', 'Email', $e, ['type' => 'email', 'col' => 'col-md-6']) ?>
        <?= form_select('department_id', 'Department', $departments, $e, ['empty' => '—', 'col' => 'col-md-4']) ?>
        <?= form_select('designation_id', 'Designation', $designations, $e, ['empty' => '—', 'col' => 'col-md-4']) ?>
        <?= form_input('joining_date', 'Joining date', $e, ['type' => 'date', 'col' => 'col-md-4']) ?>
        <?= form_input('salary', 'Basic salary (monthly)', $e, ['type' => 'money', 'required' => true, 'col' => 'col-md-6', 'default' => '0.00']) ?>
        <?= form_select('user_id', 'Linked system user', $users, $e, ['empty' => '— none —', 'col' => 'col-md-6']) ?>
        <?= form_textarea('address', 'Address', $e, ['rows' => 2]) ?>
        <?= form_textarea('notes', 'Notes', $e, ['rows' => 2]) ?>
        <div class="col-md-6"><label class="form-label">Photo</label><input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp" class="form-control<?= invalid('photo') ?>"><?= error_for('photo') ?></div>
      </div></div></div>
    </div>
    <?php if ($components): ?>
    <div class="col-lg-4">
      <div class="card"><div class="card-header">Salary structure</div><div class="card-body">
        <p class="small text-muted">Leave blank to use the component default.</p>
        <?php foreach ($components as $c): ?>
          <div class="mb-2">
            <label class="form-label small mb-0"><?= e($c['name']) ?> <span class="badge text-bg-light text-dark"><?= e($c['type']) ?></span></label>
            <div class="input-group input-group-sm">
              <input name="component[<?= (int) $c['id'] ?>]" class="form-control" inputmode="decimal" value="<?= e($structure[$c['id']] ?? '') ?>" placeholder="<?= e($c['default_value']) ?>">
              <span class="input-group-text"><?= $c['calc_type'] === 'percent' ? '% of basic' : 'fixed' ?></span>
            </div>
          </div>
        <?php endforeach; ?>
        <?= error_for('component') ?>
      </div></div>
    </div>
    <?php endif; ?>
  </div>
  <div class="mt-3 d-flex gap-2"><button class="btn btn-brand"><i class="bi bi-check2 me-1"></i>Save employee</button><a href="<?= url($e ? '/employees/' . $e['id'] : '/employees') ?>" class="btn btn-light">Cancel</a></div>
</form>
