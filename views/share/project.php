<?php
use App\Services\GalleryService;
use App\Services\ShareLinkService;
$brand = preg_match('/^#[0-9a-f]{6}$/i', (string) $company['theme_color']) ? $company['theme_color'] : '#0f766e';
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title><?= e($project['project_name'] . ' · ' . $company['name']) ?></title>
<link rel="icon" href="<?= asset('images/favicon.svg') ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= asset('vendor/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons.min.css') ?>">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<style>:root{--brand:<?= e($brand) ?>}</style>
</head>
<body>
<header class="py-4 text-white" style="background:var(--brand)">
  <div class="container d-flex align-items-center gap-3">
    <?php if ($company['logo']): ?><img src="<?= url('/media/logo/' . (int) $company['id']) ?>" alt="" class="brand-logo-lg"><?php endif; ?>
    <div><div class="small opacity-75"><?= e($company['name']) ?></div><h1 class="h3 mb-0"><?= e($project['project_name']) ?></h1>
      <?php if ($project['location']): ?><div class="small opacity-75"><i class="bi bi-geo-alt"></i> <?= e($project['location']) ?></div><?php endif; ?></div>
  </div>
</header>
<main class="container py-4">
  <div class="row g-4">
    <div class="col-lg-8">
      <?php if ($project['description']): ?><div class="card mb-4"><div class="card-body" style="white-space:pre-line"><?= e($project['description']) ?></div></div><?php endif; ?>
      <div class="gallery-grid">
        <?php foreach ($items as $it):
            $src = $it['stored_name'] ? ShareLinkService::path('media', (int) $it['id'], $expires) : null;
            $embed = $it['type'] === 'video' ? GalleryService::embedUrl($it['video_url']) : null; ?>
          <div class="gallery-item">
            <?php if ($it['type'] === 'image'): ?>
              <a class="gallery-thumb" href="<?= e($src) ?>" target="_blank"><img src="<?= e($src) ?>" alt="<?= e($it['title']) ?>" loading="lazy"></a>
            <?php elseif ($embed): ?>
              <div class="ratio ratio-4x3"><iframe src="<?= e($embed) ?>" title="<?= e($it['title']) ?>" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe></div>
            <?php elseif ($it['type'] === 'video' && $src): ?>
              <video class="w-100" controls preload="metadata" src="<?= e($src) ?>"></video>
            <?php else: ?>
              <a class="gallery-thumb" href="<?= e($src) ?>" target="_blank"><i class="bi bi-file-earmark-text"></i></a>
            <?php endif; ?>
            <div class="meta"><div class="fw-medium"><?= e($it['title']) ?></div><?php if ($it['description']): ?><div class="text-muted small"><?= e($it['description']) ?></div><?php endif; ?></div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if (!$items): ?><p class="text-muted">Gallery coming soon.</p><?php endif; ?>
    </div>
    <div class="col-lg-4">
      <div class="card mb-3"><div class="card-header">Project facts</div><div class="card-body">
        <dl class="dl-grid">
          <?php if ($project['total_land_area']): ?><dt>Land area</dt><dd><?= e(rtrim(rtrim((string) $project['total_land_area'], '0'), '.') . ' ' . $project['land_unit']) ?></dd><?php endif; ?>
          <dt>Total shares</dt><dd><?= number_format((int) $project['total_shares']) ?></dd>
          <?php if ($project['share_unit']): ?><dt>Share unit</dt><dd><?= e($project['share_unit']) ?></dd><?php endif; ?>
          <dt>Price / share</dt><dd class="fw-semibold"><?= e($company['currency_symbol']) ?> <?= e(number_format((float) $project['share_price'], 2)) ?></dd>
          <?php if ($project['expected_completion_date']): ?><dt>Completion</dt><dd><?= e(date('M Y', strtotime($project['expected_completion_date']))) ?></dd><?php endif; ?>
        </dl>
      </div></div>
      <?php if ($docs): ?>
      <div class="card mb-3"><div class="card-header">Documents</div><ul class="list-group list-group-flush">
        <?php foreach ($docs as $d): ?><li class="list-group-item"><i class="bi bi-file-earmark-arrow-down me-1"></i><a href="<?= e(ShareLinkService::path('document', (int) $d['id'], $expires)) ?>" target="_blank"><?= e($d['title']) ?></a></li><?php endforeach; ?>
      </ul></div>
      <?php endif; ?>
      <div class="card"><div class="card-body small">
        <div class="fw-semibold mb-1">Contact us</div>
        <?php if ($company['phone']): ?><div><i class="bi bi-telephone"></i> <a href="tel:<?= e(phone_digits($company['phone'])) ?>"><?= e($company['phone']) ?></a></div><?php endif; ?>
        <?php if ($company['email']): ?><div><i class="bi bi-envelope"></i> <a href="mailto:<?= e($company['email']) ?>"><?= e($company['email']) ?></a></div><?php endif; ?>
        <?php if ($company['address']): ?><div class="text-muted mt-1"><?= e($company['address']) ?></div><?php endif; ?>
      </div></div>
    </div>
  </div>
</main>
<footer class="text-center text-muted small py-4">&copy; <?= date('Y') ?> <?= e($company['name']) ?> · Link valid until <?= e(date('d M Y', $expires)) ?></footer>
</body>
</html>
