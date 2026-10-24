<div class="px-3 py-2 small border-bottom">Total collected (confirmed, filtered): <strong><?= money($total) ?></strong></div>
<div class="table-responsive"><table class="table table-hover">
  <thead><tr><th><?= sort_link('number', 'Receipt') ?></th><th><?= sort_link('date', 'Date') ?></th><th>Customer</th><th>Invoice</th><th>Method</th><th>Reference</th><th class="text-end"><?= sort_link('amount', 'Amount') ?></th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($p['rows'] as $r): ?>
    <tr>
      <td class="text-nowrap"><a href="<?= url('/payments/' . $r['id']) ?>" class="fw-medium"><?= e($r['payment_number']) ?></a></td>
      <td class="small text-nowrap"><?= e(fdate($r['payment_date'])) ?></td>
      <td><a href="<?= url('/customers/' . $r['customer_id']) ?>" class="text-reset"><?= e($r['customer_name']) ?></a></td>
      <td class="small"><a href="<?= url('/invoices/' . $r['invoice_id']) ?>"><?= e($r['invoice_number']) ?></a></td>
      <td class="small"><?= e($r['method_name']) ?></td>
      <td class="small"><?= e($r['reference']) ?></td>
      <td class="text-end text-nowrap fw-medium<?= $r['status'] === 'cancelled' ? ' text-decoration-line-through text-muted' : '' ?>"><?= money($r['amount']) ?></td>
      <td><?= status_badge($r['status']) ?></td>
      <td class="actions"><a href="<?= url('/payments/' . $r['id'] . '/receipt') ?>" target="_blank" class="btn btn-sm btn-light" title="Receipt"><i class="bi bi-printer"></i></a></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$p['rows']) echo empty_row(9, 'No payments found.'); ?>
  </tbody>
</table></div>
<?= partial('partials/pagination', ['p' => $p]) ?>
