<div class="m-list d-md-none">
  <?php foreach ($p['rows'] as $s): ?>
    <div class="m-item">
      <a href="<?= url('/sales/' . $s['id']) ?>" class="m-main">
        <span class="avatar m-icon"><i class="bi bi-cart-check"></i></span>
        <span class="m-body">
          <span class="m-title"><?= e($s['customer_name']) ?></span>
          <span class="m-sub"><i class="bi bi-hash"></i><?= e($s['sale_number']) ?> · <?= e(fdate($s['sale_date'])) ?></span>
          <span class="m-sub"><i class="bi bi-building"></i><?= (int) $s['shares'] ?> shares · <?= e($s['package_name'] ?? 'Per share') ?></span>
          <?php if ($s['director_id']): ?><span class="m-sub"><i class="bi bi-person-up"></i>via <?= e($s['director_name']) ?></span><?php endif; ?>
        </span>
        <span class="m-side">
          <span class="m-amount"><?= money($s['grand_total']) ?></span>
          <?php if ((float) $s['due_amount'] > 0): ?><span class="m-meta text-danger">Due <?= money($s['due_amount']) ?></span><?php endif; ?>
          <?= status_badge($s['status']) ?>
        </span>
      </a>
    </div>
  <?php endforeach; ?>
  <?php if (!$p['rows']): ?><div class="empty"><i class="bi bi-cart"></i>No sales found.</div><?php endif; ?>
</div>
<div class="table-responsive d-none d-md-block"><table class="table table-hover">
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
