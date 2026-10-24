<?php
$converted = (bool) $lead['customer_id'];
App\Core\View::start('actions'); ?>
<?php if (can('offers.create') && !$converted): ?><a href="<?= url('/offers/create', ['lead' => $lead['id']]) ?>" class="btn btn-light"><i class="bi bi-send me-1"></i>Create offer</a><?php endif; ?>
<?php if ($converted): ?>
  <a href="<?= url('/customers/' . $lead['customer_id']) ?>" class="btn btn-outline-success"><i class="bi bi-person-check me-1"></i>View customer</a>
<?php elseif (can('leads.convert') && can('customers.create')): ?>
  <?= post_button('/leads/' . $lead['id'] . '/convert', '', ['html' => '<i class="bi bi-person-check me-1"></i>Convert to customer', 'class' => 'btn btn-outline-success', 'confirm' => 'Convert this lead into a customer?']) ?>
<?php endif; ?>
<?php if (can('leads.edit')): ?><a href="<?= url('/leads/' . $lead['id'] . '/edit') ?>" class="btn btn-brand"><i class="bi bi-pencil me-1"></i>Edit</a><?php endif; ?>
<?php if (can('leads.delete')): ?><?= post_button('/leads/' . $lead['id'] . '/delete', '', ['html' => '<i class="bi bi-trash"></i>', 'class' => 'btn btn-outline-danger', 'confirm' => 'Delete this lead?']) ?><?php endif; ?>
<?php App\Core\View::end(); ?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card mb-3"><div class="card-body">
      <div class="d-flex align-items-center gap-3 mb-3">
        <span class="avatar lg"><?= e(initials($lead['name'])) ?></span>
        <div><div class="fw-semibold fs-5"><?= e($lead['name']) ?></div><div class="text-muted small"><?= e($lead['lead_code']) ?></div></div>
      </div>
      <div class="d-flex flex-wrap gap-2 mb-3">
        <?php if ($lead['phone']): ?><a class="btn btn-sm btn-light" href="tel:<?= e(phone_digits($lead['phone'])) ?>"><i class="bi bi-telephone"></i> Call</a><?php endif; ?>
        <?php if ($whatsapp): ?><a class="btn btn-sm btn-success" href="<?= e($whatsapp) ?>" target="_blank" rel="noopener" data-log-share="<?= url('/leads/' . $lead['id'] . '/share-log') ?>" data-channel="whatsapp"><i class="bi bi-whatsapp"></i> WhatsApp</a><?php endif; ?>
        <?php if ($lead['email']): ?><a class="btn btn-sm btn-light" href="mailto:<?= e($lead['email']) ?>?subject=<?= rawurlencode((string) (company()['name'] ?? '')) ?>&body=<?= rawurlencode('Dear ' . $lead['name'] . ",\n\n" . ($projectShare ? "Please view our project gallery: $projectShare\n" : '')) ?>" data-log-share="<?= url('/leads/' . $lead['id'] . '/share-log') ?>" data-channel="email"><i class="bi bi-envelope"></i> Email</a><?php endif; ?>
        <?php if ($projectShare): ?><button type="button" class="btn btn-sm btn-light" data-copy="<?= e($projectShare) ?>" title="Copy project gallery link (30 days)"><i class="bi bi-images"></i> Gallery link</button><?php endif; ?>
      </div>
      <dl class="dl-grid">
        <dt>Status</dt><dd>
          <?php if (can('leads.edit')): ?>
          <form method="post" action="<?= url('/leads/' . $lead['id'] . '/status') ?>" data-ajax-form="reload">
            <?= csrf_field() ?>
            <select name="status_id" class="form-select form-select-sm" data-autosubmit-ajax>
              <?php foreach ($statuses as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $lead['status_id']) ?>><?= e($n) ?></option><?php endforeach; ?>
            </select>
          </form>
          <?php else: ?><?= status_badge($lead['status_name'], $lead['status_color']) ?><?php endif; ?>
        </dd>
        <dt>Phone</dt><dd><?= e($lead['phone'] ?: '—') ?></dd>
        <dt>Email</dt><dd><?= e($lead['email'] ?: '—') ?></dd>
        <dt>Source</dt><dd><?= e($lead['source_name'] ?: '—') ?></dd>
        <dt>Project</dt><dd><?= $lead['project_id'] ? '<a href="' . url('/projects/' . $lead['project_id']) . '">' . e($lead['project_name']) . '</a>' : '—' ?></dd>
        <dt>Assigned</dt><dd>
          <?php if (can('leads.assign')): ?>
          <form method="post" action="<?= url('/leads/' . $lead['id'] . '/assign') ?>" data-ajax-form="reload">
            <?= csrf_field() ?>
            <select name="assigned_to" class="form-select form-select-sm" data-autosubmit-ajax>
              <option value="">— unassigned —</option>
              <?php foreach ($users as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $lead['assigned_to']) ?>><?= e($n) ?></option><?php endforeach; ?>
            </select>
          </form>
          <?php else: ?><?= e($lead['assigned_name'] ?? '—') ?><?php endif; ?>
        </dd>
        <dt>Follow-up</dt><dd><?= e(fdate($lead['follow_up_date']) ?: '—') ?></dd>
        <dt>Address</dt><dd><?= e($lead['address'] ?: '—') ?></dd>
        <dt>Created</dt><dd><?= e(fdate($lead['created_at'])) ?> by <?= e($lead['created_by_name'] ?? '—') ?></dd>
      </dl>
      <?php if ($lead['notes']): ?><hr><div class="small" style="white-space:pre-line"><?= e($lead['notes']) ?></div><?php endif; ?>
    </div></div>

    <?php if (can('offers.view')): ?>
    <div class="card mb-3"><div class="card-header">Offers</div>
      <ul class="list-group list-group-flush">
      <?php foreach ($offers as $o): ?>
        <li class="list-group-item d-flex justify-content-between small"><a href="<?= url('/offers/' . $o['id']) ?>"><?= e($o['offer_number']) ?></a><span><?= money($o['total']) ?> <?= status_badge($o['status']) ?></span></li>
      <?php endforeach; ?>
      <?php if (!$offers): ?><li class="list-group-item small text-muted">No offers yet.</li><?php endif; ?>
      </ul>
    </div>
    <?php endif; ?>

    <?php if (can('documents.view')): ?>
      <?= partial('partials/documents_panel', ['documents' => $documents, 'field' => 'lead_id', 'subjectId' => $lead['id'], 'docTypes' => $docTypes]) ?>
    <?php endif; ?>
  </div>

  <div class="col-lg-8">
    <?php if (can('activities.view')): ?>
      <?= partial('partials/timeline', ['timeline' => $timeline, 'subject' => 'lead', 'subjectId' => $lead['id'], 'activityTypes' => $activityTypes]) ?>
    <?php endif; ?>
  </div>
</div>
