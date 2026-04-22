<?php
declare(strict_types=1);

header('Content-Type: application/json');
header('Cache-Control: no-store');

mysqli_report(MYSQLI_REPORT_OFF);

$envFile = __DIR__ . '/.env';
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

$dbHost   = (string)getenv('DB_HOST');
$dbName   = (string)getenv('DB_NAME');
$dbUser   = (string)getenv('DB_USER');
$dbPass   = (string)getenv('DB_PASS');
$dbPrefix = (string)getenv('DB_PREFIX') ?: 'th_';

if ($dbHost === '' || $dbName === '' || $dbUser === '') {
    http_response_code(503);
    echo json_encode(['status' => 'db_down']);
    exit;
}

$conn = @mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);

if ($conn === false || mysqli_connect_errno()) {
    http_response_code(503);
    echo json_encode(['status' => 'db_down']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

$configResult = @mysqli_query($conn, "SELECT thversion FROM `{$dbPrefix}config` WHERE id=1 LIMIT 1");
if (!$configResult) {
    http_response_code(503);
    echo json_encode(['status' => 'db_down']);
    mysqli_close($conn);
    exit;
}

$configRow = mysqli_fetch_assoc($configResult);
$dbVersion = ($configRow !== null) ? (string)($configRow['thversion'] ?? '') : null;

if ($dbVersion === null || $dbVersion === '') {
    http_response_code(503);
    echo json_encode(['status' => 'not_installed']);
    mysqli_close($conn);
    exit;
}

$schemaVersion = (string)getenv('SCHEMA_VERSION');
if ($schemaVersion === '') {
    $schemaVersion = '1.0';
}

if ($dbVersion !== $schemaVersion) {
    http_response_code(503);
    echo json_encode(['status' => 'drift', 'db_schema' => $dbVersion, 'code_schema' => $schemaVersion]);
    mysqli_close($conn);
    exit;
}

$staffResult = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM `{$dbPrefix}staff`");
$staffRow    = $staffResult ? mysqli_fetch_assoc($staffResult) : null;
$staffCount  = (int)($staffRow['c'] ?? 0);

if ($staffCount === 0) {
    http_response_code(503);
    echo json_encode(['status' => 'no_admin']);
    mysqli_close($conn);
    exit;
}

mysqli_close($conn);
http_response_code(200);
echo json_encode(['status' => 'ok', 'version' => $dbVersion]);
