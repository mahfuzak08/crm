<div class="px-3 py-2 small border-bottom">Total approved (filtered): <strong><?= money($total) ?></strong></div>
<div class="table-responsive"><table class="table table-hover">
  <thead><tr><th><?= sort_link('number', 'Number') ?></th><th><?= sort_link('date', 'Date') ?></th><th>Category</th><th>Description</th><th>Method</th><th class="text-end"><?= sort_link('amount', 'Amount') ?></th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($p['rows'] as $x): ?>
    <tr>
      <td class="small text-nowrap"><a href="<?= url('/expenses/' . $x['id']) ?>"><?= e($x['expense_number']) ?></a></td>
      <td class="small text-nowrap"><?= e(fdate($x['expense_date'])) ?></td>
      <td><?= e($x['category_name']) ?><div class="small text-muted"><?= e($x['project_name']) ?></div></td>
      <td class="small" style="max-width:300px"><?= e(mb_strimwidth((string) $x['description'], 0, 90, '…')) ?></td>
      <td class="small"><?= e($x['method_name']) ?></td>
      <td class="text-end text-nowrap fw-medium"><?= money($x['amount']) ?></td>
      <td><?= status_badge($x['status']) ?></td>
      <td class="actions"><?php if ($x['attachment']): ?><a href="<?= url('/expenses/' . $x['id'] . '/attachment') ?>" target="_blank" class="btn btn-sm btn-light" title="Attachment"><i class="bi bi-paperclip"></i></a><?php endif; ?>
        <a href="<?= url('/expenses/' . $x['id'] . '/voucher') ?>" target="_blank" class="btn btn-sm btn-light" title="Voucher"><i class="bi bi-printer"></i></a></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$p['rows']) echo empty_row(8, 'No expenses.'); ?>
  </tbody>
</table></div>
<?= partial('partials/pagination', ['p' => $p]) ?>
