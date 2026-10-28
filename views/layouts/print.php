<?php
/** Print/PDF layout. Vars: $content, $company, $title, optional $public; optional $sheets = [[docTitle, copyLabel, content], ...] (one page each) */
$brand = preg_match('/^#[0-9a-f]{6}$/i', (string) ($company['theme_color'] ?? '')) ? $company['theme_color'] : '#0f766e';
$tpl = setting_safe('invoice.template', 'classic');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title><?= e($title ?? 'Document') ?></title>
<link rel="icon" href="<?= asset('images/favicon.svg') ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons.min.css') ?>">
<link rel="stylesheet" href="<?= asset('css/print.css') ?>">
<style>:root{--brand:<?= e($brand) ?>}</style>
</head>
<body class="tpl-<?= e($tpl) ?>">
<div class="toolbar no-print">
  <?php if (empty($public)): ?><a href="#" class="tb-btn" data-back>&larr; Back</a><?php endif; ?>
  <button type="button" class="tb-btn primary" data-print>Print / Save as PDF</button>
</div>
<?php foreach ($sheets ?? [['docTitle' => $docTitle ?? '', 'content' => $content]] as $sheet): ?>
<div class="sheet">
  <?php if (!empty($sheet['copyLabel'])): ?><div class="copy-label"><?= e($sheet['copyLabel']) ?></div><?php endif; ?>
  <header class="doc-head">
    <div class="brand">
      <?php if (!empty($company['logo'])): ?><img src="<?= url('/media/logo/' . (int) $company['id']) ?>" alt="" class="logo"><?php endif; ?>
      <div>
        <div class="co-name"><?= e($company['name'] ?? '') ?></div>
        <div class="co-meta">
          <?= e($company['address'] ?? '') ?><br>
          <?= e(implode(' · ', array_filter([$company['phone'] ?? '', $company['email'] ?? '', $company['website'] ?? '']))) ?>
          <?php if (!empty($company['tax_info'])): ?><br><?= e($company['tax_info']) ?><?php endif; ?>
        </div>
      </div>
    </div>
    <div class="doc-title"><?= e($sheet['docTitle'] ?? '') ?></div>
  </header>
  <?= $sheet['content'] ?>
</div>
<?php endforeach; ?>
<script src="<?= asset('js/print.js') ?>"></script>
</body>
</html>
