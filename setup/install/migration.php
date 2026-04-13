<?php
define('ROOT_PATH','../../');
require_once(ROOT_PATH.'main.inc.php');

define('MIGRATIONS_TABLE', TABLE_PREFIX.'migrations');
define('MIGRATIONS_DIR', dirname(__FILE__).'/migrations/');

class MigrationManager {

    public $errors;

    function MigrationManager() {
        $this->errors = array();
        $this->ensureMigrationsTable();
    }

    function ensureMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS " . MIGRATIONS_TABLE . " (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            executed_at DATETIME NOT NULL,
            INDEX (migration)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

        if (!db_query($sql)) {
            $this->errors[] = "Failed to create migrations table: " . db_error();
            return false;
        }
        return true;
    }

    function getMigrationFiles() {
        $files = array();

        if (!is_dir(MIGRATIONS_DIR)) {
            mkdir(MIGRATIONS_DIR, 0755, true);
            return $files;
        }

        $dir = opendir(MIGRATIONS_DIR);
        while (($file = readdir($dir)) !== false) {
            if (preg_match('/^\d{14}_.*\.php$/', $file)) {
                $files[] = $file;
            }
        }
        closedir($dir);

        sort($files);
        return $files;
    }

    function getExecutedMigrations() {
        $executed = array();
        $sql = "SELECT migration FROM " . MIGRATIONS_TABLE . " ORDER BY migration";
        $result = db_query($sql);

        if ($result) {
            while ($row = db_fetch_array($result)) {
                $executed[] = $row['migration'];
            }
        }

        return $executed;
    }

    function getPendingMigrations() {
        $all = $this->getMigrationFiles();
        $executed = $this->getExecutedMigrations();

        $pending = array();
        foreach ($all as $file) {
            $name = basename($file, '.php');
            if (!in_array($name, $executed)) {
                $pending[] = $file;
            }
        }
        return $pending;
    }

    function runMigrations() {
        $pending = $this->getPendingMigrations();

        if (empty($pending)) {
            echo "No pending migrations.\n";
            return true;
        }

        echo "Running " . count($pending) . " migration(s)...\n";

        foreach ($pending as $migration_file) {
            echo "Running: $migration_file... ";

            if ($this->runMigration($migration_file, 'up')) {
                echo "OK\n";
            } else {
                echo "FAILED\n";
                foreach ($this->errors as $error) {
                    echo "  ERROR: $error\n";
                }
                return false;
            }
        }

        return true;
    }

    function runMigration($migration_file, $direction) {
        $migration_path = MIGRATIONS_DIR . $migration_file;

        if (!file_exists($migration_path)) {
            $this->errors[] = "Migration file not found: $migration_file";
            return false;
        }

        include $migration_path;

        $migration_name = $this->getMigrationClassName($migration_file);

        if (!class_exists($migration_name)) {
            $this->errors[] = "Migration class not found: $migration_name";
            return false;
        }

        $migration = new $migration_name();

        db_query('START TRANSACTION');

        try {
            if ($direction == 'up') {
                $result = $migration->up();
            } else {
                $result = $migration->down();
            }

            if ($result === false) {
                throw new Exception('Migration returned false');
            }

            $file_key = basename($migration_file, '.php');

            if ($direction == 'up') {
                $sql = sprintf(
                    "INSERT INTO %s (migration, executed_at) VALUES (%s, NOW())",
                    MIGRATIONS_TABLE,
                    db_input($file_key)
                );
            } else {
                $sql = sprintf(
                    "DELETE FROM %s WHERE migration=%s",
                    MIGRATIONS_TABLE,
                    db_input($file_key)
                );
            }

            if (!db_query($sql)) {
                throw new Exception('Failed to record migration: ' . db_error());
            }

            db_query('COMMIT');
            return true;

        } catch (Exception $e) {
            db_query('ROLLBACK');
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    function rollbackLastMigration() {
        $executed = $this->getExecutedMigrations();

        if (empty($executed)) {
            echo "No migrations to rollback.\n";
            return true;
        }

        $last_migration = end($executed) . '.php';

        echo "Rolling back: $last_migration... ";

        if ($this->runMigration($last_migration, 'down')) {
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

    function showStatus() {
        $all = $this->getMigrationFiles();
        $executed = $this->getExecutedMigrations();
        $pending = $this->getPendingMigrations();

        echo "Total migrations: " . count($all) . "\n";
        echo "Executed: " . count($executed) . "\n";
        echo "Pending: " . count($pending) . "\n\n";

        if (!empty($pending)) {
            echo "Pending migrations:\n";
            foreach ($pending as $migration) {
                echo "  - $migration\n";
            }
        }
    }

    function getMigrationClassName($filename) {
        $name = basename($filename, '.php');
        $name = preg_replace('/^\d{14}_/', '', $name);
        $parts = explode('_', $name);
        $class_name = '';
        foreach ($parts as $part) {
            $class_name .= ucfirst($part);
        }
        return $class_name;
    }

    function createMigration($name) {
        $timestamp = date('YmdHis');
        $filename = $timestamp . '_' . $name . '.php';
        $filepath = MIGRATIONS_DIR . $filename;

        if (!is_dir(MIGRATIONS_DIR)) {
            mkdir(MIGRATIONS_DIR, 0755, true);
        }

        $class_name = $this->getMigrationClassName($filename);

        $template = '<?php
class ' . $class_name . ' {

    function up() {
        return true;
    }

    function down() {
        return true;
    }
}
?>';

        if (file_put_contents($filepath, $template)) {
            echo "Created migration: $filename\n";
            echo "Edit: $filepath\n";
            return true;
        } else {
            $this->errors[] = "Failed to create migration file";
            return false;
        }
    }
}

if (php_sapi_name() == 'cli') {
    $action = isset($argv[1]) ? $argv[1] : 'help';

    $manager = new MigrationManager();

    switch ($action) {
        case 'up':
        case 'run':
            $manager->runMigrations();
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
                echo "Usage: php migration.php create migration_name\n";
                exit(1);
            }
            $manager->createMigration($argv[2]);
            break;

        default:
            echo "TicketHub Migration Manager\n\n";
            echo "Usage:\n";
            echo "  php migration.php up              Run pending migrations\n";
            echo "  php migration.php down            Rollback last migration\n";
            echo "  php migration.php list            Show migration status\n";
            echo "  php migration.php create <name>   Create new migration\n";
            break;
    }
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo "TicketHub Migration Manager\n";
    echo "=========================\n\n";

    $manager = new MigrationManager();

    $action = isset($_GET['action']) ? $_GET['action'] : 'status';

    switch ($action) {
        case 'run':
            $manager->runMigrations();
            break;
        case 'rollback':
            $manager->rollbackLastMigration();
            break;
        default:
            $manager->showStatus();
            echo "\nActions: ?action=run | ?action=rollback\n";
    }
}

?>
