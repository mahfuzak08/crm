<?php
/** @var string $content */
$co = [];
try {
    if (App\Services\InstallService::isInstalled()) {
        $co = App\Core\Database::fetch('SELECT id, name, logo, theme_color FROM companies WHERE deleted_at IS NULL ORDER BY id LIMIT 1') ?? [];
    }
} catch (Throwable) {
}
$brand = preg_match('/^#[0-9a-f]{6}$/i', (string) ($co['theme_color'] ?? '')) ? $co['theme_color'] : '#0f766e';
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= csrf_token() ?>">
<title><?= e(($title ?? 'Sign in') . ' · ' . config('name')) ?></title>
<link rel="icon" href="<?= asset('images/favicon.svg') ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= asset('vendor/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons.min.css') ?>">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<style>:root{--brand:<?= e($brand) ?>}</style>
</head>
<body class="guest">
<div class="guest-wrap<?= !empty($wide) ? ' wide' : '' ?>">
  <div class="guest-brand">
    <?php if (!empty($co['logo'])): ?>
      <img src="<?= url('/media/logo/' . (int) $co['id']) ?>" alt="" class="brand-logo-lg">
    <?php else: ?>
      <span class="brand-mark lg"><i class="bi bi-buildings"></i></span>
    <?php endif; ?>
    <div>
      <div class="fw-semibold"><?= e($co['name'] ?? config('name')) ?></div>
      <small class="text-muted">Customer Relationship Management</small>
    </div>
  </div>
  <div class="card guest-card">
    <div class="card-body p-4">
      <?= partial('partials/alerts') ?>
      <?= $content ?>
    </div>
  </div>
  <p class="text-center text-muted small mt-3">&copy; <?= date('Y') ?> <?= e(config('name')) ?></p>
</div>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
