<?php App\Core\View::start('actions'); ?>
<a href="<?= url('/companies/create') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i>New Company</a>
<?php App\Core\View::end(); ?>
<div class="card"><div class="table-responsive"><table class="table table-hover">
  <thead><tr><th>Company</th><th>Contact</th><th>Currency</th><th class="text-end">Users</th><th class="text-end">Projects</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $c): ?>
    <tr>
      <td class="fw-medium"><?= e($c['name']) ?><?php if ((int) $c['id'] === tenant_id()): ?> <span class="badge text-bg-info">current</span><?php endif; ?><div class="small text-muted"><?= e($c['code']) ?></div></td>
      <td class="small"><?= e($c['email']) ?><br><span class="text-muted"><?= e($c['phone']) ?></span></td>
      <td><?= e($c['currency'] . ' ' . $c['currency_symbol']) ?></td>
      <td class="text-end"><?= (int) $c['users_count'] ?></td>
      <td class="text-end"><?= (int) $c['projects_count'] ?></td>
      <td><?= status_badge($c['status']) ?></td>
      <td class="actions">
        <a class="btn btn-sm btn-light" href="<?= url('/companies/' . $c['id'] . '/edit') ?>"><i class="bi bi-pencil"></i></a>
        <?php if ((int) $c['id'] !== tenant_id() && $c['status'] === 'active'): ?>
          <?= post_button('/company/switch', 'Switch', ['fields' => ['company_id' => $c['id']], 'class' => 'btn btn-sm btn-outline-brand']) ?>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
