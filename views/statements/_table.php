<?php $mm = static fn($v) => isset($company) ? money_c($v, $company) : money($v); ?>
<div class="table-responsive"><table class="table items">
  <thead><tr><th>Date</th><th>Reference</th><th>Description</th><th class="text-end r">Debit (invoice)</th><th class="text-end r">Credit (payment)</th><th class="text-end r">Balance</th></tr></thead>
  <tbody>
    <tr><td></td><td></td><td class="fw-semibold">Opening balance</td><td></td><td></td><td class="text-end r fw-semibold"><?= $mm($opening) ?></td></tr>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td class="text-nowrap"><?= e(fdate($r['d'])) ?></td>
        <td class="text-nowrap"><?= isset($company) ? e($r['ref']) : '<a href="' . url('/' . ($r['type'] === 'invoice' ? 'invoices' : 'payments') . '/' . $r['id']) . '">' . e($r['ref']) . '</a>' ?></td>
        <td><?= e($r['description']) ?></td>
        <td class="text-end r nowrap"><?= (float) $r['debit'] ? $mm($r['debit']) : '' ?></td>
        <td class="text-end r nowrap"><?= (float) $r['credit'] ? $mm($r['credit']) : '' ?></td>
        <td class="text-end r nowrap"><?= $mm($r['balance']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="6" class="text-muted text-center">No transactions in this period.</td></tr><?php endif; ?>
    <tr class="fw-semibold"><td colspan="3" class="text-end r">Totals</td><td class="text-end r nowrap"><?= $mm($totals['debit']) ?></td><td class="text-end r nowrap"><?= $mm($totals['credit']) ?></td><td class="text-end r nowrap"><?= $mm($totals['closing']) ?></td></tr>
  </tbody>
</table></div>
<div class="p-3 text-end"><span class="text-muted">Current due (closing balance):</span> <strong class="fs-5 text-danger"><?= $mm($totals['closing']) ?></strong></div>
