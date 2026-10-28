<div class="m-list d-md-none">
  <?php foreach ($p['rows'] as $c): ?>
    <div class="m-item">
      <a href="<?= url('/customers/' . $c['id']) ?>" class="m-main">
        <span class="avatar"><?= e(initials($c['name'])) ?></span>
        <span class="m-body">
          <span class="m-title"><?= e($c['name']) ?></span>
          <span class="m-sub"><i class="bi bi-hash"></i><?= e($c['customer_code']) ?><?= $c['type_name'] ? ' · ' . e($c['type_name']) : '' ?></span>
          <span class="m-sub"><i class="bi bi-telephone"></i><?= e($c['phone'] ?: ($c['email'] ?: '—')) ?></span>
        </span>
        <span class="m-side">
          <span class="m-meta"><i class="bi bi-grid-3x3-gap"></i> <?= number_format((int) $c['shares']) ?> share<?= (int) $c['shares'] === 1 ? '' : 's' ?></span>
          <?php if ((float) $c['due'] > 0): ?><span class="m-meta text-danger fw-semibold">Due <?= money($c['due']) ?></span><?php endif; ?>
        </span>
      </a>
      <?php if ($c['phone']): ?><a class="m-call" href="tel:<?= e(phone_digits($c['phone'])) ?>" aria-label="Call <?= e($c['name']) ?>"><i class="bi bi-telephone-fill"></i></a><?php endif; ?>
    </div>
  <?php endforeach; ?>
  <?php if (!$p['rows']): ?><div class="empty"><i class="bi bi-person-vcard"></i>No customers found.</div><?php endif; ?>
</div>
<div class="table-responsive d-none d-md-block"><table class="table table-hover">
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
