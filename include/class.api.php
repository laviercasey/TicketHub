<?php

class Api {

    static function validateToken($token, $ip = null) {
        require_once(INCLUDE_DIR.'class.apitoken.php');

        $apiToken = ApiToken::lookup($token);

        if (!$apiToken) {
            return null;
        }

        if (!$apiToken->validate($ip)) {
            return null;
        }

        if (!$apiToken->checkRateLimit()) {
            return null;
        }

        return $apiToken;
    }

    static function logRequest($token_id, $endpoint, $method, $request_data, $response_code, $response_time = null, $ip = null, $user_agent = null) {
        $request_body = null;
        if ($request_data) {
            $sensitive_keys = array('password', 'passwd', 'pass', 'secret', 'token', 'api_key', 'apikey', 'credit_card', 'cc_number', 'cvv', 'ssn');
            $sanitized = self::redactSensitive($request_data, $sensitive_keys);
            $json = json_encode($sanitized);
            if (strlen($json) > 65536) {
                $json = substr($json, 0, 65536) . '... [truncated]';
            }
            $request_body = $json;
        }

        $sql = sprintf(
            'INSERT INTO %s SET
             token_id=%d,
             endpoint=%s,
             method=%s,
             request_body=%s,
             response_code=%d,
             response_time=%s,
             ip_address=%s,
             user_agent=%s,
             created_at=NOW()',
            API_LOG_TABLE,
            $token_id,
            db_input($endpoint),
            db_input($method),
            $request_body ? db_input($request_body) : 'NULL',
            $response_code,
            $response_time ? db_input($response_time) : 'NULL',
            $ip ? db_input($ip) : 'NULL',
            $user_agent ? db_input($user_agent) : 'NULL'
        );

        return db_query($sql);
    }

    private static function redactSensitive($data, $keys) {
        if (!is_array($data)) return $data;
        foreach ($data as $k => &$v) {
            if (is_array($v)) {
                $v = self::redactSensitive($v, $keys);
            } elseif (in_array(strtolower($k), $keys, true)) {
                $v = '[REDACTED]';
            }
        }
        unset($v);
        return $data;
    }

    static function getStats($token_id = null, $days = 7) {
        $where = '';
        if ($token_id) {
            $where = 'WHERE token_id='.db_input($token_id).' AND ';
        } else {
            $where = 'WHERE ';
        }

        $where .= sprintf('created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)', $days);

        $sql = 'SELECT COUNT(*) as total FROM '.API_LOG_TABLE.' '.$where;
        $result = db_query($sql);
        $row = db_fetch_array($result);
        $total = $row['total'];

        $sql = 'SELECT response_code, COUNT(*) as count FROM '.API_LOG_TABLE.' '.$where.
               ' GROUP BY response_code ORDER BY count DESC';
        $result = db_query($sql);
        $by_status = array();
        while ($row = db_fetch_array($result)) {
            $by_status[$row['response_code']] = $row['count'];
        }

        $sql = 'SELECT endpoint, COUNT(*) as count FROM '.API_LOG_TABLE.' '.$where.
               ' GROUP BY endpoint ORDER BY count DESC LIMIT 10';
        $result = db_query($sql);
        $top_endpoints = array();
        while ($row = db_fetch_array($result)) {
            $top_endpoints[] = array(
                'endpoint' => $row['endpoint'],
                'count' => $row['count']
            );
        }

        $sql = 'SELECT AVG(response_time) as avg_time FROM '.API_LOG_TABLE.' '.$where.
               ' AND response_time IS NOT NULL';
        $result = db_query($sql);
        $row = db_fetch_array($result);
        $avg_response_time = $row['avg_time'];

        return array(
            'total_requests' => $total,
            'by_status' => $by_status,
            'top_endpoints' => $top_endpoints,
            'avg_response_time' => round($avg_response_time, 2)
        );
    }

    static function cleanupLogs($days = 30) {
        global $cfg;

        $retention_days = $cfg->get('api_log_retention_days', $days);

        $sql = sprintf(
            'DELETE FROM %s WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)',
            API_LOG_TABLE,
            $retention_days
        );

        return db_query($sql);
    }

}
?>
