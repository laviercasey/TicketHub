<?php

class ApiSecurity {

    static function getSecurityHeaders() {
        return array(
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'none'; frame-ancestors 'none'",
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
        );
    }

    static function applySecurityHeaders() {
        $headers = self::getSecurityHeaders();

        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $name => $value) {
            if (!headers_sent()) {
                header($name . ': ' . $value);
            }
        }
    }

    static function sanitizeInput($data) {
        if (is_array($data)) {
            $clean = array();
            foreach ($data as $key => $value) {
                $clean[$key] = self::sanitizeInput($value);
            }
            return $clean;
        }

        if (is_string($data)) {
            $data = str_replace("\0", '', $data);
            $data = trim($data);
            return $data;
        }

        return $data;
    }

    static function validateEmail($email, &$errors, $field = 'email') {
        if (!$email) {
            $errors[$field] = 'Требуется email';
            return false;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[$field] = 'Неверный формат email';
            return false;
        }

        if (strlen($email) > 255) {
            $errors[$field] = 'Email слишком длинный (максимум 255 символов)';
            return false;
        }

        if (preg_match('/[<>"\']/', $email)) {
            $errors[$field] = 'Email содержит недопустимые символы';
            return false;
        }

        return true;
    }

    static function validateUrl($url, &$errors, $field = 'url') {
        if (!$url) {
            $errors[$field] = 'Требуется URL';
            return false;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[$field] = 'Неверный формат URL';
            return false;
        }

        if (strlen($url) > 2048) {
            $errors[$field] = 'URL слишком длинный (максимум 2048 символов)';
            return false;
        }

        $parsed = parse_url($url);

        if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], array('http', 'https'))) {
            $errors[$field] = 'Разрешены только HTTP и HTTPS URL';
            return false;
        }

        if (isset($parsed['host'])) {
            $ip = gethostbyname($parsed['host']);
            if (self::isPrivateIp($ip)) {
                $errors[$field] = 'URL, указывающие на частные сети, запрещены';
                return false;
            }
        }

        return true;
    }

    static function isPrivateIp($ip) {
        $ip = trim($ip, '[]');

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }

        return false;
    }

    static function validateInteger($value, &$errors, $field = 'id', $min = null, $max = null) {
        if (!is_numeric($value)) {
            $errors[$field] = 'Должно быть допустимым числом';
            return false;
        }

        $int_value = intval($value);

        if ($min !== null && $int_value < $min) {
            $errors[$field] = "Должно быть не менее $min";
            return false;
        }

        if ($max !== null && $int_value > $max) {
            $errors[$field] = "Must be no more than $max";
            return false;
        }

        return true;
    }

    static function validateStringLength($value, &$errors, $field = 'field', $min = null, $max = null) {
        $length = mb_strlen($value, 'UTF-8');

        if ($min !== null && $length < $min) {
            $errors[$field] = "Must be at least $min characters";
            return false;
        }

        if ($max !== null && $length > $max) {
            $errors[$field] = "Must be no more than $max characters";
            return false;
        }

        return true;
    }

    static function validateEnum($value, $allowed_values, &$errors, $field = 'field') {
        if (!in_array($value, $allowed_values)) {
            $errors[$field] = 'Invalid value. Allowed: ' . implode(', ', $allowed_values);
            return false;
        }

        return true;
    }

    static function validateDateTime($value, &$errors, $field = 'date') {
        if (!$value) {
            return true;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            $errors[$field] = 'Invalid date/time format. Use ISO 8601 (e.g., 2025-02-16T10:30:00+00:00)';
            return false;
        }

        return true;
    }

    static function detectSqlInjection($value) {
        if (!is_string($value)) {
            return false;
        }

        $patterns = array(
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bSELECT\b.*\bFROM\b)/i',
            '/(\bINSERT\b.*\bINTO\b)/i',
            '/(\bUPDATE\b.*\bSET\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(\bEXEC\b|\bEXECUTE\b)/i',
            '/(--|\#|\/\*|\*\/)/i',
            '/(\bOR\b.*=.*)/i',
            '/(\bAND\b.*=.*)/i',
            '/(0x[0-9a-f]+)/i'
        );

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    static function detectXss($value) {
        if (!is_string($value)) {
            return false;
        }

        $patterns = array(
            '/<script[^>]*>.*<\/script>/is',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i',
            '/vbscript:/i',
            '/data:text\/html/i'
        );

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    static function validateRequestSize($max_size = null) {
        global $cfg;

        if ($max_size === null) {
            $max_size = $cfg->get('api_max_request_size', 1048576);
        }

        $content_length = isset($_SERVER['CONTENT_LENGTH']) ? intval($_SERVER['CONTENT_LENGTH']) : 0;

        if ($content_length > $max_size) {
            return false;
        }

        return true;
    }

    static function checkBruteForce($identifier, $max_attempts = 5, $window = 300) {
        $ip = self::getClientIp();
        $key = db_input(md5($identifier . $ip));

        $sql = sprintf(
            "SELECT COUNT(*) as cnt FROM %s WHERE rate_key=%s AND created_at > DATE_SUB(NOW(), INTERVAL %d SECOND)",
            API_RATE_LIMIT_TABLE,
            $key,
            intval($window)
        );

        $result = db_query($sql);
        if ($result && ($row = db_fetch_array($result))) {
            return $row['cnt'] < $max_attempts;
        }

        return true;
    }

    static function recordFailedAttempt($identifier) {
        $ip = self::getClientIp();
        $key = db_input(md5($identifier . $ip));

        $sql = sprintf(
            "INSERT INTO %s SET rate_key=%s, ip_address=%s, created_at=NOW()",
            API_RATE_LIMIT_TABLE,
            $key,
            db_input($ip)
        );
        db_query($sql);

        db_query(sprintf(
            "DELETE FROM %s WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            API_RATE_LIMIT_TABLE
        ));
    }

    static function clearFailedAttempts($identifier) {
        $ip = self::getClientIp();
        $key = db_input(md5($identifier . $ip));

        db_query(sprintf(
            "DELETE FROM %s WHERE rate_key=%s",
            API_RATE_LIMIT_TABLE,
            $key
        ));
    }

    static function auditLog($event_type, $details, $severity = 'info') {
        global $ost;

        $sql = sprintf(
            "INSERT INTO %s SET
                event_type=%s,
                severity=%s,
                details=%s,
                ip_address=%s,
                user_agent=%s,
                created=NOW()",
            API_AUDIT_LOG_TABLE,
            db_input($event_type),
            db_input($severity),
            db_input(json_encode($details)),
            db_input($_SERVER['REMOTE_ADDR']),
            db_input(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '')
        );

        db_query($sql);

        if (in_array($severity, array('warning', 'error', 'critical'))) {
            error_log(sprintf(
                '[API SECURITY] %s - %s - %s',
                strtoupper($severity),
                $event_type,
                json_encode($details)
            ));
        }
    }

    static function validateTokenFormat($token) {
        if (!preg_match('/^[a-zA-Z0-9]{64}$/', $token)) {
            return false;
        }

        return true;
    }

    static function validateOrigin($allowed_origins = array()) {
        if (empty($allowed_origins)) {
            return true;
        }

        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

        if (!$origin) {
            return true;
        }

        foreach ($allowed_origins as $allowed) {
            if ($allowed === '*' || $origin === $allowed) {
                return true;
            }

            if (strpos($allowed, '*') !== false) {
                $pattern = str_replace('*', '.*', preg_quote($allowed, '/'));
                if (preg_match('/^' . $pattern . '$/', $origin)) {
                    return true;
                }
            }
        }

        return false;
    }

    static function generateSecureToken($length = 64) {
        return bin2hex(random_bytes(intval($length / 2)));
    }

    static function hashSensitiveData($data, $fields = array('password', 'token', 'api_key')) {
        if (!is_array($data)) {
            return $data;
        }

        $hashed = $data;

        foreach ($fields as $field) {
            if (isset($hashed[$field])) {
                $hashed[$field] = '[REDACTED]';
            }
        }

        return $hashed;
    }

    static function isBlacklistedIp($ip) {
        $sql = 'SELECT COUNT(*) as count FROM ' . API_IP_BLACKLIST_TABLE .
               ' WHERE ip_address=' . db_input($ip) .
               ' AND (expires_at IS NULL OR expires_at > NOW())';

        $result = db_query($sql);
        if ($result && ($row = db_fetch_array($result))) {
            return $row['count'] > 0;
        }

        return false;
    }

    static function blacklistIp($ip, $reason, $duration = null) {
        $expires_sql = $duration ?
            sprintf("DATE_ADD(NOW(), INTERVAL %d SECOND)", intval($duration)) :
            'NULL';

        $sql = sprintf(
            "INSERT INTO %s SET
                ip_address=%s,
                reason=%s,
                expires_at=%s,
                created=NOW()
            ON DUPLICATE KEY UPDATE
                reason=%s,
                expires_at=%s",
            API_IP_BLACKLIST_TABLE,
            db_input($ip),
            db_input($reason),
            $expires_sql,
            db_input($reason),
            $expires_sql
        );

        return db_query($sql);
    }

    static function scanRequest(&$errors) {
        $issues = array();

        if (!in_array($_SERVER['REQUEST_METHOD'], array('GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'))) {
            $issues[] = 'Invalid HTTP method';
        }

        if (in_array($_SERVER['REQUEST_METHOD'], array('POST', 'PUT', 'PATCH'))) {
            $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
            if (strpos($content_type, 'application/json') === false) {
                $issues[] = 'Invalid Content-Type. Expected application/json';
            }
        }

        if (!self::validateRequestSize()) {
            $issues[] = 'Request body too large';
        }

        $all_input = array_merge($_GET, $_POST);
        foreach ($all_input as $key => $value) {
            if (is_string($value)) {
                if (self::detectSqlInjection($value)) {
                    $issues[] = "Potential SQL injection detected in field: $key";
                    self::auditLog('sql_injection_attempt', array(
                        'field' => $key,
                        'value' => substr($value, 0, 100)
                    ), 'warning');
                }

                if (self::detectXss($value)) {
                    $issues[] = "Potential XSS detected in field: $key";
                    self::auditLog('xss_attempt', array(
                        'field' => $key,
                        'value' => substr($value, 0, 100)
                    ), 'warning');
                }
            }
        }

        if (!empty($issues)) {
            $errors['security'] = implode('; ', $issues);
            return false;
        }

        return true;
    }

    static function getClientIp() {
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }

    static function constantTimeCompare($str1, $str2) {
        if (function_exists('hash_equals')) {
            return hash_equals($str1, $str2);
        }

        if (strlen($str1) !== strlen($str2)) {
            return false;
        }

        $result = 0;
        for ($i = 0; $i < strlen($str1); $i++) {
            $result |= ord($str1[$i]) ^ ord($str2[$i]);
        }

        return $result === 0;
    }
}

if (!defined('API_AUDIT_LOG_TABLE')) {
    define('API_AUDIT_LOG_TABLE', TABLE_PREFIX . 'api_audit_log');
}

if (!defined('API_IP_BLACKLIST_TABLE')) {
    define('API_IP_BLACKLIST_TABLE', TABLE_PREFIX . 'api_ip_blacklist');
}

?>
