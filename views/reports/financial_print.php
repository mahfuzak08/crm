<?php $m = static fn($v) => money_c($v, $company); ?>
<p class="small" style="color:#6b7385">Period <?= e(fdate($d['from'])) ?> — <?= e(fdate($d['to'])) ?> · Generated <?= e(fdate(now(), true)) ?></p>
<table class="totals" style="width:60%;margin:0 0 16px">
  <tr><td>Sales value (contracted)</td><td><?= $m($d['sales']) ?></td></tr>
  <tr><td>Invoiced</td><td><?= $m($d['invoiced']) ?></td></tr>
  <tr><td>Collection (cash in)</td><td><?= $m($d['collected']) ?></td></tr>
  <tr><td>Expenses</td><td>− <?= $m($d['expenses']) ?></td></tr>
  <tr><td>Salaries</td><td>− <?= $m($d['salaries']) ?></td></tr>
  <tr class="grand"><td>Net cash flow</td><td><?= $m($d['net']) ?></td></tr>
  <tr class="due"><td>Outstanding customer dues (today)</td><td><?= $m($d['due']) ?></td></tr>
</table>
<table class="items"><thead><tr><th>Month</th><th class="r">Sales value</th><th class="r">Collection</th><th class="r">Expenses</th><th class="r">Salaries</th><th class="r">Net cash flow</th></tr></thead><tbody>
<?php foreach ($d['monthly'] as $r): ?><tr><td><?= e(date('M Y', strtotime($r['ym'] . '-01'))) ?></td><td class="r"><?= $m($r['sales']) ?></td><td class="r"><?= $m($r['collection']) ?></td><td class="r"><?= $m($r['expenses']) ?></td><td class="r"><?= $m($r['salaries']) ?></td><td class="r"><?= $m($r['net']) ?></td></tr><?php endforeach; ?>
</tbody></table>
<p class="small" style="color:#6b7385">Net cash flow = Collection − Expenses − Salaries. This is a cash summary, not an accounting profit & loss statement.</p>
