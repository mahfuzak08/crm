<?php App\Core\View::start('actions'); ?>
<?php if (can('directors.manage')): ?><a href="<?= url('/directors/create') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i>Add Director</a><?php endif; ?>
<?php App\Core\View::end(); ?>
<div class="card"><div class="table-responsive"><table class="table table-hover">
  <thead><tr><th>Director</th><th>Designation</th><th>Contact</th><th>From</th><th class="text-end">Own shares</th><th class="text-end">Referred shares</th><?php if (can('sales.view')): ?><th class="text-end">Referral profit</th><?php endif; ?><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($directors as $d): ?>
    <tr>
      <td><a href="<?= url('/directors/' . $d['id']) ?>" class="fw-medium"><?= e($d['name']) ?></a></td>
      <td class="small"><?= e($d['designation'] ?? '—') ?></td>
      <td class="small"><?= e($d['phone']) ?><div class="text-muted"><?= e($d['email']) ?></div></td>
      <td class="small">
        <?php if ($d['customer_id']): ?><a href="<?= url('/customers/' . $d['customer_id']) ?>">Customer <?= e($d['customer_code']) ?></a><br><?php endif; ?>
        <?php if ($d['employee_id']): ?><a href="<?= url('/employees/' . $d['employee_id']) ?>">Employee <?= e($d['employee_code']) ?></a><?php endif; ?>
        <?php if (!$d['customer_id'] && !$d['employee_id']): ?><span class="text-muted">User account</span><?php endif; ?>
      </td>
      <td class="text-end"><?= number_format((int) $d['own_shares']) ?></td>
      <td class="text-end"><?= number_format((int) $d['referred_shares']) ?></td>
      <?php if (can('sales.view')): ?><td class="text-end text-nowrap"><?= money($d['referral_profit']) ?></td><?php endif; ?>
      <td><?= status_badge($d['status']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$directors) echo empty_row(8, 'No directors yet. Add one from an existing customer or employee.'); ?>
  </tbody>
</table></div></div>
