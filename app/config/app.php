<?php
declare(strict_types=1);

use App\Core\Env;

/**
 * Infrastructure configuration only. Business configuration (company profile,
 * prefixes, payment methods, statuses ...) lives in the database.
 */
return [
    'name'     => Env::get('APP_NAME', 'CRM'),
    'env'      => Env::get('APP_ENV', 'production'),
    'debug'    => Env::bool('APP_DEBUG', false),
    'url'      => rtrim((string) Env::get('APP_URL', ''), '/'),
    'timezone' => Env::get('APP_TIMEZONE', 'Asia/Dhaka'),
    'force_https' => Env::bool('FORCE_HTTPS', false),

    'db' => [
        'host'     => Env::get('DB_HOST', '127.0.0.1'),
        'port'     => (int) Env::get('DB_PORT', '3306'),
        'database' => Env::get('DB_DATABASE', ''),
        'username' => Env::get('DB_USERNAME', ''),
        'password' => Env::get('DB_PASSWORD', ''),
    ],

    'session' => [
        'name'             => Env::get('SESSION_NAME', 'lakecrm'),
        'idle_timeout'     => (int) Env::get('SESSION_IDLE_MINUTES', '30') * 60,
        'absolute_timeout' => (int) Env::get('SESSION_MAX_HOURS', '12') * 3600,
    ],

    'auth' => [
        'max_attempts'    => 5,   // failed attempts per identifier+IP
        'max_ip_attempts' => 25,  // failed attempts per IP
        'lockout_minutes' => 15,
        'reset_token_minutes' => 60,
    ],

    'upload' => [
        'max_size_mb' => (int) Env::get('UPLOAD_MAX_MB', '10'),
    ],

    'mail' => [
        'driver'     => Env::get('MAIL_DRIVER', 'none'), // none | mail | smtp
        'host'       => Env::get('MAIL_HOST', ''),
        'port'       => (int) Env::get('MAIL_PORT', '587'),
        'username'   => Env::get('MAIL_USERNAME', ''),
        'password'   => Env::get('MAIL_PASSWORD', ''),
        'encryption' => Env::get('MAIL_ENCRYPTION', 'tls'), // tls | ssl | none
        'from'       => Env::get('MAIL_FROM', ''),
        'from_name'  => Env::get('MAIL_FROM_NAME', ''),
    ],

    'per_page' => 20,
];
