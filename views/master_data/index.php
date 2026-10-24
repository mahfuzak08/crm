<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-3">
<?php foreach ($types as $key => [$label, $singular]): ?>
  <div class="col">
    <a href="<?= url('/master-data/' . $key) ?>" class="card h-100 text-reset">
      <div class="card-body d-flex align-items-center gap-3">
        <span class="stat-icon slate"><i class="bi bi-list-ul"></i></span>
        <div><div class="fw-semibold"><?= e($label) ?></div><div class="text-muted small"><?= (int) ($counts[$key] ?? 0) ?> items</div></div>
        <i class="bi bi-chevron-right ms-auto text-muted"></i>
      </div>
    </a>
  </div>
<?php endforeach; ?>
</div>
