<?php App\Core\View::start('actions'); ?>
<?= post_button('/backup', '', ['html' => '<i class="bi bi-database-down me-1"></i>Create backup now', 'class' => 'btn btn-brand', 'confirm' => 'Create a full database backup now?']) ?>
<?php App\Core\View::end(); ?>
<div class="alert alert-info small"><i class="bi bi-shield-lock me-1"></i>Backups contain <strong>all companies' data</strong> and are stored in <code>storage/backups</code> (outside the web root). Download them to a safe place. A daily cron backup is recommended (see README).</div>
<div class="card"><div class="table-responsive"><table class="table">
  <thead><tr><th>File</th><th>Created</th><th class="text-end">Size</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($files as $f): ?>
    <tr><td class="font-monospace small"><?= e($f['name']) ?></td><td class="small"><?= e(date('d M Y h:i A', $f['time'])) ?></td><td class="text-end small"><?= e(App\Services\DocumentService::humanSize((int) $f['size'])) ?></td>
      <td class="actions"><a class="btn btn-sm btn-light" href="<?= url('/backup/download', ['file' => $f['name']]) ?>"><i class="bi bi-download"></i></a>
        <?= post_button('/backup/delete', '', ['fields' => ['file' => $f['name']], 'html' => '<i class="bi bi-trash"></i>', 'confirm' => 'Delete this backup file?']) ?></td></tr>
  <?php endforeach; ?>
  <?php if (!$files) echo empty_row(4, 'No backups yet.'); ?>
  </tbody>
</table></div></div>
