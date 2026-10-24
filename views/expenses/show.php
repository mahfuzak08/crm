<?php App\Core\View::start('actions'); ?>
<a href="<?= url('/expenses/' . $x['id'] . '/voucher') ?>" target="_blank" class="btn btn-light"><i class="bi bi-printer me-1"></i>Voucher</a>
<?php if ($x['status'] === 'pending' && can('expenses.approve')): ?><?= post_button('/expenses/' . $x['id'] . '/approve', '', ['html' => '<i class="bi bi-check2-circle me-1"></i>Approve', 'class' => 'btn btn-success']) ?><?php endif; ?>
<?php if ($x['status'] !== 'cancelled' && can('expenses.edit')): ?><a href="<?= url('/expenses/' . $x['id'] . '/edit') ?>" class="btn btn-brand"><i class="bi bi-pencil me-1"></i>Edit</a><?php endif; ?>
<?php if ($x['status'] !== 'cancelled' && can('expenses.delete')): ?><?= post_button('/expenses/' . $x['id'] . '/cancel', '', ['html' => 'Cancel', 'class' => 'btn btn-outline-danger', 'confirm' => 'Cancel this expense?', 'reason' => true]) ?><?php endif; ?>
<?php App\Core\View::end(); ?>
<div class="card" style="max-width:760px"><div class="card-body">
  <div class="d-flex justify-content-between mb-3"><div><div class="stat-label">Amount</div><div class="display-6 fw-semibold"><?= money($x['amount']) ?></div></div><?= status_badge($x['status']) ?></div>
  <dl class="dl-grid">
    <dt>Number</dt><dd><?= e($x['expense_number']) ?></dd>
    <dt>Date</dt><dd><?= e(fdate($x['expense_date'])) ?></dd>
    <dt>Category</dt><dd><?= e($x['category_name']) ?></dd>
    <dt>Project</dt><dd><?= e($x['project_name'] ?? '—') ?></dd>
    <dt>Method</dt><dd><?= e($x['method_name'] ?? '—') ?></dd>
    <dt>Reference</dt><dd><?= e($x['reference'] ?: '—') ?></dd>
    <dt>Description</dt><dd style="white-space:pre-line"><?= e($x['description'] ?: '—') ?></dd>
    <dt>Attachment</dt><dd><?= $x['attachment'] ? '<a target="_blank" href="' . url('/expenses/' . $x['id'] . '/attachment') . '">' . e($x['attachment_name']) . '</a>' : '—' ?></dd>
    <dt>Created by</dt><dd><?= e($x['created_by_name']) ?>, <?= e(fdate($x['created_at'], true)) ?></dd>
    <dt>Approved by</dt><dd><?= e($x['approved_by_name'] ?? '—') ?></dd>
  </dl>
</div></div>
