#!/usr/bin/env php
<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err !== null && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        fwrite(STDERR, "[bootstrap] FATAL {$err['message']} at {$err['file']}:{$err['line']}\n");
    }
});

set_error_handler(function ($severity, $message, $file, $line) {
    fwrite(STDERR, "[bootstrap] PHP_ERROR ({$severity}) {$message} at {$file}:{$line}\n");
    return false;
});

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

function bootstrapLog(string $level, string $message): void
{
    $ts = date('Y-m-d\TH:i:s\Z');
    $stream = ($level === 'ERROR') ? STDERR : STDOUT;
    fwrite($stream, "[bootstrap] {$ts} {$level} {$message}\n");
}

$projectRoot = dirname(__DIR__);

$envFile = $projectRoot . '/.env';
if (file_exists($envFile)) {
    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $envLine) {
        $envLine = trim($envLine);
        if ($envLine === '' || $envLine[0] === '#') continue;
        if (strpos($envLine, '=') === false) continue;
        [$envKey, $envVal] = explode('=', $envLine, 2);
        $envKey = trim($envKey);
        $envVal = trim(trim($envVal), "\"'");
        if (!getenv($envKey)) {
            putenv("{$envKey}={$envVal}");
        }
    }
}

$requiredEnv = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'SECRET_SALT'];
foreach ($requiredEnv as $envName) {
    if ((string)getenv($envName) === '') {
        fwrite(STDERR, "[bootstrap] ERROR {$envName} required\n");
        exit(2);
    }
}

$adminPasswordHash = (string)getenv('ADMIN_PASSWORD_HASH');
if ($adminPasswordHash !== '') {
    if (!preg_match('/^\$2[aby]\$\d{2}\$.{53}$/', $adminPasswordHash)) {
        fwrite(STDERR, "[bootstrap] ERROR ADMIN_PASSWORD_HASH must be bcrypt (use password_hash to generate bcrypt before passing)\n");
        exit(2);
    }
}

$dbHost = (string)getenv('DB_HOST');
$dbName = (string)getenv('DB_NAME');
$dbUser = (string)getenv('DB_USER');
$dbPass = (string)getenv('DB_PASS');

$connected = false;
for ($attempt = 1; $attempt <= 30; $attempt++) {
    $conn = @mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);
    if ($conn) {
        $connected = true;
        break;
    }
    bootstrapLog('INFO', "waiting for mysql (attempt {$attempt}/30)");
    sleep(1);
}

if (!$connected) {
    bootstrapLog('ERROR', 'could not connect to mysql after 30 attempts');
    exit(1);
}

mysqli_set_charset($conn, 'utf8mb4');

$lockResult = mysqli_query($conn, "SELECT GET_LOCK('tickethub_bootstrap', 30) AS got");
if (!$lockResult) {
    bootstrapLog('ERROR', 'GET_LOCK query failed');
    exit(1);
}
$lockRow = mysqli_fetch_assoc($lockResult);
if ((int)($lockRow['got'] ?? 0) !== 1) {
    bootstrapLog('ERROR', 'could not acquire bootstrap lock (another bootstrap in progress)');
    exit(1);
}

bootstrapLog('INFO', 'lock acquired');

define('ROOT_PATH', $projectRoot . '/');
define('INCLUDE_DIR', $projectRoot . '/include/');

$GLOBALS['__db'] = $conn;

$configFile = $projectRoot . '/include/th-config.php';
if (!file_exists($configFile)) {
    $sampleFile = $projectRoot . '/include/th-config.sample.php';
    if (file_exists($sampleFile)) {
        copy($sampleFile, $configFile);
    }
}

if (file_exists($configFile)) {
    require_once $configFile;
}

if (!defined('TABLE_PREFIX')) {
    $prefix = (string)getenv('DB_PREFIX') ?: 'th_';
    define('TABLE_PREFIX', $prefix);
}

if (!defined('APP_VERSION')) {
    define('APP_VERSION', '1.0');
}

if (!function_exists('db_query')) {
    function db_query(string $sql, string $database = '', mixed $conn = ''): mixed
    {
        global $__db;
        $link = $conn ? $conn : $__db;
        if ($database) {
            mysqli_select_db($link, $database);
        }
        return @mysqli_query($link, $sql);
    }
}

if (!function_exists('db_input')) {
    function db_input(string $value): string
    {
        global $__db;
        return "'" . mysqli_real_escape_string($__db, $value) . "'";
    }
}

if (!function_exists('db_fetch_array')) {
    function db_fetch_array(mixed $result, mixed $mode = false): ?array
    {
        if (!$result) return null;
        $row = mysqli_fetch_array($result, $mode ? $mode : MYSQLI_ASSOC);
        return $row ?: null;
    }
}

if (!function_exists('db_real_escape')) {
    function db_real_escape(string $value): string
    {
        global $__db;
        return mysqli_real_escape_string($__db, $value);
    }
}

if (!function_exists('db_error')) {
    function db_error(): string
    {
        global $__db;
        return mysqli_error($__db);
    }
}

if (!function_exists('db_errno')) {
    function db_errno(): int
    {
        global $__db;
        return mysqli_errno($__db);
    }
}

$migrationFile = $projectRoot . '/setup/install/migration.php';

$bootstrapExitCode = 0;
try {
    if (!file_exists($migrationFile)) {
        bootstrapLog('ERROR', 'migration.php not found at ' . $migrationFile);
        $bootstrapExitCode = 1;
        exit(1);
    }

    if (!defined('MIGRATIONS_TABLE')) {
        define('MIGRATIONS_TABLE', TABLE_PREFIX . 'migrations');
    }

    if (!defined('MIGRATIONS_DIR')) {
        define('MIGRATIONS_DIR', $projectRoot . '/setup/install/migrations/');
    }

    bootstrapLog('INFO', 'requiring migration manager');
    require_once $migrationFile;

    bootstrapLog('INFO', 'creating migration manager');
    $manager = new MigrationManager();

    bootstrapLog('INFO', 'running migrations');
    $migrationResult = $manager->runMigrations();

    if (!empty($migrationResult['errors'])) {
        foreach ($migrationResult['errors'] as $err) {
            bootstrapLog('ERROR', $err);
        }
        $bootstrapExitCode = 1;
        exit(1);
    }

    bootstrapLog('INFO', "migrations: applied={$migrationResult['applied']} skipped={$migrationResult['skipped']}");

    $staffCountResult = mysqli_query($conn, "SELECT COUNT(*) AS c FROM " . TABLE_PREFIX . "staff");
    $staffRow = $staffCountResult ? mysqli_fetch_assoc($staffCountResult) : null;
    $staffCount = (int)($staffRow['c'] ?? 0);

    if ($staffCount === 0) {
        $adminUsername   = (string)getenv('ADMIN_USERNAME');
        $firstAdminEmail = (string)getenv('FIRST_ADMIN_EMAIL');

        if ($adminUsername !== '' && $firstAdminEmail !== '' && $adminPasswordHash !== '') {
            $safeUsername = mysqli_real_escape_string($conn, $adminUsername);
            $safeEmail    = mysqli_real_escape_string($conn, $firstAdminEmail);
            $safeHash     = mysqli_real_escape_string($conn, $adminPasswordHash);
            $tzOffset     = (int)(date('Z') / 3600);

            $insertSql = "INSERT INTO " . TABLE_PREFIX . "staff SET
                created=NOW(), isadmin=1, change_passwd=0,
                group_id=1, dept_id=1,
                email='{$safeEmail}',
                firstname='Admin', lastname='Admin',
                username='{$safeUsername}',
                passwd='{$safeHash}',
                timezone_offset={$tzOffset}";

            if (mysqli_query($conn, $insertSql)) {
                bootstrapLog('INFO', 'seeded first admin from env');
            } else {
                bootstrapLog('ERROR', 'failed to seed admin: ' . mysqli_error($conn));
                $bootstrapExitCode = 1;
                exit(1);
            }
        } else {
            bootstrapLog('WARN', 'th_staff is empty and ADMIN_USERNAME/FIRST_ADMIN_EMAIL/ADMIN_PASSWORD_HASH not set — run bin/first-admin.php');
        }
    }

    $safeVersion = mysqli_real_escape_string($conn, APP_VERSION);
    mysqli_query($conn, "INSERT IGNORE INTO " . TABLE_PREFIX . "config (id, updated, thversion) VALUES (1, NOW(), '{$safeVersion}')");
    $updateResult = mysqli_query($conn, "UPDATE " . TABLE_PREFIX . "config SET thversion='{$safeVersion}' WHERE id=1");
    if (!$updateResult) {
        bootstrapLog('WARN', 'could not update thversion: ' . mysqli_error($conn));
    }
} finally {
    mysqli_query($conn, "SELECT RELEASE_LOCK('tickethub_bootstrap')");
    bootstrapLog('INFO', 'lock released');
}

echo "bootstrap ok\n";
exit(0);
