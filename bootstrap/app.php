<?php
declare(strict_types=1);

/**
 * Application bootstrap: autoloader, environment, error handling, session.
 * Shared by the web front controller and CLI scripts.
 */

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('VIEW_PATH', BASE_PATH . '/views');
define('STORAGE_PATH', BASE_PATH . '/storage');

// PSR-4 autoloader: App\Foo\Bar -> app/Foo/Bar.php
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $file = APP_PATH . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require APP_PATH . '/Helpers/functions.php';

App\Core\Env::load(BASE_PATH . '/.env');

$config = require APP_PATH . '/config/app.php';
App\Core\Config::set($config);

date_default_timezone_set((string) config('timezone', 'UTC'));
mb_internal_encoding('UTF-8');

App\Core\ErrorHandler::register((bool) config('debug', false));
