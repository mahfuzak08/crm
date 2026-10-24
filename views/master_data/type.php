<div class="row g-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="table-responsive"><table class="table table-hover">
        <thead><tr><th>Name</th><?php if ($hasColor): ?><th>Colour</th><?php endif; ?><th>Order</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($items as $i): ?>
          <tr>
            <td class="fw-medium"><?= e($i['name']) ?> <?php if ($i['is_system']): ?><span class="badge text-bg-light text-dark" title="Used by the system: can be renamed but not deleted">system</span><?php endif; ?></td>
            <?php if ($hasColor): ?><td><?= status_badge($i['name'], $i['color']) ?></td><?php endif; ?>
            <td><?= (int) $i['sort_order'] ?></td>
            <td><?= $i['is_active'] ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Disabled</span>' ?></td>
            <td class="actions">
              <a class="btn btn-sm btn-light" href="<?= url('/master-data/' . $type, ['edit' => $i['id']]) ?>"><i class="bi bi-pencil"></i></a>
              <?php if (!$i['is_system']): ?><?= post_button('/master-data/' . $type . '/' . $i['id'] . '/delete', '', ['html' => '<i class="bi bi-trash"></i>', 'confirm' => 'Delete "' . $i['name'] . '"?']) ?><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$items) echo empty_row(5); ?>
        </tbody>
      </table></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header"><?= $editing ? 'Edit ' . e($singular) : 'Add ' . e($singular) ?></div>
      <div class="card-body">
        <form method="post" action="<?= url('/master-data/' . $type . ($editing ? '/' . $editing['id'] : '')) ?>">
          <?= csrf_field() ?>
          <div class="row g-3">
            <?= form_input('name', 'Name', $editing, ['required' => true, 'col' => 'col-12']) ?>
            <?php if ($hasColor): ?><?= form_input('color', 'Colour', $editing, ['type' => 'color', 'col' => 'col-6', 'default' => '#6c757d']) ?><?php endif; ?>
            <?= form_input('sort_order', 'Sort order', $editing, ['type' => 'number', 'col' => 'col-6', 'default' => (count($items) + 1) * 10]) ?>
            <?= form_check('is_active', 'Active', $editing, ['default' => true]) ?>
          </div>
          <div class="mt-3 d-flex gap-2">
            <button class="btn btn-brand"><?= $editing ? 'Update' : 'Add' ?></button>
            <?php if ($editing): ?><a href="<?= url('/master-data/' . $type) ?>" class="btn btn-light">Cancel</a><?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
