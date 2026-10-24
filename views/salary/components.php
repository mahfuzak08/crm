<div class="row g-3">
  <div class="col-lg-8"><div class="card"><div class="table-responsive"><table class="table table-hover">
    <thead><tr><th>Component</th><th>Type</th><th>Calculation</th><th class="text-end">Default</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($items as $c): ?>
      <tr><td class="fw-medium"><?= e($c['name']) ?></td><td><?= e(App\Services\SalaryService::TYPES[$c['type']]) ?></td><td><?= $c['calc_type'] === 'percent' ? '% of basic' : 'Fixed' ?></td>
        <td class="text-end"><?= $c['calc_type'] === 'percent' ? e((string) (float) $c['default_value']) . '%' : money($c['default_value']) ?></td>
        <td><?= $c['is_active'] ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Disabled</span>' ?></td>
        <td class="actions"><?php if (can('salary.manage')): ?><a class="btn btn-sm btn-light" href="<?= url('/salary/components', ['edit' => $c['id']]) ?>"><i class="bi bi-pencil"></i></a><?= post_button('/salary/components/' . $c['id'] . '/delete', '', ['html' => '<i class="bi bi-trash"></i>', 'confirm' => 'Delete component? Employee overrides for it are removed too.']) ?><?php endif; ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$items) echo empty_row(6); ?>
  </tbody></table></div></div></div>
  <?php if (can('salary.manage')): ?>
  <div class="col-lg-4"><div class="card"><div class="card-header"><?= $editing ? 'Edit component' : 'Add component' ?></div><div class="card-body">
    <form method="post" action="<?= url('/salary/components' . ($editing ? '/' . $editing['id'] : '')) ?>">
      <?= csrf_field() ?>
      <div class="row g-3">
        <?= form_input('name', 'Name', $editing, ['required' => true, 'col' => 'col-12']) ?>
        <?= form_select('type', 'Type', App\Services\SalaryService::TYPES, $editing, ['col' => 'col-6']) ?>
        <?= form_select('calc_type', 'Calculation', ['fixed' => 'Fixed amount', 'percent' => '% of basic'], $editing, ['col' => 'col-6']) ?>
        <?= form_input('default_value', 'Default value', $editing, ['type' => 'money', 'required' => true, 'col' => 'col-6', 'default' => '0.00']) ?>
        <?= form_input('sort_order', 'Order', $editing, ['type' => 'number', 'col' => 'col-6']) ?>
        <?= form_check('is_active', 'Active', $editing, ['default' => true]) ?>
      </div>
      <div class="mt-3 d-flex gap-2"><button class="btn btn-brand"><?= $editing ? 'Update' : 'Add' ?></button><?php if ($editing): ?><a class="btn btn-light" href="<?= url('/salary/components') ?>">Cancel</a><?php endif; ?></div>
    </form>
  </div></div></div>
  <?php endif; ?>
</div>
