<?php
declare(strict_types=1);

if (!defined('TABLE_PREFIX')) {
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', '../../');
    }
    require_once(ROOT_PATH . 'main.inc.php');
}

if (!defined('MIGRATIONS_TABLE')) {
    define('MIGRATIONS_TABLE', TABLE_PREFIX . 'migrations');
}

if (!defined('MIGRATIONS_DIR')) {
    define('MIGRATIONS_DIR', dirname(__FILE__) . '/migrations/');
}

class MigrationManager
{
    public array $errors = [];

    public function __construct()
    {
        $this->errors = [];
        $this->ensureMigrationsTable();
    }

    public function ensureMigrationsTable(): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS " . MIGRATIONS_TABLE . " (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            executed_at DATETIME NOT NULL,
            checksum VARCHAR(64) NOT NULL DEFAULT '',
            batch INT NOT NULL DEFAULT 0,
            INDEX (migration)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        if (!db_query($sql)) {
            $this->errors[] = "Не удалось создать таблицу миграций: " . db_error();
            return false;
        }

        $this->addColumnIfMissing(MIGRATIONS_TABLE, 'checksum', "VARCHAR(64) NOT NULL DEFAULT ''");
        $this->addColumnIfMissing(MIGRATIONS_TABLE, 'batch', "INT NOT NULL DEFAULT 0");

        return true;
    }

    private function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        $safeTable  = db_real_escape($table);
        $safeColumn = db_real_escape($column);
        $check = db_query(
            "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = '{$safeTable}'
               AND COLUMN_NAME = '{$safeColumn}'"
        );
        if ($check) {
            $row = db_fetch_array($check);
            if ((int)($row['cnt'] ?? 0) === 0) {
                db_query("ALTER TABLE `{$safeTable}` ADD COLUMN `{$safeColumn}` {$definition}");
            }
        }
    }

    public function getMigrationFiles(): array
    {
        $files = [];

        if (!is_dir(MIGRATIONS_DIR)) {
            mkdir(MIGRATIONS_DIR, 0755, true);
            return $files;
        }

        $dir = opendir(MIGRATIONS_DIR);
        if ($dir === false) {
            return $files;
        }
        while (($file = readdir($dir)) !== false) {
            if (preg_match('/^\d{14}_.*\.php$/', $file)) {
                $files[] = $file;
            }
        }
        closedir($dir);

        sort($files);
        return $files;
    }

    public function getExecutedMigrations(): array
    {
        $executed = [];
        $sql = "SELECT migration, checksum FROM " . MIGRATIONS_TABLE . " ORDER BY migration";
        $result = db_query($sql);

        if ($result) {
            while ($row = db_fetch_array($result)) {
                $executed[$row['migration']] = $row['checksum'];
            }
        }

        return $executed;
    }

    public function getPendingMigrations(): array|false
    {
        $all      = $this->getMigrationFiles();
        $executed = $this->getExecutedMigrations();

        $pending = [];
        foreach ($all as $file) {
            $name = basename($file, '.php');
            if (isset($executed[$name])) {
                $migrationPath = MIGRATIONS_DIR . $file;
                if (file_exists($migrationPath)) {
                    $currentChecksum = hash('sha256', (string)file_get_contents($migrationPath));
                    $storedChecksum  = $executed[$name];
                    if ($storedChecksum !== '' && $currentChecksum !== $storedChecksum) {
                        $this->errors[] = sprintf(
                            "INTEGRITY ERROR: содержимое миграции '%s' изменилось после применения! " .
                            "Ожидаемый checksum: %s, фактический: %s. " .
                            "Нельзя редактировать уже применённые миграции.",
                            $name,
                            $storedChecksum,
                            $currentChecksum
                        );
                        return false;
                    }
                }
            } else {
                $pending[] = $file;
            }
        }
        return $pending;
    }

    public function runMigrations(): array
    {
        $result = ['applied' => 0, 'skipped' => 0, 'errors' => []];

        $lockResult = db_query("SELECT GET_LOCK('tickethub_migrate', 30) AS got");
        if (!$lockResult) {
            $result['errors'][] = "Не удалось запросить advisory lock";
            return $result;
        }
        $lockRow = db_fetch_array($lockResult);
        if ((int)($lockRow['got'] ?? 0) !== 1) {
            $result['errors'][] = "another migration in progress (не удалось получить lock за 30 секунд)";
            return $result;
        }

        try {
            $pending = $this->getPendingMigrations();

            if ($pending === false) {
                $result['errors'] = $this->errors;
                return $result;
            }

            if (empty($pending)) {
                $result['skipped'] = count($this->getMigrationFiles());
                return $result;
            }

            $batchResult = db_query("SELECT COALESCE(MAX(batch), 0) AS max_batch FROM " . MIGRATIONS_TABLE);
            $batchRow    = db_fetch_array($batchResult);
            $currentBatch = (int)($batchRow['max_batch'] ?? 0) + 1;

            foreach ($pending as $migrationFile) {
                if ($this->runMigration($migrationFile, 'up', $currentBatch)) {
                    $result['applied']++;
                } else {
                    $result['errors'] = array_merge($result['errors'], $this->errors);
                    return $result;
                }
            }

            $result['skipped'] = count($this->getMigrationFiles()) - $result['applied'];

        } finally {
            db_query("SELECT RELEASE_LOCK('tickethub_migrate')");
        }

        return $result;
    }

    public function runMigration(string $migrationFile, string $direction, int $batch = 0): bool
    {
        $migrationPath = MIGRATIONS_DIR . $migrationFile;

        if (!file_exists($migrationPath)) {
            $this->errors[] = "Файл миграции не найден: $migrationFile";
            return false;
        }

        $fileChecksum = hash('sha256', (string)file_get_contents($migrationPath));

        include $migrationPath;

        $migrationName = $this->getMigrationClassName($migrationFile);

        if (!class_exists($migrationName)) {
            $this->errors[] = "Класс миграции не найден: $migrationName";
            return false;
        }

        $migration = new $migrationName();

        db_query('START TRANSACTION');

        try {
            if ($direction === 'up') {
                $migrationResult = $migration->up();
            } else {
                $migrationResult = $migration->down();
            }

            if ($migrationResult === false) {
                throw new \Exception('Миграция вернула false');
            }

            $fileKey = basename($migrationFile, '.php');

            if ($direction === 'up') {
                $sql = sprintf(
                    "INSERT INTO %s (migration, executed_at, checksum, batch) VALUES (%s, NOW(), %s, %d)",
                    MIGRATIONS_TABLE,
                    db_input($fileKey),
                    db_input($fileChecksum),
                    $batch
                );
            } else {
                $sql = sprintf(
                    "DELETE FROM %s WHERE migration=%s",
                    MIGRATIONS_TABLE,
                    db_input($fileKey)
                );
            }

            if (!db_query($sql)) {
                throw new \Exception('Не удалось записать статус миграции: ' . db_error());
            }

            db_query('COMMIT');
            return true;

        } catch (\Exception $e) {
            db_query('ROLLBACK');
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    public function rollbackLastMigration(): bool
    {
        $executed = $this->getExecutedMigrations();

        if (empty($executed)) {
            echo "Нет миграций для отката.\n";
            return true;
        }

        $lastMigration = array_key_last($executed) . '.php';

        echo "Откат: $lastMigration... ";

        if ($this->runMigration($lastMigration, 'down')) {
            echo "OK\n";
            return true;
        } else {
            echo "FAILED\n";
            foreach ($this->errors as $error) {
                echo "  ERROR: $error\n";
            }
            return false;
        }
    }

    public function showStatus(): void
    {
        $all     = $this->getMigrationFiles();
        $executed = $this->getExecutedMigrations();
        $pending  = $this->getPendingMigrations();

        if ($pending === false) {
            echo "ERROR: Обнаружены несоответствия чексумм:\n";
            foreach ($this->errors as $error) {
                echo "  " . $error . "\n";
            }
            return;
        }

        echo "Всего миграций: " . count($all) . "\n";
        echo "Применено: " . count($executed) . "\n";
        echo "Ожидает: " . count($pending) . "\n\n";

        if (!empty($pending)) {
            echo "Ожидающие миграции:\n";
            foreach ($pending as $migration) {
                echo "  - $migration\n";
            }
        }
    }

    public function getMigrationClassName(string $filename): string
    {
        $name  = basename($filename, '.php');
        $name  = (string)preg_replace('/^\d{14}_/', '', $name);
        $parts = explode('_', $name);
        $className = '';
        foreach ($parts as $part) {
            $className .= ucfirst($part);
        }
        return $className;
    }

    public function createMigration(string $name): bool
    {
        $timestamp = date('YmdHis');
        $filename  = $timestamp . '_' . $name . '.php';
        $filepath  = MIGRATIONS_DIR . $filename;

        if (!is_dir(MIGRATIONS_DIR)) {
            mkdir(MIGRATIONS_DIR, 0755, true);
        }

        $className = $this->getMigrationClassName($filename);

        $template = '<?php
declare(strict_types=1);

class ' . $className . '
{
    public function up(): bool
    {
        return true;
    }

    public function down(): bool
    {
        return true;
    }
}
';

        if (file_put_contents($filepath, $template)) {
            echo "Создана миграция: $filename\n";
            echo "Редактировать: $filepath\n";
            return true;
        } else {
            $this->errors[] = "Не удалось создать файл миграции";
            return false;
        }
    }
}

if (php_sapi_name() === 'cli') {
    $action = $argv[1] ?? 'help';

    $manager = new MigrationManager();

    switch ($action) {
        case 'up':
        case 'run':
            $result = $manager->runMigrations();
            echo "Применено: {$result['applied']}, Пропущено: {$result['skipped']}\n";
            if (!empty($result['errors'])) {
                foreach ($result['errors'] as $err) {
                    fwrite(STDERR, "ERROR: $err\n");
                }
                exit(1);
            }
            break;

        case 'down':
        case 'rollback':
            $manager->rollbackLastMigration();
            break;

        case 'list':
        case 'status':
            $manager->showStatus();
            break;

        case 'create':
            if (!isset($argv[2])) {
                echo "Использование: php migration.php create migration_name\n";
                exit(1);
            }
            $manager->createMigration($argv[2]);
            break;

        default:
            echo "TicketHub Migration Manager\n\n";
            echo "Использование:\n";
            echo "  php migration.php up              Применить ожидающие миграции\n";
            echo "  php migration.php down            Откатить последнюю миграцию\n";
            echo "  php migration.php list            Показать статус миграций\n";
            echo "  php migration.php create <name>   Создать новую миграцию\n";
            break;
    }
} else {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: text/plain; charset=utf-8');
    echo "403 Forbidden: доступ только из командной строки\n";
    exit(403);
}
