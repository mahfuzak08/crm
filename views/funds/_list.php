<div class="px-3 py-2 small border-bottom">Total received (filtered): <strong><?= money($total) ?></strong>
  <?php if ($byContributor): ?><span class="text-muted ms-2">·
    <?= implode(' · ', array_map(static fn($c) => e($c['contributor_name']) . ' <strong>' . money($c['total']) . '</strong>', array_slice($byContributor, 0, 8))) ?>
  </span><?php endif; ?>
</div>
<div class="table-responsive"><table class="table table-hover">
  <thead><tr><th><?= sort_link('number', 'Number') ?></th><th><?= sort_link('date', 'Date') ?></th><th><?= sort_link('contributor', 'Contributor') ?></th><th>Type</th><th>Purpose</th><th>Method</th><th class="text-end"><?= sort_link('amount', 'Amount') ?></th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($p['rows'] as $x): ?>
    <tr>
      <td class="small text-nowrap"><a href="<?= url('/funds/' . $x['id']) ?>"><?= e($x['fund_number']) ?></a></td>
      <td class="small text-nowrap"><?= e(fdate($x['contribution_date'])) ?></td>
      <td><?= e($x['contributor_name']) ?><div class="small text-muted"><?= $x['user_id'] ? 'Director / user' : 'Other' ?><?= $x['contributor_phone'] ? ' · ' . e($x['contributor_phone']) : '' ?></div></td>
      <td class="small"><?= e(App\Services\FundService::TYPES[$x['fund_type']]) ?></td>
      <td class="small" style="max-width:260px"><?= e(mb_strimwidth((string) $x['purpose'], 0, 80, '…')) ?><div class="text-muted"><?= e($x['project_name']) ?></div></td>
      <td class="small"><?= e($x['method_name']) ?></td>
      <td class="text-end text-nowrap fw-medium"><?= money($x['amount']) ?></td>
      <td><?= status_badge($x['status']) ?></td>
      <td class="actions"><?php if ($x['attachment']): ?><a href="<?= url('/funds/' . $x['id'] . '/attachment') ?>" target="_blank" class="btn btn-sm btn-light" title="Attachment"><i class="bi bi-paperclip"></i></a><?php endif; ?>
        <a href="<?= url('/funds/' . $x['id'] . '/receipt') ?>" target="_blank" class="btn btn-sm btn-light" title="Receipt"><i class="bi bi-printer"></i></a></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$p['rows']) echo empty_row(9, 'No fund contributions.'); ?>
  </tbody>
</table></div>
<?= partial('partials/pagination', ['p' => $p]) ?>
