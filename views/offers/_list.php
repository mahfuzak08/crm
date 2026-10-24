<div class="table-responsive"><table class="table table-hover">
  <thead><tr><th><?= sort_link('number', 'Offer') ?></th><th>To</th><th>Project</th><th class="text-end">Shares</th><th class="text-end"><?= sort_link('total', 'Total') ?></th><th><?= sort_link('valid', 'Valid until') ?></th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($p['rows'] as $o): ?>
    <tr>
      <td class="text-nowrap"><a href="<?= url('/offers/' . $o['id']) ?>" class="fw-medium"><?= e($o['offer_number']) ?></a><div class="small text-muted"><?= e(fdate($o['created_at'])) ?></div></td>
      <td><?= e($o['recipient_name']) ?> <span class="badge text-bg-light text-dark"><?= e($o['recipient_type']) ?></span></td>
      <td class="small"><?= e($o['project_name']) ?></td>
      <td class="text-end"><?= (int) $o['shares'] ?></td>
      <td class="text-end text-nowrap fw-medium"><?= money($o['total']) ?></td>
      <td class="small text-nowrap"><?= e(fdate($o['valid_until'])) ?></td>
      <td><?= status_badge($o['status']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$p['rows']) echo empty_row(7, 'No offers found.'); ?>
  </tbody>
</table></div>
<?= partial('partials/pagination', ['p' => $p]) ?>
