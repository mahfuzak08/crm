<?php
App\Core\View::start('actions'); ?>
<a href="<?= e(url_with(['export' => 'print'])) ?>" target="_blank" class="btn btn-light"><i class="bi bi-printer me-1"></i>Print</a>
<?php if (can('reports.export')): ?><a href="<?= e(url_with(['export' => 'csv'])) ?>" class="btn btn-light"><i class="bi bi-download me-1"></i>CSV</a><?php endif; ?>
<?php App\Core\View::end();
$chart = ['type' => 'bar', 'title' => 'Monthly', 'labels' => array_map(static fn($m) => date('M y', strtotime($m['ym'] . '-01')), $d['monthly']), 'series' => [
    ['name' => 'Collection', 'color' => '#198754', 'data' => array_map(static fn($m) => (float) $m['collection'], $d['monthly'])],
    ['name' => 'Expenses + Salaries', 'color' => '#dc3545', 'data' => array_map(static fn($m) => (float) $m['expenses'] + (float) $m['salaries'], $d['monthly'])],
    ['name' => 'Sales value', 'color' => '#0d6efd', 'data' => array_map(static fn($m) => (float) $m['sales'], $d['monthly'])],
]];
?>
<div class="card mb-3">
  <form class="filters" method="get" action="<?= url('/reports/financial') ?>">
    <?php if ($companies): ?><select name="company" class="form-select"><option value="">Current company</option><option value="all"<?= selected('all', $f['company']) ?>>All companies</option>
      <?php foreach ($companies as $c): ?><option value="<?= (int) $c['id'] ?>"<?= selected($c['id'], $f['company']) ?>><?= e($c['name']) ?></option><?php endforeach; ?></select><?php endif; ?>
    <input type="date" name="from" class="form-control" value="<?= e($d['from']) ?>"><input type="date" name="to" class="form-control" value="<?= e($d['to']) ?>">
    <button class="btn btn-brand">Apply</button>
  </form>
</div>
<div class="row g-3 mb-3">
  <?php foreach ([['Sales value (contracted)', $d['sales'], 'blue', 'cart-check'], ['Invoiced', $d['invoiced'], 'violet', 'receipt'], ['Collection (cash in)', $d['collected'], 'green', 'cash-coin'],
      ['Expenses', $d['expenses'], 'red', 'wallet2'], ['Salaries', $d['salaries'], 'amber', 'person-badge'], ['Net cash flow', $d['net'], (float) $d['net'] >= 0 ? 'green' : 'red', 'graph-up-arrow'],
      ['Fund contributions (not income)', $d['funds'], 'blue', 'bank']] as [$l, $v, $c, $i]): ?>
  <div class="col-sm-6 col-xl-4"><div class="card stat"><span class="stat-icon <?= $c ?>"><i class="bi bi-<?= $i ?>"></i></span><div><div class="stat-label"><?= e($l) ?></div><div class="stat-value"><?= money($v) ?></div></div></div></div>
  <?php endforeach; ?>
</div>
<p class="small text-muted"><strong>Net cash flow</strong> = Collection − Expenses − Salaries for the period. It is a cash view, not accounting profit (share sales are contracted value; outstanding customer dues today: <strong><?= money($d['due']) ?></strong>). Fund contributions from directors/others are capital or loans, so they are shown separately and not counted in net cash flow.</p>
<div class="row g-3">
  <div class="col-xl-7"><div class="card"><div class="card-header">Monthly trend</div><div class="card-body"><div id="finChart"></div>
    <script type="application/json" data-chart="finChart"><?= json_encode($chart, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script></div></div></div>
  <div class="col-xl-5"><div class="card"><div class="card-header">By month</div><div class="table-responsive"><table class="table table-sm mb-0">
    <thead><tr><th>Month</th><th class="text-end">Sales</th><th class="text-end">Collection</th><th class="text-end">Out</th><th class="text-end">Net</th></tr></thead><tbody>
    <?php foreach ($d['monthly'] as $m): ?><tr><td><?= e(date('M Y', strtotime($m['ym'] . '-01'))) ?></td><td class="text-end"><?= money($m['sales'], false) ?></td><td class="text-end"><?= money($m['collection'], false) ?></td>
      <td class="text-end"><?= money(App\Core\Money::fromCents(App\Core\Money::toCents((string) $m['expenses']) + App\Core\Money::toCents((string) $m['salaries'])), false) ?></td>
      <td class="text-end fw-semibold <?= (float) $m['net'] < 0 ? 'text-danger' : 'text-success' ?>"><?= money($m['net'], false) ?></td></tr><?php endforeach; ?>
  </tbody></table></div></div></div>
</div>
