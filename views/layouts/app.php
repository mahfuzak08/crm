<?php
/** @var string $content */
$user = auth();
$co = company();
$brand = preg_match('/^#[0-9a-f]{6}$/i', (string) ($co['theme_color'] ?? '')) ? $co['theme_color'] : '#0f766e';
$path = App\Core\Request::path();
$menu = require APP_PATH . '/config/menu.php';
$companies = App\Services\Tenant::allowedCompanies();
// Highlight only the single best-matching menu link (longest URL wins), so
// /master-data/payment_method lights up "Payment Methods" and not also "Master Data".
$matches = static function (string $u) use ($path): bool {
    $q = strstr($u, '?');
    if ($q !== false && !str_contains($_SERVER['REQUEST_URI'] ?? '', $q)) {
        return false;
    }
    $u = strtok($u, '?');
    return $u === '/' ? $path === '/' : ($path === $u || str_starts_with($path, rtrim($u, '/') . '/'));
};
$activeUrl = null;
foreach ($menu as $item) {
    foreach ($item['children'] ?? [$item] as $c) {
        if (can($c['perm']) && $matches($c['url']) && strlen($c['url']) > strlen((string) $activeUrl)) {
            $activeUrl = $c['url'];
        }
    }
}
$isActive = static fn(string $u): bool => $u === $activeUrl;
// Phone bottom tab bar: the first four sections the user may open, then "More" (opens the full menu).
$tabs = array_slice(array_values(array_filter([
    ['Home', 'house', '/', 'dashboard.view'],
    ['Leads', 'people', '/leads', 'leads.view'],
    ['Customers', 'person-vcard', '/customers', 'customers.view'],
    ['Sales', 'cart-check', '/sales', 'sales.view'],
    ['Invoices', 'receipt', '/invoices', 'invoices.view'],
    ['Payments', 'cash-coin', '/payments', 'payments.view'],
    ['Projects', 'buildings', '/projects', 'projects.view'],
], static fn($t) => can($t[3]))), 0, 4);
$tabActive = static fn(string $u): bool => $u === '/' ? $path === '/' : ($path === $u || str_starts_with($path, $u . '/'));
$isHome = $path === '/';
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="<?= e($brand) ?>">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?= e($co['name'] ?? config('name')) ?>">
<link rel="manifest" href="<?= url('/manifest.webmanifest') ?>">
<link rel="apple-touch-icon" href="<?= asset('images/favicon.svg') ?>">
<meta name="csrf-token" content="<?= csrf_token() ?>">
<meta name="base-url" content="<?= e(base_path_prefix()) ?>">
<title><?= e(($title ?? 'Dashboard') . ' · ' . ($co['name'] ?? config('name'))) ?></title>
<link rel="icon" href="<?= asset('images/favicon.svg') ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= asset('vendor/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons.min.css') ?>">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<style>:root{--brand:<?= e($brand) ?>}</style>
</head>
<body class="<?= $isHome ? 'is-home' : '' ?>">
<div class="app">
  <aside class="sidebar" id="sidebar">
    <a class="sidebar-brand" href="<?= url('/') ?>">
      <?php if (!empty($co['logo'])): ?>
        <img src="<?= url('/media/logo/' . (int) $co['id']) ?>" alt="" class="brand-logo">
      <?php else: ?>
        <span class="brand-mark"><i class="bi bi-buildings"></i></span>
      <?php endif; ?>
      <span class="brand-text"><?= e($co['name'] ?? config('name')) ?><small>CRM</small></span>
    </a>
    <nav class="sidebar-nav">
      <?php foreach ($menu as $i => $item):
          if (isset($item['children'])) {
              $kids = array_filter($item['children'], static fn($c) => can($c['perm']) && (empty($c['super']) || App\Services\AuthService::isSuperAdmin()));
              if (!$kids) continue;
              $open = (bool) array_filter($kids, static fn($c) => $isActive($c['url']));
              ?>
          <div class="nav-group<?= $open ? ' open' : '' ?>">
            <button type="button" class="nav-link nav-toggle" data-toggle-group aria-expanded="<?= $open ? 'true' : 'false' ?>">
              <i class="bi bi-<?= e($item['icon']) ?>"></i><span><?= e($item['label']) ?></span><i class="bi bi-chevron-down caret"></i>
            </button>
            <div class="nav-sub">
              <?php foreach ($kids as $c): ?>
                <a class="nav-link<?= $isActive($c['url']) ? ' active' : '' ?>" href="<?= url($c['url']) ?>"><?= e($c['label']) ?></a>
              <?php endforeach; ?>
            </div>
          </div>
      <?php } elseif (can($item['perm'])) { ?>
          <a class="nav-link<?= $isActive($item['url']) ? ' active' : '' ?>" href="<?= url($item['url']) ?>"><i class="bi bi-<?= e($item['icon']) ?>"></i><span><?= e($item['label']) ?></span></a>
      <?php } endforeach; ?>
    </nav>
  </aside>
  <div class="sidebar-backdrop" data-sidebar-close></div>

  <div class="main">
    <header class="topbar">
      <?php if (!$isHome): ?><a class="btn btn-icon appbar-back" href="<?= url('/') ?>" data-back aria-label="Back"><i class="bi bi-arrow-left"></i></a><?php endif; ?>
      <button class="btn btn-icon d-lg-none<?= $isHome ? '' : ' appbar-hide' ?>" type="button" data-sidebar-open aria-label="Menu"><i class="bi bi-list"></i></button>
      <div class="appbar-title"><?= e($isHome ? ($co['name'] ?? config('name')) : ($title ?? '')) ?></div>
      <a class="topbar-brand d-lg-none" href="<?= url('/') ?>">
        <?php if (!empty($co['logo'])): ?>
          <img src="<?= url('/media/logo/' . (int) $co['id']) ?>" alt="" class="brand-logo">
        <?php else: ?>
          <span class="brand-mark"><i class="bi bi-buildings"></i></span>
        <?php endif; ?>
        <span class="topbar-brand-name"><?= e($co['name'] ?? config('name')) ?></span>
      </a>
      <form class="topbar-search d-none d-md-flex" id="topSearch" action="<?= url('/search') ?>" method="get" role="search">
        <i class="bi bi-search"></i>
        <input type="search" name="q" class="form-control" placeholder="Search leads, customers, invoices…" value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off">
      </form>
      <div class="ms-auto d-flex align-items-center gap-2">
        <button class="btn btn-icon appbar-only" type="button" data-search-toggle aria-label="Search"><i class="bi bi-search"></i></button>
        <?php if (count($companies) > 1): ?>
          <form method="post" action="<?= url('/company/switch') ?>" class="d-none d-sm-block">
            <?= csrf_field() ?>
            <select name="company_id" class="form-select form-select-sm" data-autosubmit aria-label="Active company">
              <?php foreach ($companies as $c): ?>
                <option value="<?= (int) $c['id'] ?>"<?= selected($c['id'], $co['id'] ?? 0) ?>><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        <?php endif; ?>
        <div class="dropdown">
          <button class="btn btn-user dropdown-toggle" data-bs-toggle="dropdown" type="button">
            <span class="avatar"><?= e(initials($user['name'] ?? '')) ?></span>
            <span class="d-none d-sm-inline"><?= e($user['name'] ?? '') ?></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li class="dropdown-header"><?= e(implode(', ', array_column($user['roles'] ?? [], 'name'))) ?></li>
            <li><a class="dropdown-item" href="<?= url('/profile') ?>"><i class="bi bi-person me-2"></i>My Profile</a></li>
            <li><a class="dropdown-item" href="<?= url('/profile/password') ?>"><i class="bi bi-key me-2"></i>Change Password</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <form method="post" action="<?= url('/logout') ?>"><?= csrf_field() ?>
                <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button>
              </form>
            </li>
          </ul>
        </div>
      </div>
    </header>

    <main class="content">
      <?php if (!empty($breadcrumbs) || !empty($title)): ?>
      <div class="page-head">
        <div>
          <?php if (!empty($breadcrumbs)): ?>
          <nav aria-label="breadcrumb"><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url('/') ?>">Home</a></li>
            <?php foreach ($breadcrumbs as $label => $link): ?>
              <?php if ($link): ?><li class="breadcrumb-item"><a href="<?= url($link) ?>"><?= e($label) ?></a></li>
              <?php else: ?><li class="breadcrumb-item active"><?= e($label) ?></li><?php endif; ?>
            <?php endforeach; ?>
          </ol></nav>
          <?php endif; ?>
          <h1 class="page-title"><?= e($title ?? '') ?></h1>
        </div>
        <?php if ($pageActions = App\Core\View::section('actions')): ?><div class="page-actions"><?= $pageActions ?></div><?php endif; ?>
      </div>
      <?php endif; ?>
      <?= partial('partials/alerts') ?>
      <?= $content ?>
    </main>
    <?php if ($tabs): ?>
    <nav class="tabbar" aria-label="Main">
      <?php foreach ($tabs as [$label, $icon, $link]): $on = $tabActive($link); ?>
        <a href="<?= url($link) ?>" class="<?= $on ? 'active' : '' ?>"<?= $on ? ' aria-current="page"' : '' ?>><i class="bi bi-<?= $icon ?><?= $on ? '-fill' : '' ?>"></i><span><?= $label ?></span></a>
      <?php endforeach; ?>
      <button type="button" data-sidebar-open><i class="bi bi-grid"></i><span>More</span></button>
    </nav>
    <?php endif; ?>
    <footer class="footer">
      <span>&copy; <?= date('Y') ?> <?= e($co['name'] ?? '') ?></span>
      <span class="text-muted"><?= e(config('name')) ?></span>
    </footer>
  </div>
</div>
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toasts"></div>
<script src="<?= asset('vendor/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
