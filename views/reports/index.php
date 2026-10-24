<div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-3">
<?php foreach ($reports as $key => [$label, $desc, $icon]): if (!can(App\Services\ReportService::REQUIRES[$key] ?? 'reports.view')) continue; ?>
  <div class="col"><a href="<?= url('/reports/' . $key) ?>" class="card h-100 text-reset">
    <div class="card-body d-flex gap-3 align-items-start">
      <span class="stat-icon"><i class="bi bi-<?= e($icon) ?>"></i></span>
      <div><div class="fw-semibold"><?= e($label) ?></div><div class="small text-muted"><?= e($desc) ?></div></div>
    </div></a></div>
<?php endforeach; ?>
</div>
