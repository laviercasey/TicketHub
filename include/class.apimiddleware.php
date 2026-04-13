<?php

require_once(INCLUDE_DIR.'class.apitoken.php');
require_once(INCLUDE_DIR.'class.apiresponse.php');
require_once(INCLUDE_DIR.'class.api.php');
require_once(INCLUDE_DIR.'class.apisecurity.php');

class ApiMiddleware {

    public $token;
    public $start_time;
    public $skip_auth;

    public function __construct() {
        $this->token = null;
        $this->start_time = microtime(true);
        $this->skip_auth = false;
    }

    function handle() {
        global $cfg;

        if ($cfg->get('api_security_headers_enabled', 1)) {
            ApiSecurity::applySecurityHeaders();
        }

        $this->handleCors();

        $client_ip = ApiSecurity::getClientIp();
        if (ApiSecurity::isBlacklistedIp($client_ip)) {
            ApiSecurity::auditLog('blocked_ip_access', array('ip' => $client_ip), 'warning');
            ApiResponse::forbidden('Access denied')->send();
        }

        if ($cfg->get('api_security_scan_enabled', 1)) {
            $errors = array();
            if (!ApiSecurity::scanRequest($errors)) {
                ApiResponse::badRequest($errors['security'])->send();
            }
        }

        $this->enforceHttps();

        if ($cfg->get('api_brute_force_protection', 1)) {
            $identifier = $client_ip . '_api';
            $max_attempts = $cfg->get('api_brute_force_max_attempts', 5);
            $window = $cfg->get('api_brute_force_window', 300);

            if (!ApiSecurity::checkBruteForce($identifier, $max_attempts, $window)) {
                ApiSecurity::auditLog('brute_force_blocked', array(
                    'ip' => $client_ip,
                    'identifier' => $identifier
                ), 'warning');
                ApiResponse::tooManyRequests('Too many failed attempts. Please try again later.')->send();
            }
        }

        if (!$this->skip_auth) {
            $this->authenticate();
        }

        if ($this->token && !$this->skip_auth) {
            $this->checkRateLimit();
        }

        return $this->token;
    }

    function handleCors() {
        $allowed_origins = array();
        if (defined('API_ALLOWED_ORIGINS')) {
            $allowed_origins = array_map('trim', explode(',', API_ALLOWED_ORIGINS));
        }
        if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins, true)) {
            header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
            header('Vary: Origin');
            header('Access-Control-Allow-Credentials: true');
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');

        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    function enforceHttps() {
        global $cfg;

        if (!$cfg->get('api_require_https', 0)) {
            return;
        }

        $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    || $_SERVER['SERVER_PORT'] == 443
                    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https');

        if (!$is_https) {
            ApiResponse::forbidden('HTTPS is required for API access')->send();
        }
    }

    function authenticate() {
        global $cfg;

        $token_string = $this->extractToken();

        if (!$token_string) {
            ApiResponse::unauthorized('No authentication token provided')->send();
        }

        if (!ApiSecurity::validateTokenFormat($token_string)) {
            $client_ip = ApiSecurity::getClientIp();
            ApiSecurity::recordFailedAttempt($client_ip . '_api');

            if ($cfg->get('api_audit_log_enabled', 1)) {
                ApiSecurity::auditLog('invalid_token_format', array(
                    'ip' => $client_ip,
                    'token_prefix' => substr($token_string, 0, 8)
                ), 'warning');
            }

            ApiResponse::invalidToken('Invalid token format')->send();
        }

        $ip = ApiSecurity::getClientIp();
        $this->token = ApiToken::validateToken($token_string, $ip);

        if (!$this->token) {
            ApiSecurity::recordFailedAttempt($ip . '_api');

            if ($cfg->get('api_audit_log_enabled', 1)) {
                ApiSecurity::auditLog('authentication_failed', array(
                    'ip' => $ip,
                    'token_prefix' => substr($token_string, 0, 8)
                ), 'warning');
            }

            ApiResponse::invalidToken('Invalid or expired token')->send();
        }

        ApiSecurity::clearFailedAttempts($ip . '_api');

        if ($cfg->get('api_audit_log_enabled', 1)) {
            ApiSecurity::auditLog('authentication_success', array(
                'token_id' => $this->token->getId(),
                'ip' => $ip
            ), 'info');
        }

        $endpoint = $_SERVER['REQUEST_URI'];
        $this->token->updateUsage($ip, $endpoint);

        return $this->token;
    }

    function extractToken() {
        $auth_header = '';
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) {
                $auth_header = $headers['Authorization'];
            }
        }

        if ($auth_header) {
            if (preg_match('/Bearer\s+(.+)/i', $auth_header, $matches)) {
                return trim($matches[1]);
            }
            return trim($auth_header);
        }

        return null;
    }

    function checkRateLimit() {
        if (!$this->token) {
            return;
        }

        if (!$this->token->checkRateLimit()) {
            $rate_info = $this->token->getRateLimitInfo();
            $retry_after = $this->token->getRateWindow();

            ApiResponse::rateLimitExceeded($retry_after)->send();
        }

        $this->addRateLimitHeaders();
    }

    function addRateLimitHeaders() {
        if (!$this->token) {
            return;
        }

        $rate_info = $this->token->getRateLimitInfo();

        header('X-RateLimit-Limit: ' . $rate_info['limit']);
        header('X-RateLimit-Remaining: ' . $rate_info['remaining']);
        header('X-RateLimit-Reset: ' . $rate_info['reset']);
        header('X-RateLimit-Window: ' . $rate_info['window']);
    }

    function logRequest($response_code) {
        if (!$this->token) {
            return;
        }

        $endpoint = $_SERVER['REQUEST_URI'];
        $method = $_SERVER['REQUEST_METHOD'];
        $response_time = $this->getResponseTime();
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

        $request_data = array();
        if ($method != 'GET') {
            $raw_input = file_get_contents('php://input');
            $request_data = json_decode($raw_input, true);
        }

        Api::logRequest(
            $this->token->getId(),
            $endpoint,
            $method,
            $request_data,
            $response_code,
            $response_time,
            $ip,
            $user_agent
        );
    }

    function getResponseTime() {
        return round((microtime(true) - $this->start_time) * 1000);
    }

    function skipAuth() {
        $this->skip_auth = true;
    }

    function getToken() {
        return $this->token;
    }

    function hasPermission($permission) {
        if (!$this->token) {
            return false;
        }

        return $this->token->hasPermission($permission);
    }

    function requirePermission($permission) {
        if (!$this->hasPermission($permission)) {
            ApiResponse::insufficientPermissions(
                'This action requires permission: ' . $permission
            )->send();
        }
    }

    function run($skip_auth = false) {
        $middleware = new ApiMiddleware();

        if ($skip_auth) {
            $middleware->skipAuth();
        }

        return $middleware->handle();
    }

    function logAfterResponse($token, $response_code) {
        if (!$token) {
            return;
        }

        $middleware = new ApiMiddleware();
        $middleware->token = $token;
        $middleware->logRequest($response_code);
    }
}

function api_shutdown_handler() {
    global $api_middleware, $api_response_code;

    if (isset($api_middleware) && isset($api_response_code)) {
        ApiMiddleware::logAfterResponse($api_middleware->getToken(), $api_response_code);
    }
}

register_shutdown_function('api_shutdown_handler');

?>
