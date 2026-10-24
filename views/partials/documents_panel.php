<?php /** vars: $documents, $field (customer_id|lead_id|project_id), $subjectId, $docTypes */ ?>
<div class="card">
  <div class="card-header">Documents <span class="badge text-bg-light text-dark"><?= count($documents) ?></span></div>
  <ul class="list-group list-group-flush">
  <?php foreach ($documents as $d): ?>
    <li class="list-group-item d-flex align-items-center gap-2">
      <i class="bi bi-<?= e(App\Services\DocumentService::icon($d['mime'])) ?> fs-5 text-muted"></i>
      <div class="flex-grow-1 small text-truncate">
        <a href="<?= url('/documents/' . $d['id'] . '/download') ?>" class="fw-medium"><?= e($d['title']) ?></a>
        <div class="text-muted"><?= e($d['type_name'] ?? 'Document') ?> · <?= e(App\Services\DocumentService::humanSize((int) $d['size'])) ?> · <?= e(fdate($d['created_at'])) ?></div>
      </div>
      <?php if ($d['is_shareable'] && can('documents.share')): ?>
        <button type="button" class="btn btn-sm btn-light" data-copy="<?= e(App\Services\ShareLinkService::url('document', (int) $d['id'], 14)) ?>" title="Copy share link (14 days)"><i class="bi bi-link-45deg"></i></button>
      <?php endif; ?>
      <?php if (can('documents.delete')): ?><?= post_button('/documents/' . $d['id'] . '/delete', '', ['html' => '<i class="bi bi-trash"></i>', 'confirm' => 'Delete this document?', 'class' => 'btn btn-sm btn-light text-danger']) ?><?php endif; ?>
    </li>
  <?php endforeach; ?>
  <?php if (!$documents): ?><li class="list-group-item small text-muted">No documents.</li><?php endif; ?>
  </ul>
  <?php if (can('documents.upload')): ?>
  <div class="card-body border-top">
    <form method="post" action="<?= url('/documents') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="<?= e($field) ?>" value="<?= (int) $subjectId ?>">
      <div class="row g-2">
        <div class="col-sm-6"><input name="title" class="form-control form-control-sm" placeholder="Title (optional)"></div>
        <div class="col-sm-6"><select name="document_type_id" class="form-select form-select-sm"><option value="">Type…</option><?php foreach ($docTypes as $id => $n): ?><option value="<?= (int) $id ?>"><?= e($n) ?></option><?php endforeach; ?></select></div>
        <div class="col-12"><input type="file" name="file" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp" required></div>
        <div class="col-12 d-flex justify-content-between align-items-center">
          <div class="form-check small"><input type="hidden" name="is_shareable" value="0"><input class="form-check-input" type="checkbox" name="is_shareable" value="1" id="shr<?= e($field) ?>"><label class="form-check-label" for="shr<?= e($field) ?>">Shareable by link</label></div>
          <button class="btn btn-sm btn-brand"><i class="bi bi-upload me-1"></i>Upload</button>
        </div>
      </div>
    </form>
  </div>
  <?php endif; ?>
</div>
