<?php App\Core\View::start('actions'); ?>
<a href="<?= e(url_with(['export' => 'print', 'page' => null])) ?>" target="_blank" class="btn btn-light"><i class="bi bi-printer me-1"></i>Print</a>
<?php if (can('reports.export')): ?><a href="<?= e(url_with(['export' => 'csv', 'page' => null])) ?>" class="btn btn-light"><i class="bi bi-download me-1"></i>CSV</a><?php endif; ?>
<?php App\Core\View::end(); ?>
<p class="text-muted small mt-n2"><?= e($desc) ?></p>
<?php if (!empty($r['kpis'])): ?>
<div class="row g-3 mb-3"><?php foreach ($r['kpis'] as $l => $v): ?><div class="col-sm-4"><div class="card stat"><div><div class="stat-label"><?= e($l) ?></div><div class="stat-value"><?= e((string) $v) ?></div></div></div></div><?php endforeach; ?></div>
<?php endif; ?>
<?php if (!empty($r['summary'])): ?>
<div class="row g-3 mb-3">
  <?php foreach ($r['summary'] as $label => $rows): ?>
  <div class="col-md-6 col-xl-4"><div class="card h-100"><div class="card-header"><?= e($label) ?></div>
    <table class="table table-sm mb-0"><tbody>
      <?php foreach ($rows as $s): ?><tr><td><?= e($s['label']) ?></td><td class="text-end text-muted"><?= $s['cnt'] !== '' ? (int) $s['cnt'] : '' ?></td><td class="text-end"><?= $s['total'] === null ? '' : (!empty($r['count_summary']) ? (int) $s['total'] : money($s['total'])) ?></td></tr><?php endforeach; ?>
      <?php if (!$rows): ?><tr><td class="text-muted">No data</td></tr><?php endif; ?>
    </tbody></table></div></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<div class="card">
  <?= partial('reports/_filters', compact('type', 'f', 'r', 'options')) ?>
  <div id="list"><?= partial('reports/_table', compact('p', 'r')) ?></div>
</div>
