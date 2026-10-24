<div class="table-responsive"><table class="table table-hover">
  <thead><tr><th>Document</th><th>Type</th><th>Linked to</th><th>Size</th><th>Uploaded</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($p['rows'] as $d): ?>
    <tr>
      <td><i class="bi bi-<?= e(App\Services\DocumentService::icon($d['mime'])) ?> text-muted me-1"></i><a href="<?= url('/documents/' . $d['id'] . '/download') ?>" class="fw-medium"><?= e($d['title']) ?></a>
        <?php if ($d['is_shareable']): ?><span class="badge text-bg-info">shareable</span><?php endif; ?>
        <div class="small text-muted text-truncate" style="max-width:280px"><?= e($d['original_name']) ?></div></td>
      <td class="small"><?= e($d['type_name']) ?></td>
      <td class="small">
        <?php if ($d['customer_id']): ?><a href="<?= url('/customers/' . $d['customer_id']) ?>"><?= e($d['customer_name']) ?></a><?php endif; ?>
        <?php if ($d['lead_id']): ?><a href="<?= url('/leads/' . $d['lead_id']) ?>"><?= e($d['lead_name']) ?></a> <span class="text-muted">lead</span><?php endif; ?>
        <?php if ($d['project_id']): ?><div class="text-muted"><?= e($d['project_name']) ?></div><?php endif; ?>
      </td>
      <td class="small text-nowrap"><?= e(App\Services\DocumentService::humanSize((int) $d['size'])) ?></td>
      <td class="small text-nowrap"><?= e(fdate($d['created_at'])) ?><div class="text-muted"><?= e($d['uploader']) ?></div></td>
      <td class="actions">
        <a href="<?= url('/documents/' . $d['id'] . '/download', ['dl' => 1]) ?>" class="btn btn-sm btn-light" title="Download"><i class="bi bi-download"></i></a>
        <?php if (can('documents.share')): ?>
          <?php if ($d['is_shareable']): ?><button type="button" class="btn btn-sm btn-light" data-copy="<?= e(App\Services\ShareLinkService::url('document', (int) $d['id'], 14)) ?>" title="Copy share link (14 days)"><i class="bi bi-link-45deg"></i></button><?php endif; ?>
          <?= post_button('/documents/' . $d['id'] . '/share', '', ['html' => '<i class="bi bi-' . ($d['is_shareable'] ? 'eye-slash' : 'share') . '"></i>', 'class' => 'btn btn-sm btn-light', 'title' => $d['is_shareable'] ? 'Disable sharing' : 'Allow sharing']) ?>
        <?php endif; ?>
        <?php if (can('documents.delete')): ?><?= post_button('/documents/' . $d['id'] . '/delete', '', ['html' => '<i class="bi bi-trash"></i>', 'confirm' => 'Delete this document?', 'class' => 'btn btn-sm btn-light text-danger']) ?><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$p['rows']) echo empty_row(6, 'No documents.'); ?>
  </tbody>
</table></div>
<?= partial('partials/pagination', ['p' => $p]) ?>
