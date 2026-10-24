<?php
$t = ['total' => 0, 'sold' => 0, 'reserved' => 0, 'available' => 0];
foreach ($rows as $r) {
    $t['total'] += (int) $r['total_shares']; $t['sold'] += (int) $r['sold']; $t['reserved'] += (int) $r['reserved'];
    $t['available'] += max(0, (int) $r['total_shares'] - (int) $r['sold'] - (int) $r['reserved']);
}
?>
<div class="row g-3 mb-3">
  <?php foreach ([['Total shares', $t['total'], 'slate'], ['Sold', $t['sold'], 'green'], ['Reserved', $t['reserved'], 'blue'], ['Available', $t['available'], 'amber']] as [$l, $v, $c]): ?>
  <div class="col-6 col-xl-3"><div class="card stat"><span class="stat-icon <?= $c ?>"><i class="bi bi-grid-3x3-gap"></i></span><div><div class="stat-label"><?= $l ?></div><div class="stat-value"><?= number_format($v) ?></div></div></div></div>
  <?php endforeach; ?>
</div>
<div class="card"><div class="card-header">Inventory by project</div>
<div class="table-responsive"><table class="table table-hover">
  <thead><tr><th>Project</th><th>Share unit</th><th class="text-end">Price</th><th class="text-end">Total</th><th class="text-end">Sold</th><th class="text-end">Reserved</th><th class="text-end">Cancelled</th><th class="text-end">Available</th><th class="text-end">Sales value</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): $avail = max(0, (int) $r['total_shares'] - (int) $r['sold'] - (int) $r['reserved']); ?>
    <tr>
      <td><a href="<?= url('/projects/' . $r['id']) ?>" class="fw-medium"><?= e($r['project_name']) ?></a><div class="small text-muted"><?= e($r['project_code']) ?></div></td>
      <td class="small"><?= e($r['share_unit']) ?></td>
      <td class="text-end text-nowrap"><?= money($r['share_price']) ?></td>
      <td class="text-end"><?= number_format((int) $r['total_shares']) ?></td>
      <td class="text-end"><?= number_format((int) $r['sold']) ?></td>
      <td class="text-end"><?= number_format((int) $r['reserved']) ?></td>
      <td class="text-end text-muted"><?= number_format((int) $r['cancelled']) ?></td>
      <td class="text-end fw-semibold text-success"><?= number_format($avail) ?></td>
      <td class="text-end text-nowrap"><?= money($r['sales_value']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows) echo empty_row(9, 'No projects yet.'); ?>
  </tbody>
</table></div></div>
