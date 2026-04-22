#!/usr/bin/env php
<?php
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "CLI only\n");
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

$isDryRun  = false;
$argUsername = '';
$argEmail    = '';
$argPassword = '';

foreach ($argv as $arg) {
    if ($arg === '--dry-run') {
        $isDryRun = true;
    } elseif (str_starts_with($arg, '--username=')) {
        $argUsername = substr($arg, strlen('--username='));
    } elseif (str_starts_with($arg, '--email=')) {
        $argEmail = substr($arg, strlen('--email='));
    } elseif (str_starts_with($arg, '--password=')) {
        $argPassword = substr($arg, strlen('--password='));
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

$staffResult = mysqli_query($conn, "SELECT COUNT(*) AS c FROM {$prefix}staff");
$staffRow    = $staffResult ? mysqli_fetch_assoc($staffResult) : null;
$staffCount  = (int)($staffRow['c'] ?? 0);

if ($staffCount > 0) {
    echo "admin already exists, use regular reset flow\n";
    mysqli_close($conn);
    exit(1);
}

$usernameBlacklist = ['admin', 'admins', 'tickethub'];

function validateAdminUsername(string $username): ?string
{
    global $usernameBlacklist;
    if (!preg_match('/^[a-zA-Z0-9._-]{3,32}$/', $username)) {
        return 'username must be 3-32 chars, only letters/digits/._-';
    }
    if (in_array(strtolower($username), $usernameBlacklist, true)) {
        return 'username is reserved';
    }
    return null;
}

function validateAdminEmail(string $email): ?string
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'invalid email address';
    }
    return null;
}

function validateAdminPassword(string $password): ?string
{
    if (strlen($password) < 10) {
        return 'password must be at least 10 characters';
    }
    if (!preg_match('/[a-zA-Z]/', $password)) {
        return 'password must contain at least one letter';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return 'password must contain at least one digit';
    }
    return null;
}

function promptUserLine(string $prompt): string
{
    echo $prompt;
    $line = fgets(STDIN);
    return $line === false ? '' : trim($line);
}

function promptHiddenLine(string $prompt): string
{
    if (PHP_OS_FAMILY !== 'Windows' && stream_isatty(STDIN)) {
        echo $prompt;
        system('stty -echo');
        $line = fgets(STDIN);
        system('stty echo');
        echo "\n";
        return $line === false ? '' : trim($line);
    }
    return promptUserLine($prompt);
}

$isInteractive = ($argUsername === '' || $argEmail === '' || $argPassword === '');

if ($isInteractive) {
    if ($argUsername === '') {
        while (true) {
            $argUsername = promptUserLine('Username (3-32 chars, letters/digits/._-): ');
            $err = validateAdminUsername($argUsername);
            if ($err === null) break;
            echo "Error: {$err}\n";
        }
    }

    if ($argEmail === '') {
        while (true) {
            $argEmail = promptUserLine('Email: ');
            $err = validateAdminEmail($argEmail);
            if ($err === null) break;
            echo "Error: {$err}\n";
        }
    }

    if ($argPassword === '') {
        while (true) {
            $password1 = promptHiddenLine('Password (min 10 chars, 1 letter + 1 digit): ');
            $err = validateAdminPassword($password1);
            if ($err !== null) {
                echo "Error: {$err}\n";
                continue;
            }
            $password2 = promptHiddenLine('Confirm password: ');
            if ($password1 !== $password2) {
                echo "Error: passwords do not match\n";
                continue;
            }
            $argPassword = $password1;
            break;
        }
    }
} else {
    $err = validateAdminUsername($argUsername);
    if ($err !== null) {
        fwrite(STDERR, "ERROR: {$err}\n");
        exit(1);
    }
    $err = validateAdminEmail($argEmail);
    if ($err !== null) {
        fwrite(STDERR, "ERROR: {$err}\n");
        exit(1);
    }
    $err = validateAdminPassword($argPassword);
    if ($err !== null) {
        fwrite(STDERR, "ERROR: {$err}\n");
        exit(1);
    }
}

$passwordHash = password_hash($argPassword, PASSWORD_DEFAULT);
$tzOffset     = (int)(date('Z') / 3600);

$safeUsername = mysqli_real_escape_string($conn, $argUsername);
$safeEmail    = mysqli_real_escape_string($conn, $argEmail);
$safeHash     = mysqli_real_escape_string($conn, $passwordHash);

$insertSql = "INSERT INTO {$prefix}staff SET
    created=NOW(), isadmin=1, change_passwd=0,
    group_id=1, dept_id=1,
    email='{$safeEmail}',
    firstname='Admin', lastname='Admin',
    username='{$safeUsername}',
    passwd='{$safeHash}',
    timezone_offset={$tzOffset}";

if ($isDryRun) {
    echo "DRY RUN — SQL that would be executed:\n";
    echo $insertSql . "\n";
    mysqli_close($conn);
    exit(0);
}

if (mysqli_query($conn, $insertSql)) {
    echo "Admin '{$argUsername}' created successfully\n";
} else {
    fwrite(STDERR, "ERROR: " . mysqli_error($conn) . "\n");
    mysqli_close($conn);
    exit(1);
}

mysqli_close($conn);
exit(0);
