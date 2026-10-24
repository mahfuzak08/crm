<?php $filters = array_filter($f, static fn($v) => $v !== ''); ?>
<div class="small" style="margin-bottom:10px;color:#6b7385">
  <?php if ($filters): ?>Filters: <?php foreach ($filters as $k => $v): ?><?= e(ucfirst(str_replace('_', ' ', $k))) ?>: <?= e($v) ?> · <?php endforeach; ?><?php endif; ?>
  Generated <?= e(fdate(now(), true)) ?> · <?= count($rows) ?> rows
</div>
<?php if (!empty($r['kpis'])): ?><p><?php foreach ($r['kpis'] as $l => $v): ?><strong><?= e($l) ?>:</strong> <?= e((string) $v) ?> &nbsp; <?php endforeach; ?></p><?php endif; ?>
<table class="items" style="font-size:10.5px">
  <thead><tr><?php foreach ($r['columns'] as [$label, $kind]): ?><th class="<?= in_array($kind, ['money', 'int'], true) ? 'r' : '' ?>"><?= e($label) ?></th><?php endforeach; ?></tr></thead>
  <tbody>
  <?php foreach ($rows as $row): ?><tr><?php foreach ($r['columns'] as $k => [$label, $kind]): ?><td class="<?= in_array($kind, ['money', 'int'], true) ? 'r nowrap' : '' ?>"><?= partial('reports/_cell', ['value' => $row[$k] ?? '', 'kind' => $kind, 'company' => $company]) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
  </tbody>
  <?php if (!empty($r['totals'])): ?><tfoot><tr><?php $first = true; foreach ($r['columns'] as $k => [$label, $kind]): ?><td class="<?= in_array($kind, ['money', 'int'], true) ? 'r nowrap' : '' ?>" style="font-weight:700;border-top:2px solid #1e2533"><?= isset($r['totals'][$k]) ? partial('reports/_cell', ['value' => $r['totals'][$k], 'kind' => $kind, 'company' => $company]) : ($first ? 'Total' : '') ?></td><?php $first = false; endforeach; ?></tr></tfoot><?php endif; ?>
</table>
