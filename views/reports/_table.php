<div class="table-responsive"><table class="table table-hover table-sm">
  <thead><tr><?php foreach ($r['columns'] as [$label, $kind]): ?><th class="<?= in_array($kind, ['money', 'int'], true) ? 'text-end' : '' ?>"><?= e($label) ?></th><?php endforeach; ?></tr></thead>
  <tbody>
  <?php foreach ($p['rows'] as $row): ?>
    <tr><?php foreach ($r['columns'] as $k => [$label, $kind]): ?><td class="<?= in_array($kind, ['money', 'int'], true) ? 'text-end text-nowrap' : 'small' ?>"><?= partial('reports/_cell', ['value' => $row[$k] ?? '', 'kind' => $kind]) ?></td><?php endforeach; ?></tr>
  <?php endforeach; ?>
  <?php if (!$p['rows']) echo empty_row(count($r['columns']), 'No data for the selected filters.'); ?>
  </tbody>
  <?php if (!empty($r['totals'])): ?>
  <tfoot><tr class="fw-semibold table-light"><?php $first = true; foreach ($r['columns'] as $k => [$label, $kind]): ?>
    <td class="<?= in_array($kind, ['money', 'int'], true) ? 'text-end text-nowrap' : '' ?>"><?php if (isset($r['totals'][$k])): ?><?= partial('reports/_cell', ['value' => $r['totals'][$k], 'kind' => $kind]) ?><?php elseif ($first): ?>Total (all pages)<?php endif; $first = false; ?></td>
  <?php endforeach; ?></tr></tfoot>
  <?php endif; ?>
</table></div>
<?= partial('partials/pagination', ['p' => $p]) ?>
