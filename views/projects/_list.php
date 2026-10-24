<div class="table-responsive"><table class="table table-hover">
  <thead><tr><th><?= sort_link('code', 'Code') ?></th><th><?= sort_link('name', 'Project') ?></th><th>Status</th><th class="text-end">Share price</th><th style="min-width:200px">Shares (sold / reserved / available)</th></tr></thead>
  <tbody>
  <?php foreach ($p['rows'] as $r):
      $tot = max(1, (int) $r['total_shares']); $sold = (int) $r['sold_shares']; $res = (int) $r['reserved_shares']; $avail = max(0, (int) $r['total_shares'] - $sold - $res); ?>
    <tr>
      <td class="text-nowrap"><a href="<?= url('/projects/' . $r['id']) ?>"><?= e($r['project_code']) ?></a></td>
      <td><a href="<?= url('/projects/' . $r['id']) ?>" class="fw-medium text-reset"><?= e($r['project_name']) ?></a><div class="small text-muted"><?= e($r['location']) ?></div></td>
      <td><?= $r['status_name'] ? status_badge($r['status_name'], $r['status_color']) : '' ?></td>
      <td class="text-end text-nowrap"><?= money($r['share_price']) ?></td>
      <td>
        <div class="inv-bar mb-1"><span style="width:<?= round($sold / $tot * 100, 2) ?>%;background:#198754"></span><span style="width:<?= round($res / $tot * 100, 2) ?>%;background:#0dcaf0"></span></div>
        <div class="small text-muted"><?= number_format($sold) ?> / <?= number_format($res) ?> / <strong class="text-success"><?= number_format($avail) ?></strong> of <?= number_format((int) $r['total_shares']) ?></div>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$p['rows']) echo empty_row(5, 'No projects yet.'); ?>
  </tbody>
</table></div>
<?= partial('partials/pagination', ['p' => $p]) ?>
