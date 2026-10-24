<?php
$old = App\Core\Session::getFlash('_old');
$sel = array_map('intval', (array) ($old['permissions'] ?? $selected));
$dis = $readonly ? ' disabled' : '';
?>
<form method="post" action="<?= url($role ? '/roles/' . $role['id'] : '/roles') ?>">
  <?= csrf_field() ?>
  <?php if ($readonly): ?>
    <div class="alert alert-info small"><?= $role && $role['slug'] === 'super_admin' ? 'Super Admin always has full access to everything.' : 'This role is read-only for you.' ?></div>
  <?php endif; ?>
  <div class="card mb-3"><div class="card-body"><div class="row g-3">
    <?= form_input('name', 'Role name', $role, ['required' => true, 'attrs' => ['disabled' => (bool) $readonly]]) ?>
    <?= form_input('description', 'Description', $role, ['attrs' => ['disabled' => (bool) $readonly]]) ?>
  </div></div></div>

  <div class="card">
    <div class="card-header">Permissions
      <?php if (!$readonly): ?><span><button type="button" class="btn btn-sm btn-light" id="permAll">Select all</button> <button type="button" class="btn btn-sm btn-light" id="permNone">Clear</button></span><?php endif; ?>
    </div>
    <div class="card-body">
      <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
      <?php foreach ($modules as $module => $perms): ?>
        <div class="col"><div class="border rounded p-2 h-100">
          <div class="fw-semibold text-capitalize small mb-1"><?= e(str_replace('_', ' ', $module)) ?></div>
          <?php foreach ($perms as $p): ?>
            <div class="form-check">
              <input class="form-check-input perm" type="checkbox" name="permissions[]" value="<?= (int) $p['id'] ?>" id="p<?= (int) $p['id'] ?>"<?= ($role && $role['slug'] === 'super_admin') || in_array((int) $p['id'], $sel, true) ? ' checked' : '' ?><?= $dis ?>>
              <label class="form-check-label small" for="p<?= (int) $p['id'] ?>" title="<?= e($p['name']) ?>"><?= e($p['label']) ?></label>
            </div>
          <?php endforeach; ?>
        </div></div>
      <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="mt-3 d-flex gap-2">
    <?php if (!$readonly): ?><button class="btn btn-brand"><i class="bi bi-check2 me-1"></i>Save role</button><?php endif; ?>
    <a href="<?= url('/roles') ?>" class="btn btn-light">Back</a>
  </div>
</form>
