<div class="table-responsive"><table class="table table-hover">
  <thead><tr><th><?= sort_link('number', 'Invoice') ?></th><th><?= sort_link('date', 'Date') ?></th><th>Customer</th><th><?= sort_link('due_date', 'Due date') ?></th><th class="text-end"><?= sort_link('total', 'Total') ?></th><th class="text-end">Paid</th><th class="text-end"><?= sort_link('due', 'Due') ?></th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($p['rows'] as $i): ?>
    <tr>
      <td class="text-nowrap"><a href="<?= url('/invoices/' . $i['id']) ?>" class="fw-medium"><?= e($i['invoice_number']) ?></a></td>
      <td class="small text-nowrap"><?= e(fdate($i['invoice_date'])) ?></td>
      <td><a href="<?= url('/customers/' . $i['customer_id']) ?>" class="text-reset"><?= e($i['customer_name']) ?></a><div class="small text-muted"><?= e($i['project_name']) ?></div></td>
      <td class="small text-nowrap"><?= e(fdate($i['due_date'])) ?></td>
      <td class="text-end text-nowrap"><?= money($i['grand_total']) ?></td>
      <td class="text-end text-nowrap text-success"><?= money($i['paid_amount']) ?></td>
      <td class="text-end text-nowrap<?= (float) $i['due_amount'] > 0 ? ' text-danger fw-semibold' : '' ?>"><?= money($i['due_amount']) ?></td>
      <td><?= status_badge($i['status']) ?></td>
      <td class="actions">
        <?php if (can('invoices.print')): ?><a href="<?= url('/invoices/' . $i['id'] . '/print') ?>" target="_blank" class="btn btn-sm btn-light" title="Print"><i class="bi bi-printer"></i></a><?php endif; ?>
        <?php if (in_array($i['status'], ['unpaid', 'partial', 'overdue'], true) && can('payments.create')): ?><a href="<?= url('/payments/create', ['invoice' => $i['id']]) ?>" class="btn btn-sm btn-light text-success" title="Receive payment"><i class="bi bi-cash"></i></a><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$p['rows']) echo empty_row(9, 'No invoices found.'); ?>
  </tbody>
</table></div>
<?= partial('partials/pagination', ['p' => $p]) ?>
