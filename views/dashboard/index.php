<?php
$c = $d['cards'];
$lbl = array_map(static fn($r) => date('M', strtotime($r['ym'] . '-01')), $d['series']);
$money = can('invoices.view') || can('payments.view') || can('reports.view');
$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$salesChart = ['type' => 'bar', 'labels' => $lbl, 'series' => [
    ['name' => 'Sales', 'color' => '#0d6efd', 'data' => array_map(static fn($r) => (float) $r['sales'], $d['series'])],
    ['name' => 'Collection', 'color' => '#198754', 'data' => array_map(static fn($r) => (float) $r['collection'], $d['series'])],
    ['name' => 'Expense', 'color' => '#dc3545', 'data' => array_map(static fn($r) => (float) $r['expense'], $d['series'])],
]];
$leadChart = ['type' => 'bar', 'count' => true, 'labels' => array_column($d['funnel'], 'name'), 'series' => [
    ['name' => 'Leads', 'color' => '#6f42c1', 'data' => array_map('intval', array_column($d['funnel'], 'cnt'))],
]];
$cards = [
    ['Total Leads', number_format($c['leads']), 'people', 'violet', 'leads.view', '/leads'],
    ['New Leads (month)', number_format($c['new_leads']), 'person-plus', 'blue', 'leads.view', '/leads'],
    ['Customers', number_format($c['customers']), 'person-vcard', 'slate', 'customers.view', '/customers'],
    ['Projects', number_format($c['projects']), 'buildings', 'slate', 'projects.view', '/projects'],
    ['Available Shares', number_format($c['available_shares']), 'box-seam', 'amber', 'shares.view', '/shares'],
    ['Sold Shares', number_format($c['sold_shares']), 'check2-circle', 'green', 'shares.view', '/shares'],
    ['Total Sales', money_short($c['sales']), 'graph-up-arrow', 'blue', 'sales.view', '/sales'],
    ['Total Invoiced', money_short($c['invoiced']), 'receipt', 'violet', 'invoices.view', '/invoices'],
    ['Total Collection', money_short($c['collection']), 'cash-coin', 'green', 'payments.view', '/payments'],
    ['Total Due', money_short($c['due']), 'exclamation-circle', 'red', 'invoices.view', '/reports/due'],
    ['Total Expense', money_short($c['expense']), 'wallet2', 'red', 'expenses.view', '/expenses'],
    ['Board of Directors', number_format($c['directors']), 'person-workspace', 'slate', 'directors.view', '/directors'],
];
App\Core\View::start('actions');
if ($companies): ?>
  <form method="get" action="<?= url('/') ?>"><select name="company" class="form-select" data-autosubmit>
    <option value="">This company</option><option value="all"<?= selected('all', $scope) ?>>All companies</option>
    <?php foreach ($companies as $co): ?><option value="<?= (int) $co['id'] ?>"<?= selected($co['id'], $scope) ?>><?= e($co['name']) ?></option><?php endforeach; ?>
  </select></form>
<?php endif;
if (can('leads.create')): ?><a href="<?= url('/leads/create') ?>" class="btn btn-light"><i class="bi bi-person-plus me-1"></i>Lead</a><?php endif;
if (can('sales.create')): ?><a href="<?= url('/sales/create') ?>" class="btn btn-brand"><i class="bi bi-cart-plus me-1"></i>Sell shares</a><?php endif;
App\Core\View::end(); ?>

<div class="row g-3 mb-3">
<?php foreach ($cards as [$label, $value, $icon, $color, $perm, $link]): if (!can($perm)) continue; ?>
  <div class="col-6 col-md-4 col-xl-3">
    <a href="<?= url($link) ?>" class="card stat text-reset"><span class="stat-icon <?= $color ?>"><i class="bi bi-<?= $icon ?>"></i></span>
      <div class="min-w-0"><div class="stat-label"><?= e($label) ?></div><div class="stat-value"><?= $value ?></div></div></a>
  </div>
<?php endforeach; ?>
</div>

<div class="row g-3 mb-3">
  <?php if ($money): ?>
  <div class="col-xl-8"><div class="card h-100"><div class="card-header">Monthly sales, collection &amp; expense <span class="small text-muted fw-normal">last 12 months</span></div>
    <div class="card-body"><div id="chartSales"></div><script type="application/json" data-chart="chartSales"><?= json_encode($salesChart, $jsonFlags) ?></script></div></div></div>
  <?php endif; ?>
  <?php if (can('leads.view')): ?>
  <div class="col-xl-4"><div class="card h-100"><div class="card-header">Lead conversion <span class="badge text-bg-success"><?= e((string) $c['conversion_rate']) ?>%</span></div>
    <div class="card-body"><div id="chartLeads"></div><script type="application/json" data-chart="chartLeads"><?= json_encode($leadChart, $jsonFlags) ?></script></div></div></div>
  <?php endif; ?>
</div>

<?php if ($d['board']): ?>
<div class="card mb-3"><div class="card-header">Board of Directors <a href="<?= url('/directors') ?>" class="small fw-normal">all</a></div>
  <div class="card-body"><div class="row g-3">
    <?php foreach ($d['board'] as $b): ?>
      <div class="col-6 col-md-4 col-xl-3"><a href="<?= url('/directors/' . $b['id']) ?>" class="d-flex align-items-center gap-2 text-reset">
        <span class="avatar"><?= e(initials($b['name'])) ?></span>
        <span class="min-w-0"><span class="d-block fw-medium text-truncate"><?= e($b['name']) ?></span>
          <span class="d-block small text-muted text-truncate"><?= e($b['designation'] ?: 'Director') ?> · <?= number_format((int) $b['own_shares']) ?> shares</span></span></a></div>
    <?php endforeach; ?>
  </div></div></div>
<?php endif; ?>

<div class="row g-3">
  <?php if (isset($lists['followups'])): ?>
  <div class="col-lg-4"><div class="card h-100"><div class="card-header">Follow-ups due <a href="<?= url('/leads', ['followup' => 'overdue']) ?>" class="small fw-normal">all</a></div>
    <ul class="list-group list-group-flush">
      <?php foreach ($lists['followups'] as $l): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center small"><span><a href="<?= url('/leads/' . $l['id']) ?>"><?= e($l['name']) ?></a><div class="text-muted"><?= e($l['phone']) ?></div></span>
          <span class="text-end"><span class="<?= $l['follow_up_date'] < today() ? 'text-danger fw-semibold' : '' ?>"><?= e(fdate($l['follow_up_date'])) ?></span><br><?= status_badge($l['status'], $l['color']) ?></span></li>
      <?php endforeach; ?>
      <?php if (!$lists['followups']): ?><li class="list-group-item small text-muted">Nothing due. 🎉</li><?php endif; ?>
    </ul></div></div>
  <?php endif; ?>
  <?php if (isset($lists['overdue'])): ?>
  <div class="col-lg-4"><div class="card h-100"><div class="card-header">Overdue invoices <a href="<?= url('/invoices', ['status' => 'overdue']) ?>" class="small fw-normal">all</a></div>
    <ul class="list-group list-group-flush">
      <?php foreach ($lists['overdue'] as $i): ?>
        <li class="list-group-item d-flex justify-content-between small"><span><a href="<?= url('/invoices/' . $i['id']) ?>"><?= e($i['invoice_number']) ?></a><div class="text-muted"><?= e($i['customer']) ?></div></span>
          <span class="text-end text-danger fw-semibold"><?= money($i['due_amount']) ?><div class="text-muted fw-normal"><?= e(fdate($i['due_date'])) ?></div></span></li>
      <?php endforeach; ?>
      <?php if (!$lists['overdue']): ?><li class="list-group-item small text-muted">No overdue invoices.</li><?php endif; ?>
    </ul></div></div>
  <?php endif; ?>
  <?php if (isset($lists['payments'])): ?>
  <div class="col-lg-4"><div class="card h-100"><div class="card-header">Recent collections <a href="<?= url('/payments') ?>" class="small fw-normal">all</a></div>
    <ul class="list-group list-group-flush">
      <?php foreach ($lists['payments'] as $p): ?>
        <li class="list-group-item d-flex justify-content-between small"><span><a href="<?= url('/payments/' . $p['id']) ?>"><?= e($p['payment_number']) ?></a><div class="text-muted"><?= e($p['customer']) ?> · <?= e($p['method']) ?></div></span>
          <span class="text-end text-success fw-semibold"><?= money($p['amount']) ?><div class="text-muted fw-normal"><?= e(fdate($p['payment_date'])) ?></div></span></li>
      <?php endforeach; ?>
      <?php if (!$lists['payments']): ?><li class="list-group-item small text-muted">No payments yet.</li><?php endif; ?>
    </ul></div></div>
  <?php endif; ?>
  <?php if ($lists['notifications']): ?>
  <div class="col-lg-4"><div class="card"><div class="card-header">Notifications</div><ul class="list-group list-group-flush">
    <?php foreach ($lists['notifications'] as $n): ?><li class="list-group-item small"><a href="<?= url('/notifications/' . $n['id']) ?>"><?= e($n['title']) ?></a><div class="text-muted"><?= e(fdate($n['created_at'], true)) ?></div></li><?php endforeach; ?>
  </ul></div></div>
  <?php endif; ?>
</div>
