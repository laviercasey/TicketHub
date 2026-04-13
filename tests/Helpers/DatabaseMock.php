<?php

class DatabaseMock {
    private static array $queryResults = [];
    private static array $executedQueries = [];
    private static int $lastInsertId = 0;
    private static int $affectedRows = 0;
    private static string $lastError = '';
    private static int $lastErrno = 0;

    public static function reset(): void {
        self::$queryResults = [];
        self::$executedQueries = [];
        self::$lastInsertId = 0;
        self::$affectedRows = 0;
        self::$lastError = '';
        self::$lastErrno = 0;
    }

    public static function setQueryResult(string $pattern, $rows): void {
        self::$queryResults[$pattern] = $rows;
    }

    public static function setLastInsertId(int $id): void {
        self::$lastInsertId = $id;
    }

    public static function setAffectedRows(int $count): void {
        self::$affectedRows = $count;
    }

    public static function getExecutedQueries(): array {
        return self::$executedQueries;
    }

    public static function getLastQuery(): ?string {
        return end(self::$executedQueries) ?: null;
    }

    public static function findResult(string $query) {
        foreach (self::$queryResults as $pattern => $result) {
            if (stripos($query, $pattern) !== false) {
                return $result;
            }
        }
        return null;
    }

    public static function recordQuery(string $query): void {
        self::$executedQueries[] = $query;
    }

    public static function getLastInsertId(): int {
        return self::$lastInsertId;
    }

    public static function getAffectedRows(): int {
        return self::$affectedRows;
    }

    public static function getError(): string {
        return self::$lastError;
    }

    public static function getErrno(): int {
        return self::$lastErrno;
    }
}

class MockQueryResult {
    private array $rows;
    private int $position = 0;

    public function __construct(array $rows) {
        $this->rows = $rows;
    }

    public function fetchArray(): ?array {
        if ($this->position >= count($this->rows)) {
            return null;
        }
        return $this->rows[$this->position++];
    }

    public function fetchRow(): ?array {
        if ($this->position >= count($this->rows)) {
            return null;
        }
        $row = $this->rows[$this->position++];
        return array_values($row);
    }

    public function numRows(): int {
        return count($this->rows);
    }

    public function reset(): void {
        $this->position = 0;
    }
}

if (!function_exists('db_query')) {
    function db_query($query, $database = "", $conn = "") {
        DatabaseMock::recordQuery($query);
        $result = DatabaseMock::findResult($query);
        if ($result === null) {
            return new MockQueryResult([]);
        }
        if ($result instanceof MockQueryResult) {
            return $result;
        }
        if (is_array($result)) {
            return new MockQueryResult($result);
        }
        return $result;
    }
}

if (!function_exists('db_fetch_array')) {
    function db_fetch_array($result, $mode = false) {
        if ($result instanceof MockQueryResult) {
            return $result->fetchArray();
        }
        return null;
    }
}

if (!function_exists('db_fetch_row')) {
    function db_fetch_row($result) {
        if ($result instanceof MockQueryResult) {
            return $result->fetchRow();
        }
        return null;
    }
}

if (!function_exists('db_num_rows')) {
    function db_num_rows($result) {
        if ($result instanceof MockQueryResult) {
            return $result->numRows();
        }
        return 0;
    }
}

if (!function_exists('db_affected_rows')) {
    function db_affected_rows() {
        return DatabaseMock::getAffectedRows();
    }
}

if (!function_exists('db_insert_id')) {
    function db_insert_id() {
        return DatabaseMock::getLastInsertId();
    }
}

if (!function_exists('db_input')) {
    function db_input($param, $quote = true) {
        if ($param !== null && $param !== '' && preg_match("/^\d+(\.\d+)?$/", (string)$param)) {
            return intval($param) == $param ? (int)$param : (float)$param;
        }
        if ($param && is_array($param)) {
            foreach ($param as $key => $value) {
                $param[$key] = db_input($value, $quote);
            }
            return $param;
        }
        return db_real_escape($param, $quote);
    }
}

if (!function_exists('db_real_escape')) {
    function db_real_escape($val, $quote = false) {
        $val = addslashes($val ?? '');
        return ($quote) ? "'$val'" : $val;
    }
}

if (!function_exists('db_error')) {
    function db_error() {
        return DatabaseMock::getError();
    }
}

if (!function_exists('db_errno')) {
    function db_errno() {
        return DatabaseMock::getErrno();
    }
}

if (!function_exists('db_connect')) {
    function db_connect($dbhost, $dbuser, $dbpass, $dbname = "") {
        return true;
    }
}

if (!function_exists('db_close')) {
    function db_close() {
        return true;
    }
}
