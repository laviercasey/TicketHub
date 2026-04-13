<?php

class ApiToken {

    public $token_id;
    public $token;
    public $name;
    public $description;
    public $staff_id;
    public $token_type;
    public $permissions;
    public $ip_whitelist;
    public $ip_check_enabled;
    public $rate_limit;
    public $rate_window;
    public $is_active;
    public $expires_at;
    public $last_used_at;
    public $last_used_ip;
    public $last_used_endpoint;
    public $total_requests;
    public $created_at;
    public $updated_at;

    public $ht;

    public function __construct($id) {
        $this->token_id = 0;
        $this->ht = array();

        if ($id) {
            $this->load($id);
        }
    }

    function load($id) {
        $sql = 'SELECT * FROM '.API_TOKEN_TABLE.' WHERE ';

        if (is_numeric($id)) {
            $sql .= 'token_id='.db_input($id);
        } else {
            $sql .= 'token='.db_input($id);
        }

        if (!($res = db_query($sql)) || !db_num_rows($res)) {
            return false;
        }

        $row = db_fetch_array($res);
        $this->token_id = $row['token_id'];
        $this->token = $row['token'];
        $this->name = $row['name'];
        $this->description = $row['description'];
        $this->staff_id = $row['staff_id'];
        $this->token_type = $row['token_type'];
        $this->permissions = $row['permissions'];
        $this->ip_whitelist = $row['ip_whitelist'];
        $this->ip_check_enabled = $row['ip_check_enabled'];
        $this->rate_limit = $row['rate_limit'];
        $this->rate_window = $row['rate_window'];
        $this->is_active = $row['is_active'];
        $this->expires_at = $row['expires_at'];
        $this->last_used_at = $row['last_used_at'];
        $this->last_used_ip = $row['last_used_ip'];
        $this->last_used_endpoint = $row['last_used_endpoint'];
        $this->total_requests = $row['total_requests'];
        $this->created_at = $row['created_at'];
        $this->updated_at = $row['updated_at'];

        $this->ht = $row;

        return true;
    }

    function getId() {
        return $this->token_id;
    }

    function getToken() {
        return $this->token;
    }

    function getName() {
        return $this->name;
    }

    function getDescription() {
        return $this->description;
    }

    function getStaffId() {
        return $this->staff_id;
    }

    function getType() {
        return $this->token_type;
    }

    function isActive() {
        return $this->is_active;
    }

    function isExpired() {
        if (!$this->expires_at) {
            return false;
        }
        return strtotime($this->expires_at) < time();
    }

    function getRateLimit() {
        return $this->rate_limit;
    }

    function getRateWindow() {
        return $this->rate_window;
    }

    function getTotalRequests() {
        return $this->total_requests;
    }

    function getLastUsedAt() {
        return $this->last_used_at;
    }

    function getPermissions() {
        if (!$this->permissions) {
            return array();
        }

        $perms = json_decode($this->permissions, true);
        return $perms ? $perms : array();
    }

    function hasPermission($permission) {
        $permissions = $this->getPermissions();

        if (in_array('admin:*', $permissions)) {
            return true;
        }

        if (in_array($permission, $permissions)) {
            return true;
        }

        $parts = explode(':', $permission);
        if (count($parts) == 2) {
            $wildcard = $parts[0] . ':*';
            if (in_array($wildcard, $permissions)) {
                return true;
            }
        }

        return false;
    }

    function getIpWhitelist() {
        if (!$this->ip_whitelist) {
            return array();
        }

        $list = json_decode($this->ip_whitelist, true);
        return $list ? $list : array();
    }

    function isIpAllowed($ip) {
        if (!$this->ip_check_enabled) {
            return true;
        }

        $whitelist = $this->getIpWhitelist();

        if (empty($whitelist)) {
            return true;
        }

        foreach ($whitelist as $allowed) {
            if ($allowed == $ip) {
                return true;
            }

            if (strpos($allowed, '/') !== false) {
                if ($this->ipInCidr($ip, $allowed)) {
                    return true;
                }
            }
        }

        return false;
    }

    function ipInCidr($ip, $cidr) {
        list($subnet, $mask) = explode('/', $cidr);

        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);
        $mask_long = -1 << (32 - $mask);

        return ($ip_long & $mask_long) == ($subnet_long & $mask_long);
    }

    function validate($ip = null) {
        if (!$this->isActive()) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        if ($ip && !$this->isIpAllowed($ip)) {
            return false;
        }

        return true;
    }

    function checkRateLimit() {
        global $cfg;

        $window_start = date('Y-m-d H:i:s', time() - $this->rate_window);
        $window_end = date('Y-m-d H:i:s');

        $sql = sprintf(
            'SELECT COUNT(*) as count FROM %s
             WHERE token_id=%d
             AND created_at BETWEEN %s AND %s',
            API_LOG_TABLE,
            $this->token_id,
            db_input($window_start),
            db_input($window_end)
        );

        $result = db_query($sql);
        if ($result && ($row = db_fetch_array($result))) {
            $count = $row['count'];

            if ($count >= $this->rate_limit) {
                return false;
            }
        }

        return true;
    }

    function getRateLimitInfo() {
        $window_start = date('Y-m-d H:i:s', time() - $this->rate_window);
        $window_end = date('Y-m-d H:i:s');

        $sql = sprintf(
            'SELECT COUNT(*) as count FROM %s
             WHERE token_id=%d
             AND created_at BETWEEN %s AND %s',
            API_LOG_TABLE,
            $this->token_id,
            db_input($window_start),
            db_input($window_end)
        );

        $result = db_query($sql);
        $count = 0;
        if ($result && ($row = db_fetch_array($result))) {
            $count = $row['count'];
        }

        return array(
            'limit' => $this->rate_limit,
            'remaining' => max(0, $this->rate_limit - $count),
            'reset' => time() + $this->rate_window,
            'window' => $this->rate_window
        );
    }

    function updateUsage($ip, $endpoint) {
        $sql = sprintf(
            'UPDATE %s SET
             last_used_at=NOW(),
             last_used_ip=%s,
             last_used_endpoint=%s,
             total_requests=total_requests+1,
             updated_at=NOW()
             WHERE token_id=%d',
            API_TOKEN_TABLE,
            db_input($ip),
            db_input($endpoint),
            $this->token_id
        );

        return db_query($sql);
    }

    function update($data, &$errors) {
        $fields = array();

        if (isset($data['name'])) {
            if (!$data['name']) {
                $errors['name'] = 'Name is required';
            } else {
                $fields[] = 'name='.db_input($data['name']);
            }
        }

        if (isset($data['description'])) {
            $fields[] = 'description='.db_input($data['description']);
        }

        if (isset($data['is_active'])) {
            $fields[] = 'is_active='.db_input($data['is_active'] ? 1 : 0);
        }

        if (isset($data['permissions'])) {
            if (is_array($data['permissions'])) {
                $fields[] = 'permissions='.db_input(json_encode($data['permissions']));
            }
        }

        if (isset($data['ip_whitelist'])) {
            if (is_array($data['ip_whitelist'])) {
                $fields[] = 'ip_whitelist='.db_input(json_encode($data['ip_whitelist']));
            }
        }

        if (isset($data['ip_check_enabled'])) {
            $fields[] = 'ip_check_enabled='.db_input($data['ip_check_enabled'] ? 1 : 0);
        }

        if (isset($data['rate_limit'])) {
            $fields[] = 'rate_limit='.db_input($data['rate_limit']);
        }

        if (isset($data['expires_at'])) {
            $fields[] = 'expires_at='.db_input($data['expires_at']);
        }

        if ($errors || empty($fields)) {
            return false;
        }

        $fields[] = 'updated_at=NOW()';

        $sql = 'UPDATE '.API_TOKEN_TABLE.' SET '.implode(', ', $fields).
               ' WHERE token_id='.db_input($this->token_id);

        if (db_query($sql)) {
            $this->load($this->token_id);
            return true;
        }

        $errors['db'] = 'Database error: '.db_error();
        return false;
    }

    function delete() {
        $sql = 'DELETE FROM '.API_TOKEN_TABLE.' WHERE token_id='.db_input($this->token_id);
        return db_query($sql);
    }

    function create($data, &$errors) {
        global $cfg;

        if (!$data['name']) {
            $errors['name'] = 'Token name is required';
        }

        $token_raw = ApiToken::generateToken();
        $token_hash = hash('sha256', $token_raw);

        $token_type = isset($data['token_type']) ? $data['token_type'] : 'permanent';
        $permissions = isset($data['permissions']) ? json_encode($data['permissions']) : '[]';
        $ip_whitelist = isset($data['ip_whitelist']) ? json_encode($data['ip_whitelist']) : null;
        $ip_check_enabled = isset($data['ip_check_enabled']) ? ($data['ip_check_enabled'] ? 1 : 0) : 0;

        $rate_limit = isset($data['rate_limit']) ? $data['rate_limit'] : $cfg->get('api_default_rate_limit', 1000);
        $rate_window = isset($data['rate_window']) ? $data['rate_window'] : $cfg->get('api_default_rate_window', 3600);

        $expires_at = isset($data['expires_at']) ? db_input($data['expires_at']) : 'NULL';

        if ($errors) {
            return null;
        }

        $sql = sprintf(
            'INSERT INTO %s SET
             token=%s,
             name=%s,
             description=%s,
             staff_id=%s,
             token_type=%s,
             permissions=%s,
             ip_whitelist=%s,
             ip_check_enabled=%d,
             rate_limit=%d,
             rate_window=%d,
             is_active=1,
             expires_at=%s,
             created_at=NOW(),
             updated_at=NOW()',
            API_TOKEN_TABLE,
            db_input($token_hash),
            db_input($data['name']),
            db_input($data['description']),
            isset($data['staff_id']) ? db_input($data['staff_id']) : 'NULL',
            db_input($token_type),
            db_input($permissions),
            $ip_whitelist ? db_input($ip_whitelist) : 'NULL',
            $ip_check_enabled,
            $rate_limit,
            $rate_window,
            $expires_at
        );

        if (!db_query($sql)) {
            $errors['db'] = 'Unable to create token: '.db_error();
            return null;
        }

        $token_id = db_insert_id();
        $obj = new ApiToken($token_id);
        $obj->token_raw = $token_raw;
        return $obj;
    }

    static function generateToken() {
        return bin2hex(random_bytes(32));
    }

    static function lookup($token) {
        $sql = 'SELECT token_id FROM '.API_TOKEN_TABLE.' WHERE token='.db_input(hash('sha256', $token));
        $result = db_query($sql);

        if ($result && db_num_rows($result)) {
            $row = db_fetch_array($result);
            return new ApiToken($row['token_id']);
        }

        return null;
    }

    static function validateToken($token, $ip = null) {
        $apiToken = ApiToken::lookup($token);

        if (!$apiToken) {
            return null;
        }

        if (!$apiToken->validate($ip)) {
            return null;
        }

        return $apiToken;
    }

    function getTokens($filters = array()) {
        $where = array();

        if (isset($filters['staff_id'])) {
            $where[] = 'staff_id='.db_input($filters['staff_id']);
        }

        if (isset($filters['is_active'])) {
            $where[] = 'is_active='.db_input($filters['is_active'] ? 1 : 0);
        }

        if (isset($filters['token_type'])) {
            $where[] = 'token_type='.db_input($filters['token_type']);
        }

        $where_clause = $where ? 'WHERE '.implode(' AND ', $where) : '';

        $sql = 'SELECT * FROM '.API_TOKEN_TABLE.' '.$where_clause.' ORDER BY created_at DESC';

        $tokens = array();
        $result = db_query($sql);

        if ($result) {
            while ($row = db_fetch_array($result)) {
                $token = new ApiToken($row['token_id']);
                $tokens[] = $token;
            }
        }

        return $tokens;
    }

    function cleanupOldRateLimits() {
        $sql = sprintf(
            'DELETE FROM %s WHERE window_end < DATE_SUB(NOW(), INTERVAL 1 DAY)',
            API_RATE_LIMIT_TABLE
        );
        return db_query($sql);
    }

    function cleanupOldLogs($days = 30) {
        $sql = sprintf(
            'DELETE FROM %s WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)',
            API_LOG_TABLE,
            $days
        );
        return db_query($sql);
    }
}

if (!defined('API_TOKEN_TABLE')) {
    define('API_TOKEN_TABLE', TABLE_PREFIX.'api_tokens');
}
if (!defined('API_LOG_TABLE')) {
    define('API_LOG_TABLE', TABLE_PREFIX.'api_logs');
}
if (!defined('API_RATE_LIMIT_TABLE')) {
    define('API_RATE_LIMIT_TABLE', TABLE_PREFIX.'api_rate_limits');
}

?>
