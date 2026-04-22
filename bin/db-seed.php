#!/usr/bin/env php
<?php
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

if (count($argv) < 2) {
    fwrite(STDERR, "Usage: php bin/db-seed.php <seed_name> [--dry-run]\n");
    exit(1);
}

$seedName  = $argv[1];
$isDryRun  = in_array('--dry-run', $argv, true);

if (!preg_match('/^[a-zA-Z0-9_]+$/', $seedName)) {
    fwrite(STDERR, "ERROR: seed_name must contain only letters, digits, and underscores\n");
    exit(1);
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

$dbHost = (string)getenv('DB_HOST') ?: 'localhost';
$dbName = (string)getenv('DB_NAME') ?: 'tickethub';
$dbUser = (string)getenv('DB_USER') ?: '';
$dbPass = (string)getenv('DB_PASS') ?: '';

$conn = @mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);
if (!$conn) {
    fwrite(STDERR, "ERROR: cannot connect to database\n");
    exit(1);
}
mysqli_set_charset($conn, 'utf8mb4');

$prefix = (string)getenv('DB_PREFIX') ?: 'th_';

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `{$prefix}seeds` (
    seed_name VARCHAR(191) NOT NULL,
    executed_at DATETIME NOT NULL,
    PRIMARY KEY (seed_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$safeSeednName = mysqli_real_escape_string($conn, $seedName);
$checkResult = mysqli_query($conn, "SELECT seed_name FROM `{$prefix}seeds` WHERE seed_name='{$safeSeednName}' LIMIT 1");
if ($checkResult && mysqli_num_rows($checkResult) > 0) {
    echo "seed '{$seedName}' already applied\n";
    mysqli_close($conn);
    exit(1);
}

$seedFile = $projectRoot . "/db/seeds/{$seedName}.php";
if (!file_exists($seedFile)) {
    fwrite(STDERR, "ERROR: seed file not found: {$seedFile}\n");
    mysqli_close($conn);
    exit(1);
}

if (!function_exists('db_query')) {
    function db_query(string $sql, string $database = '', mixed $connArg = ''): mixed
    {
        global $conn;
        return @mysqli_query($conn, $sql);
    }
}

if (!function_exists('db_input')) {
    function db_input(string $value): string
    {
        global $conn;
        return "'" . mysqli_real_escape_string($conn, $value) . "'";
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

if (!defined('TABLE_PREFIX')) {
    define('TABLE_PREFIX', $prefix);
}

require_once $seedFile;

$parts     = explode('_', $seedName);
$className = 'Seed' . implode('', array_map('ucfirst', $parts));

if (!class_exists($className)) {
    fwrite(STDERR, "ERROR: class '{$className}' not found in {$seedFile}\n");
    mysqli_close($conn);
    exit(1);
}

if ($isDryRun) {
    echo "DRY RUN — would apply seed '{$seedName}' (class {$className})\n";
    mysqli_close($conn);
    exit(0);
}

$seeder = new $className();
$success = $seeder->run();

if ($success) {
    $safeSeedName = mysqli_real_escape_string($conn, $seedName);
    mysqli_query($conn, "INSERT INTO `{$prefix}seeds` (seed_name, executed_at) VALUES ('{$safeSeedName}', NOW())");
    echo "seed '{$seedName}' applied successfully\n";
} else {
    fwrite(STDERR, "ERROR: seed '{$seedName}' run() returned false\n");
    mysqli_close($conn);
    exit(1);
}

mysqli_close($conn);
exit(0);
