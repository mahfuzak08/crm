<?php
use App\Services\GalleryService;
App\Core\View::start('actions'); ?>
<?php if ($shareUrl): ?>
  <button type="button" class="btn btn-light" data-copy="<?= e($shareUrl) ?>"><i class="bi bi-link-45deg me-1"></i>Copy public link</button>
  <a href="<?= e($shareUrl) ?>" target="_blank" rel="noopener" class="btn btn-light"><i class="bi bi-box-arrow-up-right me-1"></i>Preview</a>
<?php endif; ?>
<?php App\Core\View::end(); ?>

<form method="get" action="<?= url('/gallery') ?>" class="mb-3" style="max-width:420px">
  <select name="project" class="form-select" data-autosubmit>
    <?php foreach ($projects as $id => $n): ?><option value="<?= (int) $id ?>"<?= selected($id, $project['id'] ?? '') ?>><?= e($n) ?></option><?php endforeach; ?>
    <?php if (!$projects): ?><option>No projects yet</option><?php endif; ?>
  </select>
</form>

<?php if ($project): ?>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="gallery-grid">
      <?php foreach ($items as $it): $thumb = $it['type'] === 'video' ? GalleryService::youtubeThumb($it['video_url']) : null; ?>
        <div class="gallery-item<?= $it['status'] !== 'active' ? ' opacity-50' : '' ?>">
          <a class="gallery-thumb" href="<?= $it['stored_name'] ? url('/gallery/' . $it['id'] . '/file') : e($it['video_url']) ?>" target="_blank" rel="noopener">
            <?php if ($it['type'] === 'image'): ?><img src="<?= url('/gallery/' . $it['id'] . '/file') ?>" alt="<?= e($it['title']) ?>" loading="lazy">
            <?php elseif ($thumb): ?><img src="<?= e($thumb) ?>" alt="" loading="lazy">
            <?php else: ?><i class="bi bi-<?= $it['type'] === 'video' ? 'play-btn' : 'file-earmark-text' ?>"></i><?php endif; ?>
          </a>
          <div class="meta">
            <div class="fw-medium text-truncate" title="<?= e($it['title']) ?>"><?= e($it['title']) ?></div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-muted text-capitalize"><?= e($it['type']) ?> · #<?= (int) $it['sort_order'] ?></span>
              <?php if (can('gallery.manage')): ?>
              <span>
                <a href="<?= url('/gallery', ['project' => $project['id'], 'edit' => $it['id']]) ?>" class="btn btn-sm btn-link p-0"><i class="bi bi-pencil"></i></a>
                <?= post_button('/gallery/' . $it['id'] . '/delete', '', ['html' => '<i class="bi bi-trash"></i>', 'class' => 'btn btn-sm btn-link text-danger p-0', 'confirm' => 'Delete this item?']) ?>
              </span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if (!$items): ?><div class="card"><div class="empty"><i class="bi bi-images"></i>No gallery items for this project yet.</div></div><?php endif; ?>
  </div>
  <div class="col-lg-4">
    <?php if (can('gallery.manage')): ?>
    <div class="card"><div class="card-header"><?= $editing ? 'Edit item' : 'Add to gallery' ?></div><div class="card-body">
      <form method="post" action="<?= url('/gallery/project/' . $project['id'] . ($editing ? '/' . $editing['id'] : '')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="row g-3">
          <?= form_input('title', 'Title', $editing, ['required' => true, 'col' => 'col-12']) ?>
          <?= form_select('type', 'Type', ['image' => 'Image', 'video' => 'Video', 'document' => 'Document'], $editing, ['col' => 'col-6']) ?>
          <?= form_input('sort_order', 'Order', $editing, ['type' => 'number', 'col' => 'col-6']) ?>
          <?= form_textarea('description', 'Description', $editing, ['rows' => 2]) ?>
          <div class="col-12"><label class="form-label">File</label><input type="file" name="file" class="form-control<?= invalid('file') ?>"><?= error_for('file') ?>
            <div class="form-text">Images: jpg, png, webp · Documents: pdf, doc(x), xls(x) · Video: mp4/webm (or use a link)</div></div>
          <?= form_input('video_url', 'Video link (YouTube / Vimeo)', $editing, ['col' => 'col-12', 'placeholder' => 'https://youtube.com/watch?v=…']) ?>
          <?= form_select('status', 'Status', ['active' => 'Visible', 'inactive' => 'Hidden'], $editing, ['col' => 'col-12']) ?>
        </div>
        <div class="mt-3 d-flex gap-2"><button class="btn btn-brand"><?= $editing ? 'Update' : 'Add item' ?></button>
          <?php if ($editing): ?><a href="<?= url('/gallery', ['project' => $project['id']]) ?>" class="btn btn-light">Cancel</a><?php endif; ?></div>
      </form>
    </div></div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
