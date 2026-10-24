<?php App\Core\View::start('actions'); ?>
<?php if (can('roles.create')): ?><a href="<?= url('/roles/create') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i>New Role</a><?php endif; ?>
<?php App\Core\View::end(); ?>
<div class="card">
  <div class="table-responsive"><table class="table table-hover">
    <thead><tr><th>Role</th><th>Description</th><th>Type</th><th class="text-end">Users</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($roles as $r): ?>
      <tr>
        <td class="fw-medium"><a href="<?= url('/roles/' . $r['id'] . '/edit') ?>"><?= e($r['name']) ?></a></td>
        <td class="small text-muted"><?= e($r['description']) ?></td>
        <td><?= $r['company_id'] ? '<span class="badge text-bg-info">Custom</span>' : '<span class="badge text-bg-light text-dark">System</span>' ?></td>
        <td class="text-end"><?= (int) $r['users_count'] ?></td>
        <td class="actions">
          <a href="<?= url('/roles/' . $r['id'] . '/edit') ?>" class="btn btn-sm btn-light"><i class="bi bi-shield-check"></i> Permissions</a>
          <?php if (can('roles.delete') && !$r['is_system']): ?>
            <?= post_button('/roles/' . $r['id'] . '/delete', '', ['html' => '<i class="bi bi-trash"></i>', 'confirm' => 'Delete this role?', 'title' => 'Delete']) ?>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
