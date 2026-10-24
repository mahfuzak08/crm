<?php
declare(strict_types=1);

/**
 * Front controller — the only PHP file reachable from the web.
 */
require dirname(__DIR__) . '/bootstrap/app.php';

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Services\InstallService;

Response::securityHeaders();

if (config('force_https') && !Request::isHttps()) {
    Response::redirect(preg_replace('#^http://#', 'https://', full_url(Request::path())), 301);
}

Session::start();

$path = Request::path();
$installed = InstallService::isInstalled();

// Until installed, everything routes to the installer; afterwards the installer is gone.
if (!$installed && !str_starts_with($path, '/install')) {
    Response::redirect('/install');
}

$router = new Router();
require BASE_PATH . '/routes/' . ($installed ? 'web.php' : 'install.php');
$router->dispatch(Request::method(), $path);
