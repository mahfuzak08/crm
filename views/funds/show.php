<?php App\Core\View::start('actions'); ?>
<a href="<?= url('/funds/' . $x['id'] . '/receipt') ?>" target="_blank" class="btn btn-light"><i class="bi bi-printer me-1"></i>Receipt</a>
<?php if ($x['status'] !== 'cancelled' && can('funds.edit')): ?><a href="<?= url('/funds/' . $x['id'] . '/edit') ?>" class="btn btn-brand"><i class="bi bi-pencil me-1"></i>Edit</a><?php endif; ?>
<?php if ($x['status'] !== 'cancelled' && can('funds.delete')): ?><?= post_button('/funds/' . $x['id'] . '/cancel', '', ['html' => 'Cancel', 'class' => 'btn btn-outline-danger', 'confirm' => 'Cancel this fund contribution?', 'reason' => true]) ?><?php endif; ?>
<?php App\Core\View::end(); ?>
<div class="card" style="max-width:760px"><div class="card-body">
  <div class="d-flex justify-content-between mb-3"><div><div class="stat-label">Amount</div><div class="display-6 fw-semibold"><?= money($x['amount']) ?></div></div><?= status_badge($x['status']) ?></div>
  <dl class="dl-grid">
    <dt>Number</dt><dd><?= e($x['fund_number']) ?></dd>
    <dt>Date</dt><dd><?= e(fdate($x['contribution_date'])) ?></dd>
    <dt>Contributor</dt><dd><?= $x['user_id'] && can('users.view') ? '<a href="' . url('/users/' . $x['user_id']) . '">' . e($x['contributor_name']) . '</a>' : e($x['contributor_name']) ?>
      <span class="text-muted small"><?= $x['user_id'] ? '(director / user)' : '(other)' ?></span></dd>
    <dt>Phone</dt><dd><?= e($x['contributor_phone'] ?: '—') ?></dd>
    <dt>Type</dt><dd><?= e(App\Services\FundService::TYPES[$x['fund_type']]) ?></dd>
    <dt>Project</dt><dd><?= e($x['project_name'] ?? '—') ?></dd>
    <dt>Purpose</dt><dd><?= e($x['purpose'] ?: '—') ?></dd>
    <dt>Method</dt><dd><?= e($x['method_name'] ?? '—') ?></dd>
    <dt>Reference</dt><dd><?= e($x['reference'] ?: '—') ?></dd>
    <dt>Notes</dt><dd style="white-space:pre-line"><?= e($x['notes'] ?: '—') ?></dd>
    <dt>Attachment</dt><dd><?= $x['attachment'] ? '<a target="_blank" href="' . url('/funds/' . $x['id'] . '/attachment') . '">' . e($x['attachment_name']) . '</a>' : '—' ?></dd>
    <dt>Recorded by</dt><dd><?= e($x['created_by_name']) ?>, <?= e(fdate($x['created_at'], true)) ?></dd>
  </dl>
</div></div>
