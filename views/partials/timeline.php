<?php /** vars: $timeline, $subject (lead|customer), $subjectId, $activityTypes */ ?>
<div class="card">
  <div class="card-header">Activity timeline</div>
  <?php if (can('activities.create')): ?>
  <div class="card-body border-bottom">
    <form method="post" action="<?= url('/activities') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="subject" value="<?= e($subject) ?>"><input type="hidden" name="subject_id" value="<?= (int) $subjectId ?>">
      <div class="row g-2">
        <div class="col-sm-4"><select name="type_id" class="form-select form-select-sm" required>
          <?php foreach ($activityTypes as $id => $n): ?><option value="<?= (int) $id ?>"><?= e($n) ?></option><?php endforeach; ?></select></div>
        <div class="col-sm-4"><input type="date" name="activity_date" class="form-control form-control-sm" value="<?= today() ?>" title="Date"></div>
        <div class="col-sm-4"><input type="date" name="follow_up_date" class="form-control form-control-sm" title="Next follow-up (optional)" placeholder="Follow-up"></div>
        <div class="col-12"><textarea name="description" class="form-control form-control-sm" rows="2" placeholder="What happened? (call notes, meeting summary…)" required></textarea></div>
        <div class="col-12"><button class="btn btn-sm btn-brand"><i class="bi bi-plus-lg me-1"></i>Log activity</button> <span class="small text-muted ms-2">Follow-up date also updates the lead's next follow-up.</span></div>
      </div>
    </form>
  </div>
  <?php endif; ?>
  <div class="card-body">
    <?php if (!$timeline): ?><div class="empty py-3"><i class="bi bi-clock-history"></i>No activity yet.</div><?php endif; ?>
    <ul class="timeline">
    <?php foreach ($timeline as $a): ?>
      <li>
        <span class="dot" style="background:<?= e($a['type_color'] ?: '#6c757d') ?>"><i class="bi bi-<?= e(['call' => 'telephone', 'meeting' => 'people', 'sms' => 'chat-dots', 'email' => 'envelope', 'whatsapp' => 'whatsapp', 'follow_up' => 'alarm', 'offer_sent' => 'send', 'payment' => 'cash', 'document_shared' => 'paperclip'][$a['type_code']] ?? 'dot') ?>"></i></span>
        <div class="d-flex justify-content-between gap-2 flex-wrap">
          <div><span class="fw-semibold small"><?= e($a['type_name']) ?></span> <span class="text-muted small">· <?= e($a['user_name'] ?? 'System') ?></span></div>
          <div class="small text-muted text-nowrap"><?= e(fdate($a['activity_at'], true)) ?>
            <?php if (can('activities.delete')): ?><?= post_button('/activities/' . $a['id'] . '/delete', '', ['html' => '<i class="bi bi-x"></i>', 'class' => 'btn btn-sm btn-link text-muted p-0 ms-1', 'confirm' => 'Delete this activity?']) ?><?php endif; ?></div>
        </div>
        <div class="small mt-1" style="white-space:pre-line"><?= e($a['description']) ?></div>
        <?php if ($a['follow_up_date']): ?><div class="small text-warning-emphasis mt-1"><i class="bi bi-alarm"></i> Follow-up <?= e(fdate($a['follow_up_date'])) ?></div><?php endif; ?>
        <?php if ($a['document_id'] && $a['document_title']): ?><div class="small mt-1"><i class="bi bi-paperclip"></i> <a href="<?= url('/documents/' . $a['document_id'] . '/download') ?>"><?= e($a['document_title']) ?></a></div><?php endif; ?>
      </li>
    <?php endforeach; ?>
    </ul>
  </div>
</div>
