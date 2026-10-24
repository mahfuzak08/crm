<?php
declare(strict_types=1);

/**
 * Command-line tool (not web accessible).
 *
 *   php cli.php migrate             Apply pending SQL migrations
 *   php cli.php sync-permissions    Sync permission catalog into the database
 *   php cli.php cron                Daily maintenance: overdue invoices, expired offers, log pruning
 *   php cli.php backup [keep=14]    Create a gzip SQL backup and keep the newest N
 *   php cli.php create-admin        Create an additional Super Admin interactively
 *   php cli.php link-customers      Add users holding the Customer role to the customer list (idempotent)
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/bootstrap/app.php';

use App\Core\Database;
use App\Services\BackupService;
use App\Services\InstallService;
use App\Services\RoleService;

$cmd = $argv[1] ?? 'help';
$out = static fn(string $m) => fwrite(STDOUT, $m . PHP_EOL);

switch ($cmd) {
    case 'migrate':
        $applied = InstallService::migrate(Database::pdo());
        $out($applied ? 'Applied: ' . implode(', ', $applied) : 'Nothing to migrate.');
        break;

    case 'sync-permissions':
        RoleService::syncPermissions();
        $out('Permissions synced.');
        break;

    case 'cron':
        $n1 = Database::run("UPDATE invoices SET status = 'overdue' WHERE status IN ('unpaid', 'partial') AND due_date < CURDATE() AND due_amount > 0 AND deleted_at IS NULL")->rowCount();
        $n2 = Database::run("UPDATE offers SET status = 'expired' WHERE status IN ('draft', 'sent', 'viewed') AND valid_until < CURDATE() AND deleted_at IS NULL")->rowCount();
        $n3 = Database::run('DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL 180 DAY)')->rowCount();
        $n4 = Database::run('DELETE FROM password_resets WHERE expires_at < NOW() - INTERVAL 1 DAY OR used_at IS NOT NULL')->rowCount();
        $out("Overdue invoices: $n1, expired offers: $n2, pruned login attempts: $n3, pruned reset tokens: $n4");
        break;

    case 'backup':
        $keep = (int) (explode('=', $argv[2] ?? 'keep=14')[1] ?? 14);
        $name = BackupService::create();
        $pruned = BackupService::prune(max(1, $keep));
        $out("Backup created: storage/backups/$name (pruned $pruned old)");
        break;

    case 'create-admin':
        $ask = static function (string $q): string { fwrite(STDOUT, $q . ': '); return trim((string) fgets(STDIN)); };
        $company = (int) (Database::value('SELECT id FROM companies WHERE deleted_at IS NULL ORDER BY id LIMIT 1') ?? 0);
        if (!$company) { $out('Run the web installer first.'); exit(1); }
        $name = $ask('Full name'); $email = strtolower($ask('Email')); $username = $ask('Username');
        fwrite(STDOUT, 'Password (min 8, letters+numbers): '); system('stty -echo'); $pass = trim((string) fgets(STDIN)); system('stty echo'); fwrite(STDOUT, PHP_EOL);
        if (strlen($pass) < 8 || !preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9_.\-]{3,50}$/', $username)) {
            $out('Invalid input.'); exit(1);
        }
        $id = Database::insert('users', ['company_id' => $company, 'name' => $name, 'email' => $email, 'username' => $username,
            'password' => password_hash($pass, PASSWORD_DEFAULT), 'status' => 'active', 'password_changed_at' => date('Y-m-d H:i:s')]);
        Database::insert('user_roles', ['user_id' => $id, 'role_id' => (int) Database::value("SELECT id FROM roles WHERE slug = 'super_admin' AND company_id IS NULL")]);
        App\Services\AuditLogService::log('CREATE_USER', 'users', $id, null, ['username' => $username, 'via' => 'cli'], $company);
        $out("Super Admin '$username' created.");
        break;

    case 'link-customers':
        $users = Database::fetchAll(
            "SELECT DISTINCT u.id, u.company_id, u.name FROM users u JOIN user_roles ur ON ur.user_id = u.id JOIN roles r ON r.id = ur.role_id
             WHERE r.slug = 'customer' AND u.deleted_at IS NULL ORDER BY u.id"
        );
        foreach ($users as $u) {
            $cid = Database::transaction(static fn() => App\Services\CustomerService::syncFromUser((int) $u['id'], (int) $u['company_id']));
            $out("User #{$u['id']} {$u['name']} -> customer #$cid");
        }
        $out(count($users) . ' user(s) processed.');
        break;

    default:
        $out("Usage: php cli.php [migrate|sync-permissions|cron|backup [keep=14]|create-admin|link-customers]");
}
