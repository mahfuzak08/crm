<div class="table-responsive">
<table class="table table-hover">
  <thead><tr>
    <th><?= sort_link('name', 'Name') ?></th><th>Username</th><th>Contact</th><th>Roles</th><th>Status</th><th><?= sort_link('last_login', 'Last login') ?></th><th></th>
  </tr></thead>
  <tbody>
  <?php foreach ($p['rows'] as $u): ?>
    <tr>
      <td><div class="d-flex align-items-center gap-2"><span class="avatar"><?= e(initials($u['name'])) ?></span><a href="<?= url('/users/' . $u['id']) ?>" class="fw-medium"><?= e($u['name']) ?></a></div></td>
      <td class="text-muted">@<?= e($u['username']) ?></td>
      <td class="small"><?= e($u['email']) ?><br><span class="text-muted"><?= e($u['phone']) ?></span></td>
      <td class="small"><?= e($u['roles']) ?></td>
      <td><?= status_badge($u['status']) ?></td>
      <td class="small text-nowrap"><?= $u['last_login_at'] ? e(fdate($u['last_login_at'], true)) : '<span class="text-muted">Never</span>' ?></td>
      <td class="actions">
        <?php if (can('users.edit')): ?><a href="<?= url('/users/' . $u['id'] . '/edit') ?>" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil"></i></a><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$p['rows']) echo empty_row(7); ?>
  </tbody>
</table>
</div>
<?= partial('partials/pagination', ['p' => $p]) ?>
