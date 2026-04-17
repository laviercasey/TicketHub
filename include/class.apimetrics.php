<?php

class ApiMetrics {

    static function _query($sql) {
        return db_query($sql);
    }

    static function getRealtimeStats($hours = 24) {
        $stats = array(
            'total_requests' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'avg_response_time' => 0,
            'requests_per_hour' => 0,
            'active_tokens' => 0,
            'error_rate' => 0,
            'top_endpoints' => array(),
            'top_tokens' => array(),
            'status_distribution' => array()
        );

        $hours = (int)$hours;
        $time_condition = "created_at > DATE_SUB(NOW(), INTERVAL $hours HOUR)";

        $sql = "SELECT COUNT(*) as total FROM " . API_LOG_TABLE . " WHERE $time_condition";
        $result = self::_query($sql);
        if ($result && ($row = db_fetch_array($result))) {
            $stats['total_requests'] = (int)$row['total'];
        }

        $sql = "SELECT
                    SUM(CASE WHEN response_code < 400 THEN 1 ELSE 0 END) as successful,
                    SUM(CASE WHEN response_code >= 400 THEN 1 ELSE 0 END) as failed
                FROM " . API_LOG_TABLE . " WHERE $time_condition";
        $result = self::_query($sql);
        if ($result && ($row = db_fetch_array($result))) {
            $stats['successful_requests'] = (int)$row['successful'];
            $stats['failed_requests'] = (int)$row['failed'];
        }

        $sql = "SELECT AVG(response_time) as avg_time FROM " . API_LOG_TABLE . " WHERE $time_condition";
        $result = self::_query($sql);
        if ($result && ($row = db_fetch_array($result))) {
            $stats['avg_response_time'] = round((float)$row['avg_time'], 2);
        }

        if ($stats['total_requests'] > 0 && $hours > 0) {
            $stats['requests_per_hour'] = round($stats['total_requests'] / $hours, 2);
        }

        $sql = "SELECT COUNT(DISTINCT token_id) as count FROM " . API_LOG_TABLE . " WHERE $time_condition";
        $result = self::_query($sql);
        if ($result && ($row = db_fetch_array($result))) {
            $stats['active_tokens'] = (int)$row['count'];
        }

        if ($stats['total_requests'] > 0) {
            $stats['error_rate'] = round(($stats['failed_requests'] / $stats['total_requests']) * 100, 2);
        }

        $sql = "SELECT response_code, COUNT(*) as count
                FROM " . API_LOG_TABLE . "
                WHERE $time_condition
                GROUP BY response_code
                ORDER BY count DESC";
        $result = self::_query($sql);
        while ($result && ($row = db_fetch_array($result))) {
            $stats['status_distribution'][$row['response_code']] = (int)$row['count'];
        }

        $sql = "SELECT endpoint, COUNT(*) as count, AVG(response_time) as avg_time
                FROM " . API_LOG_TABLE . "
                WHERE $time_condition
                GROUP BY endpoint
                ORDER BY count DESC
                LIMIT 10";
        $result = self::_query($sql);
        while ($result && ($row = db_fetch_array($result))) {
            $stats['top_endpoints'][] = array(
                'endpoint' => $row['endpoint'],
                'count' => (int)$row['count'],
                'avg_response_time' => round((float)$row['avg_time'], 2)
            );
        }

        $sql = "SELECT l.token_id, t.name, COUNT(*) as count
                FROM " . API_LOG_TABLE . " l
                LEFT JOIN " . API_TOKEN_TABLE . " t ON l.token_id = t.token_id
                WHERE l.$time_condition
                GROUP BY l.token_id
                ORDER BY count DESC
                LIMIT 10";
        $result = self::_query($sql);
        while ($result && ($row = db_fetch_array($result))) {
            $stats['top_tokens'][] = array(
                'token_id' => (int)$row['token_id'],
                'name' => $row['name'],
                'count' => (int)$row['count']
            );
        }

        return $stats;
    }

    static function getHistoricalData($days = 7, $interval = 'hour') {
        $data = array();
        $days = (int)$days;

        if ($interval == 'hour' && $days > 2) {
            $interval = 'day';
        }

        if ($interval == 'hour') {
            $sql = "SELECT
                        DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as time_bucket,
                        COUNT(*) as total,
                        SUM(CASE WHEN response_code < 400 THEN 1 ELSE 0 END) as successful,
                        SUM(CASE WHEN response_code >= 400 THEN 1 ELSE 0 END) as failed,
                        AVG(response_time) as avg_response_time
                    FROM " . API_LOG_TABLE . "
                    WHERE created_at > DATE_SUB(NOW(), INTERVAL $days DAY)
                    GROUP BY time_bucket
                    ORDER BY time_bucket ASC";
        } else {
            $sql = "SELECT
                        DATE(created_at) as time_bucket,
                        COUNT(*) as total,
                        SUM(CASE WHEN response_code < 400 THEN 1 ELSE 0 END) as successful,
                        SUM(CASE WHEN response_code >= 400 THEN 1 ELSE 0 END) as failed,
                        AVG(response_time) as avg_response_time
                    FROM " . API_LOG_TABLE . "
                    WHERE created_at > DATE_SUB(NOW(), INTERVAL $days DAY)
                    GROUP BY time_bucket
                    ORDER BY time_bucket ASC";
        }

        $result = self::_query($sql);
        while ($result && ($row = db_fetch_array($result))) {
            $data[] = array(
                'timestamp' => $row['time_bucket'],
                'request_count' => (int)$row['total'],
                'successful' => (int)$row['successful'],
                'failed' => (int)$row['failed'],
                'avg_response_time' => round((float)$row['avg_response_time'], 2)
            );
        }

        return $data;
    }

    static function getPerformanceMetrics($hours = 24) {
        $metrics = array(
            'avg_response_time' => 0,
            'min_response_time' => 0,
            'max_response_time' => 0,
            'p50_response_time' => 0,
            'p95_response_time' => 0,
            'p99_response_time' => 0,
            'slow_requests' => 0,
            'slowest_endpoints' => array()
        );

        $hours = (int)$hours;
        $time_condition = "created_at > DATE_SUB(NOW(), INTERVAL $hours HOUR)";

        $sql = "SELECT
                    AVG(response_time) as avg_time,
                    MIN(response_time) as min_time,
                    MAX(response_time) as max_time,
                    COUNT(*) as total_count
                FROM " . API_LOG_TABLE . " WHERE $time_condition";
        $result = self::_query($sql);
        $total_count = 0;
        if ($result && ($row = db_fetch_array($result))) {
            $metrics['avg_response_time'] = round((float)$row['avg_time'], 2);
            $metrics['min_response_time'] = round((float)$row['min_time'], 2);
            $metrics['max_response_time'] = round((float)$row['max_time'], 2);
            $total_count = (int)$row['total_count'];
        }

        if ($total_count > 0) {
            $offset50 = (int)floor($total_count * 0.5);
            $sql = "SELECT response_time FROM " . API_LOG_TABLE . "
                    WHERE $time_condition
                    ORDER BY response_time ASC
                    LIMIT $offset50, 1";
            $result = self::_query($sql);
            if ($result && ($row = db_fetch_array($result))) {
                $metrics['p50_response_time'] = round((float)$row['response_time'], 2);
            }

            $offset95 = (int)floor($total_count * 0.95);
            $sql = "SELECT response_time FROM " . API_LOG_TABLE . "
                    WHERE $time_condition
                    ORDER BY response_time ASC
                    LIMIT $offset95, 1";
            $result = self::_query($sql);
            if ($result && ($row = db_fetch_array($result))) {
                $metrics['p95_response_time'] = round((float)$row['response_time'], 2);
            }

            $offset99 = (int)floor($total_count * 0.99);
            $sql = "SELECT response_time FROM " . API_LOG_TABLE . "
                    WHERE $time_condition
                    ORDER BY response_time ASC
                    LIMIT $offset99, 1";
            $result = self::_query($sql);
            if ($result && ($row = db_fetch_array($result))) {
                $metrics['p99_response_time'] = round((float)$row['response_time'], 2);
            }
        }

        $sql = "SELECT COUNT(*) as count FROM " . API_LOG_TABLE . "
                WHERE $time_condition AND response_time > 2000";
        $result = self::_query($sql);
        if ($result && ($row = db_fetch_array($result))) {
            $metrics['slow_requests'] = (int)$row['count'];
        }

        $sql = "SELECT endpoint, AVG(response_time) as avg_time, COUNT(*) as count
                FROM " . API_LOG_TABLE . "
                WHERE $time_condition
                GROUP BY endpoint
                ORDER BY avg_time DESC
                LIMIT 10";
        $result = self::_query($sql);
        while ($result && ($row = db_fetch_array($result))) {
            $metrics['slowest_endpoints'][] = array(
                'endpoint' => $row['endpoint'],
                'avg_response_time' => round((float)$row['avg_time'], 2),
                'count' => (int)$row['count']
            );
        }

        return $metrics;
    }

    static function getErrorAnalysis($hours = 24) {
        $analysis = array(
            'total_errors' => 0,
            'error_rate' => 0,
            'errors_by_status' => array(),
            'errors_by_endpoint' => array(),
            'recent_errors' => array()
        );

        $hours = (int)$hours;
        $time_condition = "created_at > DATE_SUB(NOW(), INTERVAL $hours HOUR)";

        $sql = "SELECT COUNT(*) as count FROM " . API_LOG_TABLE . "
                WHERE $time_condition AND response_code >= 400";
        $result = self::_query($sql);
        if ($result && ($row = db_fetch_array($result))) {
            $analysis['total_errors'] = (int)$row['count'];
        }

        $sql = "SELECT COUNT(*) as total FROM " . API_LOG_TABLE . " WHERE $time_condition";
        $result = self::_query($sql);
        if ($result && ($row = db_fetch_array($result))) {
            $total = (int)$row['total'];
            if ($total > 0) {
                $analysis['error_rate'] = round(($analysis['total_errors'] / $total) * 100, 2);
            }
        }

        $sql = "SELECT response_code, COUNT(*) as count
                FROM " . API_LOG_TABLE . "
                WHERE $time_condition AND response_code >= 400
                GROUP BY response_code
                ORDER BY count DESC";
        $result = self::_query($sql);
        while ($result && ($row = db_fetch_array($result))) {
            $analysis['errors_by_status'][$row['response_code']] = (int)$row['count'];
        }

        $sql = "SELECT endpoint, COUNT(*) as count
                FROM " . API_LOG_TABLE . "
                WHERE $time_condition AND response_code >= 400
                GROUP BY endpoint
                ORDER BY count DESC
                LIMIT 10";
        $result = self::_query($sql);
        while ($result && ($row = db_fetch_array($result))) {
            $analysis['errors_by_endpoint'][] = array(
                'endpoint' => $row['endpoint'],
                'count' => (int)$row['count']
            );
        }

        $sql = "SELECT
                    log_id,
                    endpoint,
                    method,
                    response_code,
                    response_time,
                    ip_address,
                    created_at
                FROM " . API_LOG_TABLE . "
                WHERE response_code >= 400
                ORDER BY created_at DESC
                LIMIT 20";
        $result = self::_query($sql);
        while ($result && ($row = db_fetch_array($result))) {
            $analysis['recent_errors'][] = array(
                'log_id' => (int)$row['log_id'],
                'endpoint' => $row['endpoint'],
                'method' => $row['method'],
                'status_code' => (int)$row['response_code'],
                'response_time' => (int)$row['response_time'],
                'ip_address' => $row['ip_address'],
                'timestamp' => $row['created_at']
            );
        }

        return $analysis;
    }

    static function getTokenStats() {
        $stats = array(
            'total_tokens' => 0,
            'active_tokens' => 0,
            'inactive_tokens' => 0,
            'token_usage' => array()
        );

        $sql = "SELECT COUNT(*) as total FROM " . API_TOKEN_TABLE;
        $result = self::_query($sql);
        if ($result && ($row = db_fetch_array($result))) {
            $stats['total_tokens'] = (int)$row['total'];
        }

        $sql = "SELECT
                    SUM(CASE WHEN is_active=1 THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN is_active=0 THEN 1 ELSE 0 END) as inactive
                FROM " . API_TOKEN_TABLE;
        $result = self::_query($sql);
        if ($result && ($row = db_fetch_array($result))) {
            $stats['active_tokens'] = (int)$row['active'];
            $stats['inactive_tokens'] = (int)$row['inactive'];
        }

        $sql = "SELECT
                    t.token_id,
                    t.name,
                    t.is_active,
                    t.rate_limit,
                    t.last_used_at,
                    COUNT(l.log_id) as requests
                FROM " . API_TOKEN_TABLE . " t
                LEFT JOIN " . API_LOG_TABLE . " l ON t.token_id = l.token_id
                    AND l.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY t.token_id
                ORDER BY requests DESC
                LIMIT 20";
        $result = self::_query($sql);
        while ($result && ($row = db_fetch_array($result))) {
            $rate_percentage = 0;
            if ((int)$row['rate_limit'] > 0) {
                $rate_percentage = round(((int)$row['requests'] / (int)$row['rate_limit']) * 100, 2);
            }
            if ($rate_percentage > 100) {
                $rate_percentage = 100;
            }

            $stats['token_usage'][] = array(
                'token_id' => (int)$row['token_id'],
                'name' => $row['name'],
                'is_active' => (int)$row['is_active'],
                'requests' => (int)$row['requests'],
                'rate_limit' => (int)$row['rate_limit'],
                'rate_limit_percentage' => $rate_percentage,
                'last_used' => $row['last_used_at']
            );
        }

        return $stats;
    }

    static function healthCheck() {
        $health = array(
            'status' => 'healthy',
            'checks' => array(),
            'timestamp' => date('Y-m-d H:i:s')
        );

        $check = array('component' => 'Database', 'status' => 'ok', 'message' => '');
        $sql = "SELECT 1";
        if (self::_query($sql)) {
            $check['message'] = 'Database connection OK';
        } else {
            $check['status'] = 'error';
            $check['message'] = 'Database query failed';
            $health['status'] = 'unhealthy';
        }
        $health['checks'][] = $check;

        $check = array('component' => 'API Tables', 'status' => 'ok', 'message' => '');
        $tables = array(API_TOKEN_TABLE, API_LOG_TABLE, API_RATE_LIMIT_TABLE);
        $missing = array();
        foreach ($tables as $table) {
            global $__db;
            $escaped = ($__db instanceof \mysqli) ? mysqli_real_escape_string($__db, $table) : addslashes($table);
            $sql = "SHOW TABLES LIKE '" . $escaped . "'";
            $result = self::_query($sql);
            if (!$result || !db_num_rows($result)) {
                $missing[] = $table;
            }
        }

        if (defined('API_AUDIT_LOG_TABLE')) {
            global $__db;
            $escaped = ($__db instanceof \mysqli) ? mysqli_real_escape_string($__db, API_AUDIT_LOG_TABLE) : addslashes(API_AUDIT_LOG_TABLE);
            $sql = "SHOW TABLES LIKE '" . $escaped . "'";
            $result = self::_query($sql);
            if (!$result || !db_num_rows($result)) {
                $missing[] = API_AUDIT_LOG_TABLE;
            }
        }

        if (empty($missing)) {
            $check['message'] = 'All API tables exist';
        } else {
            $check['status'] = 'error';
            $check['message'] = 'Missing tables: ' . implode(', ', $missing);
            $health['status'] = 'degraded';
        }
        $health['checks'][] = $check;

        $check = array('component' => 'API Activity', 'status' => 'ok', 'message' => '');
        $sql = "SELECT COUNT(*) as count FROM " . API_LOG_TABLE . "
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $result = self::_query($sql);
        if ($result && ($row = db_fetch_array($result))) {
            $count = (int)$row['count'];
            $check['message'] = "$count requests in last hour";
            if ($count == 0) {
                $check['status'] = 'warning';
                $check['message'] = 'No API activity in last hour';
            }
        }
        $health['checks'][] = $check;

        $check = array('component' => 'Error Rate', 'status' => 'ok', 'message' => '');
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN response_code >= 400 THEN 1 ELSE 0 END) as errors
                FROM " . API_LOG_TABLE . "
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $result = self::_query($sql);
        if ($result && ($row = db_fetch_array($result))) {
            $total = (int)$row['total'];
            $errs = (int)$row['errors'];
            $error_rate = $total > 0 ? round(($errs / $total) * 100, 2) : 0;

            $check['message'] = "Error rate: $error_rate%";
            if ($error_rate > 50) {
                $check['status'] = 'error';
                $health['status'] = 'unhealthy';
            } elseif ($error_rate > 25) {
                $check['status'] = 'warning';
                if ($health['status'] == 'healthy') {
                    $health['status'] = 'degraded';
                }
            }
        }
        $health['checks'][] = $check;

        $check = array('component' => 'Performance', 'status' => 'ok', 'message' => '');
        $sql = "SELECT AVG(response_time) as avg_time FROM " . API_LOG_TABLE . "
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $result = self::_query($sql);
        if ($result && ($row = db_fetch_array($result))) {
            $avg_time = round((float)$row['avg_time'], 2);
            $check['message'] = "Average response time: {$avg_time}ms";

            if ($avg_time > 2000) {
                $check['status'] = 'warning';
                if ($health['status'] == 'healthy') {
                    $health['status'] = 'degraded';
                }
            }
        }
        $health['checks'][] = $check;

        $check = array('component' => 'Tokens', 'status' => 'ok', 'message' => '');
        $sql = "SELECT COUNT(*) as count FROM " . API_TOKEN_TABLE . " WHERE is_active = 1";
        $result = self::_query($sql);
        if ($result && ($row = db_fetch_array($result))) {
            $count = (int)$row['count'];
            $check['message'] = "$count active tokens";
            if ($count == 0) {
                $check['status'] = 'warning';
                $check['message'] = 'No active API tokens';
            }
        }
        $health['checks'][] = $check;

        return $health;
    }

    static function checkAlerts() {
        $alerts = array();

        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN response_code >= 400 THEN 1 ELSE 0 END) as errors
                FROM " . API_LOG_TABLE . "
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $result = self::_query($sql);
        if ($result && ($row = db_fetch_array($result))) {
            $total = (int)$row['total'];
            $errors = (int)$row['errors'];
            $error_rate = $total > 0 ? round(($errors / $total) * 100, 2) : 0;

            if ($error_rate > 25 && $total > 10) {
                $alerts[] = array(
                    'severity' => 'critical',
                    'type' => 'High Error Rate',
                    'message' => "Error rate: $error_rate% ($errors/$total requests in last hour)"
                );
            }
        }

        $sql = "SELECT COUNT(*) as count FROM " . API_LOG_TABLE . "
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                AND response_time > 2000";
        $result = self::_query($sql);
        if ($result && ($row = db_fetch_array($result))) {
            $slow_count = (int)$row['count'];
            if ($slow_count > 10) {
                $alerts[] = array(
                    'severity' => 'warning',
                    'type' => 'Slow Requests',
                    'message' => "$slow_count slow requests (>2s) in last hour"
                );
            }
        }

        if (defined('API_AUDIT_LOG_TABLE')) {
            global $__db;
            $escaped = ($__db instanceof \mysqli) ? mysqli_real_escape_string($__db, API_AUDIT_LOG_TABLE) : addslashes(API_AUDIT_LOG_TABLE);
            $sql = "SHOW TABLES LIKE '" . $escaped . "'";
            $result = self::_query($sql);
            if ($result && db_num_rows($result)) {
                $sql = "SELECT COUNT(*) as count FROM " . API_AUDIT_LOG_TABLE . "
                        WHERE severity IN ('warning', 'error', 'critical')
                        AND created > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
                $result = self::_query($sql);
                if ($result && ($row = db_fetch_array($result))) {
                    $security_events = (int)$row['count'];
                    if ($security_events > 20) {
                        $alerts[] = array(
                            'severity' => 'critical',
                            'type' => 'Security Events',
                            'message' => "$security_events security events in last hour"
                        );
                    }
                }
            }
        }

        $sql = "SELECT t.name, t.rate_limit, COUNT(l.log_id) as requests
                FROM " . API_TOKEN_TABLE . " t
                JOIN " . API_LOG_TABLE . " l ON t.token_id = l.token_id
                    AND l.created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                WHERE t.is_active = 1 AND t.rate_limit > 0
                GROUP BY t.token_id
                HAVING requests > t.rate_limit * 0.8";
        $result = self::_query($sql);
        $near_limit = 0;
        while ($result && ($row = db_fetch_array($result))) {
            $near_limit++;
        }
        if ($near_limit > 0) {
            $alerts[] = array(
                'severity' => 'warning',
                'type' => 'Rate Limit Warning',
                'message' => "$near_limit token(s) approaching or exceeding rate limits"
            );
        }

        return $alerts;
    }

    static function getUsageTrends($days = 30) {
        $trends = array(
            'daily_requests' => array(),
            'growth_rate' => 0,
            'popular_hours' => array(),
            'busiest_day' => 'N/A'
        );

        $days = (int)$days;

        $sql = "SELECT
                    DATE(created_at) as date_val,
                    COUNT(*) as count
                FROM " . API_LOG_TABLE . "
                WHERE created_at > DATE_SUB(NOW(), INTERVAL $days DAY)
                GROUP BY date_val
                ORDER BY date_val ASC";
        $result = self::_query($sql);
        while ($result && ($row = db_fetch_array($result))) {
            $trends['daily_requests'][] = array(
                'date' => $row['date_val'],
                'count' => (int)$row['count']
            );
        }

        $count = count($trends['daily_requests']);
        if ($count >= 4) {
            $half = (int)floor($count / 2);
            $first_total = 0;
            $second_total = 0;
            for ($i = 0; $i < $half; $i++) {
                $first_total += $trends['daily_requests'][$i]['count'];
            }
            for ($i = $half; $i < $count; $i++) {
                $second_total += $trends['daily_requests'][$i]['count'];
            }
            if ($first_total > 0) {
                $trends['growth_rate'] = round((($second_total - $first_total) / $first_total) * 100, 2);
            }
        }

        $sql = "SELECT
                    HOUR(created_at) as hour_val,
                    COUNT(*) as count
                FROM " . API_LOG_TABLE . "
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY hour_val
                ORDER BY count DESC";
        $result = self::_query($sql);
        while ($result && ($row = db_fetch_array($result))) {
            $trends['popular_hours'][] = array(
                'hour' => (int)$row['hour_val'],
                'count' => (int)$row['count']
            );
        }

        $sql = "SELECT
                    DATE(created_at) as date_val,
                    COUNT(*) as count
                FROM " . API_LOG_TABLE . "
                WHERE created_at > DATE_SUB(NOW(), INTERVAL $days DAY)
                GROUP BY date_val
                ORDER BY count DESC
                LIMIT 1";
        $result = self::_query($sql);
        if ($result && ($row = db_fetch_array($result))) {
            $trends['busiest_day'] = $row['date_val'] . ' (' . $row['count'] . ' requests)';
        }

        return $trends;
    }

    static function cleanupOldLogs($retention_days = 30) {
        $retention_days = (int)$retention_days;

        $sql = "DELETE FROM " . API_LOG_TABLE . "
                WHERE created_at < DATE_SUB(NOW(), INTERVAL $retention_days DAY)";
        $deleted_logs = 0;
        if (self::_query($sql)) {
            $tmp = db_query("SELECT ROW_COUNT() as cnt");
            if ($tmp && ($r = db_fetch_array($tmp))) {
                $deleted_logs = (int)$r['cnt'];
            }
        }

        $deleted_audit = 0;
        if (defined('API_AUDIT_LOG_TABLE')) {
            $sql = "DELETE FROM " . API_AUDIT_LOG_TABLE . "
                    WHERE created < DATE_SUB(NOW(), INTERVAL $retention_days DAY)";
            if (self::_query($sql)) {
                $tmp = db_query("SELECT ROW_COUNT() as cnt");
                if ($tmp && ($r = db_fetch_array($tmp))) {
                    $deleted_audit = (int)$r['cnt'];
                }
            }
        }

        $sql = "DELETE FROM " . API_RATE_LIMIT_TABLE . "
                WHERE window_end < DATE_SUB(NOW(), INTERVAL 1 DAY)";
        $deleted_rate = 0;
        if (self::_query($sql)) {
            $tmp = db_query("SELECT ROW_COUNT() as cnt");
            if ($tmp && ($r = db_fetch_array($tmp))) {
                $deleted_rate = (int)$r['cnt'];
            }
        }

        return array(
            'api_logs_deleted' => $deleted_logs,
            'audit_logs_deleted' => $deleted_audit,
            'rate_limits_deleted' => $deleted_rate
        );
    }

    static function getSummary($hours = 24) {
        $hours = (int)$hours;
        $days = ($hours >= 168) ? 30 : 7;

        return array(
            'realtime' => ApiMetrics::getRealtimeStats($hours),
            'performance' => ApiMetrics::getPerformanceMetrics($hours),
            'errors' => ApiMetrics::getErrorAnalysis($hours),
            'tokens' => ApiMetrics::getTokenStats(),
            'health' => ApiMetrics::healthCheck(),
            'alerts' => ApiMetrics::checkAlerts(),
            'trends' => ApiMetrics::getUsageTrends($days),
            'historical' => ApiMetrics::getHistoricalData($days, 'hour')
        );
    }
}

?>
