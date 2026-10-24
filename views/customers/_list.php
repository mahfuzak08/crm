<div class="table-responsive"><table class="table table-hover">
  <thead><tr><th><?= sort_link('code', 'Code') ?></th><th><?= sort_link('name', 'Customer') ?></th><th>Contact</th><th>Type</th><th>Location</th><th class="text-end">Shares</th><th class="text-end"><?= sort_link('due', 'Due') ?></th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($p['rows'] as $c): ?>
    <tr>
      <td class="small text-nowrap"><a href="<?= url('/customers/' . $c['id']) ?>"><?= e($c['customer_code']) ?></a></td>
      <td><a href="<?= url('/customers/' . $c['id']) ?>" class="fw-medium text-reset"><?= e($c['name']) ?></a></td>
      <td class="small text-nowrap"><?= e($c['phone']) ?><br><span class="text-muted"><?= e($c['email']) ?></span></td>
      <td class="small"><?= e($c['type_name']) ?></td>
      <td class="small"><?= e(implode(', ', array_filter([$c['city'], $c['district']]))) ?></td>
      <td class="text-end"><?= number_format((int) $c['shares']) ?></td>
      <td class="text-end text-nowrap<?= (float) $c['due'] > 0 ? ' text-danger fw-semibold' : '' ?>"><?= money($c['due']) ?></td>
      <td><?= status_badge($c['status']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$p['rows']) echo empty_row(8, 'No customers found.'); ?>
  </tbody>
</table></div>
<?= partial('partials/pagination', ['p' => $p]) ?>
