<?php
/**
 * Create or promote an admin user (idempotent)
 *
 * Usage (local):
 *   php scripts/create_admin.php [username] [password] [--reset-password]
 *
 * Usage (docker):
 *   docker exec bilet-satin-alma php /var/www/html/scripts/create_admin.php [username] [password] [--reset-password]
 *
 * Defaults:
 *   username = admin
 *   password = admin123
 */

// Load environment variables from .env if present (same as public/index.php)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/models/User.php';

use App\Core\Database;
use App\Models\User;

function printLn(string $msg) { echo $msg . PHP_EOL; }
function errorLn(string $msg) { fwrite(STDERR, $msg . PHP_EOL); }

// Parse args
$argv = $_SERVER['argv'] ?? [];
array_shift($argv); // remove script name

$username = $argv[0] ?? 'admin';
$password = $argv[1] ?? 'admin123';
$resetPassword = in_array('--reset-password', $argv, true);

try {
    $db = Database::getInstance();

    // Ensure schema is present (Database::getInstance may have already initialized)
    $stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
    $stmt->execute();
    if ($stmt->fetch() === false) {
        // Attempt to initialize schema
        $schemaPath = __DIR__ . '/../database/schema.sql';
        if (!file_exists($schemaPath)) {
            throw new Exception("Database not initialized and schema.sql not found.");
        }
        $db->exec(file_get_contents($schemaPath));
        printLn('Database schema initialized.');
    }

    // Find existing user
    $existing = User::findByUsername($username);
    if ($existing) {
        // Promote to admin if needed
        $updates = [];
        if ($existing->role !== 'admin') {
            $existing->update(['role' => 'admin']);
            $updates[] = 'role=admin';
        }
        if ($resetPassword) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $upd = $db->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
            $upd->execute(['hash' => $hash, 'id' => $existing->id]);
            $updates[] = 'password reset';
        }
        if (empty($updates)) {
            printLn("User '{$username}' already exists and is up-to-date.");
        } else {
            printLn("User '{$username}' updated: " . implode(', ', $updates));
        }
        exit(0);
    }

    // Create new admin user
    $user = User::create([
        'username' => $username,
        'password' => $password,
        'role' => 'admin',
        'credit' => 0.0,
        'firma_id' => null,
    ]);

    printLn("Admin user created: username='{$user->username}' (id={$user->id})");
    exit(0);

} catch (Throwable $e) {
    errorLn('Error: ' . $e->getMessage());
    exit(1);
}



