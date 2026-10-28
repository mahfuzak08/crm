<div class="m-list d-md-none">
  <?php foreach ($p['rows'] as $l):
      $overdue = $l['follow_up_date'] && $l['follow_up_date'] < today() && !in_array($l['status_code'], ['converted', 'lost'], true); ?>
    <div class="m-item">
      <a href="<?= url('/leads/' . $l['id']) ?>" class="m-main">
        <span class="avatar"><?= e(initials($l['name'])) ?></span>
        <span class="m-body">
          <span class="m-title"><?= e($l['name']) ?></span>
          <span class="m-sub"><i class="bi bi-envelope"></i><?= e($l['email'] ?: '—') ?></span>
          <span class="m-sub"><i class="bi bi-building"></i><?= e($l['project_name'] ?: ($l['source_name'] ?: '—')) ?></span>
        </span>
        <span class="m-side">
          <span class="m-meta"><?= e($l['phone'] ?: '') ?></span>
          <span class="m-status"><span class="dot" style="background:<?= e($l['status_color'] ?: '#94a3b8') ?>"></span><?= e($l['status_name']) ?></span>
          <?php if ($l['follow_up_date']): ?><span class="m-meta<?= $overdue ? ' text-danger fw-semibold' : '' ?>"><i class="bi bi-alarm"></i> <?= e(fdate($l['follow_up_date'])) ?></span><?php endif; ?>
        </span>
      </a>
      <?php if ($l['phone']): ?><a class="m-call" href="tel:<?= e(phone_digits($l['phone'])) ?>" aria-label="Call <?= e($l['name']) ?>"><i class="bi bi-telephone-fill"></i></a><?php endif; ?>
    </div>
  <?php endforeach; ?>
  <?php if (!$p['rows']): ?><div class="empty"><i class="bi bi-people"></i>No leads found.</div><?php endif; ?>
</div>
<div class="table-responsive d-none d-md-block"><table class="table table-hover">
  <thead><tr><th><?= sort_link('code', 'Code') ?></th><th><?= sort_link('name', 'Lead') ?></th><th>Contact</th><th><?= sort_link('status', 'Status') ?></th><th>Source / Project</th><th>Assigned</th><th><?= sort_link('followup', 'Follow-up') ?></th><th><?= sort_link('created', 'Created') ?></th></tr></thead>
  <tbody>
  <?php foreach ($p['rows'] as $l):
      $overdue = $l['follow_up_date'] && $l['follow_up_date'] < today() && !in_array($l['status_code'], ['converted', 'lost'], true); ?>
    <tr>
      <td class="text-nowrap small"><a href="<?= url('/leads/' . $l['id']) ?>"><?= e($l['lead_code']) ?></a></td>
      <td><a href="<?= url('/leads/' . $l['id']) ?>" class="fw-medium text-reset"><?= e($l['name']) ?></a></td>
      <td class="small text-nowrap"><?php if ($l['phone']): ?><a href="tel:<?= e(phone_digits($l['phone'])) ?>"><?= e($l['phone']) ?></a><br><?php endif; ?><span class="text-muted"><?= e($l['email']) ?></span></td>
      <td><?= status_badge($l['status_name'], $l['status_color']) ?></td>
      <td class="small"><?= e($l['source_name']) ?><br><span class="text-muted"><?= e($l['project_name']) ?></span></td>
      <td class="small"><?= e($l['assigned_name'] ?? '—') ?></td>
      <td class="small text-nowrap<?= $overdue ? ' text-danger fw-semibold' : '' ?>"><?= e(fdate($l['follow_up_date'])) ?></td>
      <td class="small text-nowrap text-muted"><?= e(fdate($l['created_at'])) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$p['rows']) echo empty_row(8, 'No leads found.'); ?>
  </tbody>
</table></div>
<?= partial('partials/pagination', ['p' => $p]) ?>
