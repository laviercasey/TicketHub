<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 0);

define('ROOT_PATH', '../');
define('ROOT_DIR', '../');
define('INCLUDE_DIR', ROOT_DIR . 'include/');
define('SETUPINC', true);

$envFile = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '.env';
if (file_exists($envFile)) {
    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        if (!getenv($k)) putenv("$k=$v");
    }
}

require_once(INCLUDE_DIR . 'mysql.php');

$dbhost = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'tickethub';
$dbuser = getenv('DB_USER') ?: '';
$dbpass = getenv('DB_PASS') ?: '';

if (!db_connect($dbhost, $dbuser, $dbpass)) {
    die("ОШИБКА: не удалось подключиться к MySQL ($dbhost)\n");
}
if (!db_select_database($dbname)) {
    die("ОШИБКА: не удалось выбрать базу данных ($dbname)\n");
}

define('PREFIX', 'th_');

$isCli = (php_sapi_name() === 'cli');

$appEnv = getenv('APP_ENV') ?: '';
if (strtolower($appEnv) === 'production') {
    fail('Сидирование запрещено в production-окружении. Переменная APP_ENV=production.');
}

$testPassword = getenv('SEED_PASSWORD') ?: 'TestPassword1!';

if (!$isCli) {
    if (empty($_POST['confirm'])) {
        header('Content-Type: text/html; charset=utf-8');
        echo <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="utf-8"><title>TicketHub — Seed Data</title>
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; max-width: 600px; margin: 60px auto; color: #1a1a2e; }
  h1 { font-size: 1.4rem; }
  .warn { background: #fff3cd; border: 1px solid #ffc107; padding: 12px 16px; border-radius: 8px; margin: 16px 0; }
  .info { background: #e8f4f8; border: 1px solid #0891b2; padding: 12px 16px; border-radius: 8px; margin: 16px 0; font-size: 0.9rem; }
  button { background: #2563eb; color: #fff; border: none; padding: 10px 24px; border-radius: 6px; cursor: pointer; font-size: 1rem; }
  button:hover { background: #1d4ed8; }
  code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.9em; }
</style>
</head>
<body>
  <h1>TicketHub — Загрузка тестовых данных</h1>
  <div class="warn">
    <strong>Внимание!</strong> Эта операция добавит тестовые данные в базу данных <code>$dbname</code>.
    Рекомендуется выполнять на чистой установке.
  </div>
  <div class="info">
    <strong>Будет создано:</strong><br>
    — 6 сотрудников (пароль: <code>$testPassword</code>)<br>
    — 18 заявок с сообщениями и ответами<br>
    — 3 доски задач с 14 задачами<br>
    — 17 единиц техники (инвентарь)<br>
    — 5 документов базы знаний<br>
    — 10 локаций, бренды, модели<br>
    — API-токены, VIP-пользователи, и др.
  </div>
  <div class="warn">
    <strong>Не используйте seed-данные в production!</strong> Тестовые аккаунты имеют известный пароль.
    После тестирования смените пароли или удалите аккаунты.
  </div>
  <form method="post">
    <input type="hidden" name="confirm" value="1">
    <button type="submit">Загрузить тестовые данные</button>
  </form>
</body>
</html>
HTML;
        exit;
    }
}

$bcryptHash = password_hash($testPassword, PASSWORD_DEFAULT);

$seedFile = __DIR__ . '/inc/seed-data.sql';
if (!file_exists($seedFile)) {
    fail("Файл seed-data.sql не найден: $seedFile");
}

$sql = file_get_contents($seedFile);
$sql = str_replace('%TABLE_PREFIX%', PREFIX, $sql);
$sql = str_replace('%STAFF_PASSWD%', addslashes($bcryptHash), $sql);

$queries = splitSql($sql);

output("TicketHub Seed Data Loader");
output("=========================");
output("БД: $dbname @ $dbhost");
output("Пароль тестовых аккаунтов: $testPassword");
output("Запросов к выполнению: " . count($queries));
output("");

global $__db;
@mysqli_query($__db, 'SET SESSION SQL_MODE=""');
@mysqli_query($__db, 'SET FOREIGN_KEY_CHECKS=0');

$executed = 0;
$skipped = 0;
$errors = 0;

foreach ($queries as $i => $query) {
    $query = trim($query);
    if ($query === '') continue;

    if (mysqli_query($__db, $query)) {
        $affected = mysqli_affected_rows($__db);
        $executed++;
        if (preg_match('/^\s*(INSERT|UPDATE)/i', $query)) {
            $table = '';
            if (preg_match('/(?:INTO|UPDATE)\s+`?(\w+)`?/i', $query, $m)) {
                $table = $m[1];
            }
            if ($affected > 0) {
                output("  + $table: $affected строк(и)");
            } else {
                $skipped++;
            }
        }
    } else {
        $err = mysqli_error($__db);
        if (stripos($err, 'Duplicate') !== false) {
            $skipped++;
            continue;
        }
        $errors++;
        $short = mb_substr($query, 0, 100);
        output("  ! ОШИБКА: $err");
        output("    Запрос: $short...");
    }
}

@mysqli_query($__db, 'SET FOREIGN_KEY_CHECKS=1');

output("");
output("Готово!");
output("  Выполнено: $executed");
output("  Пропущено (дубликаты): $skipped");
output("  Ошибок: $errors");
output("");
output("Тестовые аккаунты:");
output("  ivanov  / $testPassword  (Менеджер тех. отдела)");
output("  petrova / $testPassword  (Специалист тех. отдела)");
output("  sidorov / $testPassword  (Специалист отдела продаж)");
output("  kozlova / $testPassword  (Специалист тех. отдела)");
output("  volkov  / $testPassword  (Менеджер отдела продаж)");
output("");

if ($errors === 0) {
    output("Seed-данные успешно загружены.");
} else {
    output("Загрузка завершена с ошибками. Проверьте вывод выше.");
}

output("");
output("!!! НЕ ИСПОЛЬЗУЙТЕ SEED-ДАННЫЕ В PRODUCTION !!!");
output("Смените пароли или удалите тестовые аккаунты перед выходом в продакшен.");

if (!$isCli) {
    $out = ob_get_clean();
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Seed Results</title>";
    echo "<style>body{font-family:monospace;max-width:800px;margin:40px auto;white-space:pre-wrap;line-height:1.6;}</style>";
    echo "</head><body>" . htmlspecialchars($out) . "</body></html>";
}

function output($msg) {
    static $isCli = null;
    if ($isCli === null) $isCli = (php_sapi_name() === 'cli');
    if ($isCli) {
        echo $msg . "\n";
    }
}

function fail($msg) {
    $isCli = (php_sapi_name() === 'cli');
    if ($isCli) {
        fwrite(STDERR, "ОШИБКА: $msg\n");
        exit(1);
    }
    die("<h2 style='color:red'>ОШИБКА: " . htmlspecialchars($msg) . "</h2>");
}

function splitSql(string $sql): array {
    $queries = [];
    $current = '';
    $inString = false;
    $stringChar = '';
    $len = strlen($sql);

    for ($i = 0; $i < $len; $i++) {
        $char = $sql[$i];

        if ($inString && $char === '\\') {
            $current .= $char;
            $i++;
            if ($i < $len) $current .= $sql[$i];
            continue;
        }

        if ($char === "'" || $char === '"') {
            if (!$inString) {
                $inString = true;
                $stringChar = $char;
            } elseif ($char === $stringChar) {
                $inString = false;
            }
            $current .= $char;
            continue;
        }

        if ($char === ';' && !$inString) {
            $trimmed = trim($current);
            if ($trimmed !== '') {
                $queries[] = $trimmed;
            }
            $current = '';
            continue;
        }

        if (!$inString && $char === '-' && $i + 1 < $len && $sql[$i + 1] === '-') {
            $eol = strpos($sql, "\n", $i);
            if ($eol === false) break;
            $i = $eol;
            $current .= "\n";
            continue;
        }

        $current .= $char;
    }

    $trimmed = trim($current);
    if ($trimmed !== '') {
        $queries[] = $trimmed;
    }

    return $queries;
}
