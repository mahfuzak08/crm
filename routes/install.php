<?php
declare(strict_types=1);

/** Routes available ONLY before installation (see public/index.php). */

use App\Controllers\InstallController;

/** @var App\Core\Router $router */
$router->get('/install', [InstallController::class, 'index']);
$router->post('/install/database', [InstallController::class, 'database']);
$router->post('/install/setup', [InstallController::class, 'setup']);
