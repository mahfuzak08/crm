<?php App\Core\View::start('actions'); ?>
<?php if (can('directors.manage')): ?><a href="<?= url('/directors/create') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i>Add Director</a><?php endif; ?>
<?php App\Core\View::end(); ?>
<div class="card"><div class="table-responsive"><table class="table table-hover">
  <thead><tr><th>Director</th><th>Designation</th><th>Contact</th><th class="text-end">Own shares</th><th class="text-end">Referred shares</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($directors as $d): ?>
    <tr>
      <td><a href="<?= url('/directors/' . $d['id']) ?>" class="fw-medium"><?= e($d['name']) ?></a></td>
      <td class="small"><?= e($d['designation'] ?? '—') ?></td>
      <td class="small"><?= e($d['phone']) ?><div class="text-muted"><?= e($d['email']) ?></div></td>
      <td class="text-end"><?= number_format((int) $d['own_shares']) ?></td>
      <td class="text-end"><?= number_format((int) $d['referred_shares']) ?></td>
      <td><?= status_badge($d['status']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$directors) echo empty_row(6, 'No directors yet. Add one from an existing customer or employee.'); ?>
  </tbody>
</table></div></div>
