<div class="row g-3">
  <div class="col-lg-8">
    <div class="card">
      <form class="filters" method="get" action="<?= url('/documents') ?>" data-live-filter data-target="#list">
        <input type="search" name="q" class="form-control search" placeholder="Search title, file, customer, lead…" value="<?= e($f['q']) ?>">
        <select name="type" class="form-select"><option value="">All types</option><?php foreach ($types as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['type']) ?>><?= e($n) ?></option><?php endforeach; ?></select>
        <select name="project" class="form-select"><option value="">All projects</option><?php foreach ($projects as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $f['project']) ?>><?= e($n) ?></option><?php endforeach; ?></select>
      </form>
      <div id="list"><?= partial('documents/_list', ['p' => $p]) ?></div>
    </div>
  </div>
  <div class="col-lg-4">
    <?php if (can('documents.upload')): ?>
    <div class="card"><div class="card-header">Upload document</div><div class="card-body">
      <form method="post" action="<?= url('/documents') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="row g-3">
          <?= form_input('title', 'Title', null, ['col' => 'col-12']) ?>
          <?= form_select('document_type_id', 'Type', $types, null, ['empty' => '—', 'col' => 'col-12']) ?>
          <?= form_select('project_id', 'Project (optional)', $projects, null, ['empty' => '—', 'col' => 'col-12']) ?>
          <div class="col-12"><label class="form-label">File <span class="req">*</span></label><input type="file" name="file" class="form-control<?= invalid('file') ?>" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp" required><?= error_for('file') ?>
            <div class="form-text">PDF, Word, Excel or images. Max <?= (int) config('upload.max_size_mb') ?> MB.</div></div>
          <?= form_check('is_shareable', 'Shareable by link (e.g. brochure)') ?>
        </div>
        <button class="btn btn-brand mt-3"><i class="bi bi-upload me-1"></i>Upload</button>
      </form>
      <p class="small text-muted mt-3 mb-0"><i class="bi bi-shield-lock"></i> Files are stored outside the web root and can only be downloaded by authorised users.</p>
    </div></div>
    <?php endif; ?>
  </div>
</div>
