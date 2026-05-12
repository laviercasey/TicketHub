<?php
declare(strict_types=1);

class InitialIndexes
{
    private const MIGRATION_TAG = '[migration 20260512000001]';

    public function up(): bool
    {
        $prefix = defined('TABLE_PREFIX') ? TABLE_PREFIX : 'th_';

        $createOps = [
            [$prefix . 'ticket',          'idx_email',         '(`email`)'],
            [$prefix . 'ticket_response', 'idx_ticket_created', '(`ticket_id`, `created`)'],
            [$prefix . 'ticket_note',     'idx_ticket_created', '(`ticket_id`, `created`)'],
        ];

        foreach ($createOps as [$table, $index, $columns]) {
            if (!$this->createIndexIfMissing($table, $index, $columns)) {
                return false;
            }
        }

        $dropOps = [
            [$prefix . 'ticket', 'dept_id'],
            [$prefix . 'ticket', 'staff_id'],
            [$prefix . 'ticket', 'created'],
            [$prefix . 'ticket', 'closed'],
        ];

        foreach ($dropOps as [$table, $index]) {
            if (!$this->dropIndexIfExists($table, $index)) {
                return false;
            }
        }

        return true;
    }

    public function down(): bool
    {
        $prefix = defined('TABLE_PREFIX') ? TABLE_PREFIX : 'th_';

        $recreateOps = [
            [$prefix . 'ticket', 'dept_id',  '(`dept_id`)'],
            [$prefix . 'ticket', 'staff_id', '(`staff_id`)'],
            [$prefix . 'ticket', 'created',  '(`created`)'],
            [$prefix . 'ticket', 'closed',   '(`closed`)'],
        ];

        foreach ($recreateOps as [$table, $index, $columns]) {
            if (!$this->createIndexIfMissing($table, $index, $columns)) {
                return false;
            }
        }

        $dropOps = [
            [$prefix . 'ticket',          'idx_email'],
            [$prefix . 'ticket_response', 'idx_ticket_created'],
            [$prefix . 'ticket_note',     'idx_ticket_created'],
        ];

        foreach ($dropOps as [$table, $index]) {
            if (!$this->dropIndexIfExists($table, $index)) {
                return false;
            }
        }

        return true;
    }

    private function indexExists(string $table, string $index): ?bool
    {
        $safeTable = db_real_escape($table);
        $safeIndex = db_real_escape($index);

        $sql = "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME   = '{$safeTable}'
                  AND INDEX_NAME   = '{$safeIndex}'";

        $result = db_query($sql);
        if (!$result) {
            error_log(self::MIGRATION_TAG . " failed to query INFORMATION_SCHEMA для {$table}.{$index}: " . db_error());
            return null;
        }

        $row = db_fetch_array($result);
        return (int)($row['cnt'] ?? 0) > 0;
    }

    private function createIndexIfMissing(string $table, string $index, string $columns): bool
    {
        try {
            $exists = $this->indexExists($table, $index);
            if ($exists === null) {
                return false;
            }
            if ($exists === true) {
                return true;
            }

            $sql = "CREATE INDEX `{$index}` ON `{$table}` {$columns}";
            if (!db_query($sql)) {
                error_log(self::MIGRATION_TAG . " CREATE INDEX {$index} ON {$table} failed: " . db_error());
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            error_log(self::MIGRATION_TAG . " exception creating {$index} ON {$table}: " . $e->getMessage());
            return false;
        }
    }

    private function dropIndexIfExists(string $table, string $index): bool
    {
        try {
            $exists = $this->indexExists($table, $index);
            if ($exists === null) {
                return false;
            }
            if ($exists === false) {
                return true;
            }

            $sql = "DROP INDEX `{$index}` ON `{$table}`";
            if (!db_query($sql)) {
                error_log(self::MIGRATION_TAG . " DROP INDEX {$index} ON {$table} failed: " . db_error());
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            error_log(self::MIGRATION_TAG . " exception dropping {$index} ON {$table}: " . $e->getMessage());
            return false;
        }
    }
}
