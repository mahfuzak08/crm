<div class="table-responsive"><table class="table table-hover">
  <thead><tr><th><?= sort_link('code', 'Code') ?></th><th><?= sort_link('name', 'Lead') ?></th><th>Contact</th><th><?= sort_link('status', 'Status') ?></th><th>Source / Project</th><th>Assigned</th><th><?= sort_link('followup', 'Follow-up') ?></th><th><?= sort_link('created', 'Created') ?></th></tr></thead>
  <tbody>
  <?php foreach ($p['rows'] as $l):
      $overdue = $l['follow_up_date'] && $l['follow_up_date'] < today() && !in_array($l['status_code'], ['converted', 'lost'], true); ?>
    <tr>
      <td class="text-nowrap small"><a href="<?= url('/leads/' . $l['id']) ?>"><?= e($l['lead_code']) ?></a></td>
      <td><a href="<?= url('/leads/' . $l['id']) ?>" class="fw-medium text-reset"><?= e($l['name']) ?></a></td>
      <td class="small text-nowrap"><?php if ($l['phone']): ?><a href="tel:<?= e(phone_digits($l['phone'])) ?>"><?= e($l['phone']) ?></a><br><?php endif; ?><span class="text-muted"><?= e($l['email']) ?></span></td>
      <td><?= status_badge($l['status_name'], $l['status_color']) ?></td>
      <td class="small"><?= e($l['source_name']) ?><br><span class="text-muted"><?= e($l['project_name']) ?></span></td>
      <td class="small"><?= e($l['assigned_name'] ?? '—') ?></td>
      <td class="small text-nowrap<?= $overdue ? ' text-danger fw-semibold' : '' ?>"><?= e(fdate($l['follow_up_date'])) ?></td>
      <td class="small text-nowrap text-muted"><?= e(fdate($l['created_at'])) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$p['rows']) echo empty_row(8, 'No leads found.'); ?>
  </tbody>
</table></div>
<?= partial('partials/pagination', ['p' => $p]) ?>
