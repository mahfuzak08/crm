<div class="table-responsive"><table class="table table-hover">
  <thead><tr><th><?= sort_link('number', 'Sale') ?></th><th><?= sort_link('date', 'Date') ?></th><th>Customer</th><th>Project / Package</th><th class="text-end"><?= sort_link('shares', 'Shares') ?></th><th class="text-end"><?= sort_link('total', 'Total') ?></th><th class="text-end">Due</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($p['rows'] as $s): ?>
    <tr>
      <td class="text-nowrap"><a href="<?= url('/sales/' . $s['id']) ?>" class="fw-medium"><?= e($s['sale_number']) ?></a></td>
      <td class="small text-nowrap"><?= e(fdate($s['sale_date'])) ?></td>
      <td><a href="<?= url('/customers/' . $s['customer_id']) ?>" class="text-reset"><?= e($s['customer_name']) ?></a>
        <?php if ($s['director_id']): ?><div class="small text-muted">via <?= e($s['director_name']) ?> · customer price <?= money($s['customer_total']) ?></div><?php endif; ?></td>
      <td class="small"><?= e($s['project_name']) ?><div class="text-muted"><?= e($s['package_name'] ?? 'Per share') ?></div></td>
      <td class="text-end"><?= (int) $s['shares'] ?></td>
      <td class="text-end text-nowrap"><?= money($s['grand_total']) ?></td>
      <td class="text-end text-nowrap<?= (float) $s['due_amount'] > 0 ? ' text-danger' : '' ?>"><?= money($s['due_amount']) ?></td>
      <td><?= status_badge($s['status']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$p['rows']) echo empty_row(8, 'No sales found.'); ?>
  </tbody>
</table></div>
<?= partial('partials/pagination', ['p' => $p]) ?>
