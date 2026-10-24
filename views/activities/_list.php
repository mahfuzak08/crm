<div class="table-responsive"><table class="table table-hover">
  <thead><tr><th>Date</th><th>Type</th><th>Lead / Customer</th><th>Description</th><th>Follow-up</th><th>By</th></tr></thead>
  <tbody>
  <?php foreach ($p['rows'] as $a): ?>
    <tr>
      <td class="small text-nowrap"><?= e(fdate($a['activity_at'], true)) ?></td>
      <td><?= status_badge($a['type_name'], $a['type_color']) ?></td>
      <td class="small"><?php if ($a['lead_id']): ?><a href="<?= url('/leads/' . $a['lead_id']) ?>"><?= e($a['lead_name']) ?></a> <span class="text-muted">lead</span>
        <?php elseif ($a['customer_id']): ?><a href="<?= url('/customers/' . $a['customer_id']) ?>"><?= e($a['customer_name']) ?></a> <span class="text-muted">customer</span><?php endif; ?></td>
      <td class="small" style="max-width:420px"><?= e(mb_strimwidth((string) $a['description'], 0, 180, '…')) ?></td>
      <td class="small text-nowrap"><?= e(fdate($a['follow_up_date'])) ?></td>
      <td class="small text-muted"><?= e($a['user_name'] ?? 'System') ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$p['rows']) echo empty_row(6, 'No activities found.'); ?>
  </tbody>
</table></div>
<?= partial('partials/pagination', ['p' => $p]) ?>
