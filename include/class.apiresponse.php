<?php

class ApiResponse {

    public $data;
    public $error;
    public $meta;
    public $headers;
    public $status_code;

    public function __construct() {
        $this->data = null;
        $this->error = null;
        $this->meta = array();
        $this->headers = array();
        $this->status_code = 200;

        $this->meta['version'] = '1.0';
        $this->meta['timestamp'] = gmdate('Y-m-d\TH:i:s\Z');
        $this->meta['request_id'] = $this->generateRequestId();
    }

    function setData($data) {
        $this->data = $data;
        return $this;
    }

    function setError($code, $message, $details = null) {
        $this->error = array(
            'code' => $code,
            'message' => $message
        );

        if ($details) {
            $this->error['details'] = $details;
        }

        return $this;
    }

    function setStatusCode($code) {
        $this->status_code = $code;
        return $this;
    }

    function addHeader($name, $value) {
        $this->headers[$name] = $value;
        return $this;
    }

    function setPagination($total, $count, $per_page, $current_page) {
        $total_pages = ceil($total / $per_page);

        $this->meta['pagination'] = array(
            'total' => (int)$total,
            'count' => (int)$count,
            'per_page' => (int)$per_page,
            'current_page' => (int)$current_page,
            'total_pages' => (int)$total_pages,
            'links' => array(
                'next' => null,
                'prev' => null
            )
        );

        if ($current_page < $total_pages) {
            $this->meta['pagination']['links']['next'] = $this->buildPaginationUrl($current_page + 1);
        }

        if ($current_page > 1) {
            $this->meta['pagination']['links']['prev'] = $this->buildPaginationUrl($current_page - 1);
        }

        return $this;
    }

    function setRateLimitHeaders($limit, $remaining, $reset, $window) {
        $this->addHeader('X-RateLimit-Limit', $limit);
        $this->addHeader('X-RateLimit-Remaining', $remaining);
        $this->addHeader('X-RateLimit-Reset', $reset);
        $this->addHeader('X-RateLimit-Window', $window);
        return $this;
    }

    function generateRequestId() {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    function buildPaginationUrl($page) {
        $path = $_SERVER['REQUEST_URI'];

        $path = preg_replace('/([?&])page=\d+/', '$1', $path);

        $separator = (strpos($path, '?') !== false) ? '&' : '?';
        return $path . $separator . 'page=' . $page;
    }

    function send() {
        http_response_code($this->status_code);

        header('Content-Type: application/json; charset=utf-8');

        header('X-API-Version: 1.0');

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        $this->sendCorsHeaders();

        $response = array(
            'success' => $this->error ? false : true
        );

        if ($this->data !== null) {
            $response['data'] = $this->data;
        }

        if ($this->error) {
            $response['error'] = $this->error;
        }

        if (!empty($this->meta)) {
            $response['meta'] = $this->meta;
        }

        echo json_encode($response);
        exit;
    }

    function sendCorsHeaders() {
        $allowed_origins = array();
        if (defined('API_ALLOWED_ORIGINS')) {
            $allowed_origins = array_map('trim', explode(',', API_ALLOWED_ORIGINS));
        }
        if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
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

    static function success($data = null, $message = null) {
        $response = new ApiResponse();
        $response->setStatusCode(200);
        $response->setData($data);

        if ($message) {
            $response->meta['message'] = $message;
        }

        return $response;
    }

    static function created($data, $message = 'Resource created successfully') {
        $response = new ApiResponse();
        $response->setStatusCode(201);
        $response->setData($data);
        $response->meta['message'] = $message;

        return $response;
    }

    static function noContent() {
        http_response_code(204);
        exit;
    }

    static function badRequest($message = 'Bad request', $details = null) {
        $response = new ApiResponse();
        $response->setStatusCode(400);
        $response->setError('BAD_REQUEST', $message, $details);

        return $response;
    }

    static function validationError($errors, $message = 'Validation failed') {
        $response = new ApiResponse();
        $response->setStatusCode(400);
        $response->setError('VALIDATION_ERROR', $message, $errors);

        return $response;
    }

    static function unauthorized($message = 'Unauthorized') {
        $response = new ApiResponse();
        $response->setStatusCode(401);
        $response->setError('UNAUTHORIZED', $message);

        return $response;
    }

    static function invalidToken($message = 'Invalid or expired token') {
        $response = new ApiResponse();
        $response->setStatusCode(401);
        $response->setError('INVALID_TOKEN', $message);

        return $response;
    }

    static function forbidden($message = 'Forbidden') {
        $response = new ApiResponse();
        $response->setStatusCode(403);
        $response->setError('FORBIDDEN', $message);

        return $response;
    }

    static function insufficientPermissions($message = 'Insufficient permissions') {
        $response = new ApiResponse();
        $response->setStatusCode(403);
        $response->setError('INSUFFICIENT_PERMISSIONS', $message);

        return $response;
    }

    static function notFound($message = 'Resource not found') {
        $response = new ApiResponse();
        $response->setStatusCode(404);
        $response->setError('RESOURCE_NOT_FOUND', $message);

        return $response;
    }

    static function methodNotAllowed($allowed_methods = array()) {
        $response = new ApiResponse();
        $response->setStatusCode(405);
        $response->setError('METHOD_NOT_ALLOWED', 'Method not allowed');

        if (!empty($allowed_methods)) {
            $response->addHeader('Allow', implode(', ', $allowed_methods));
        }

        return $response;
    }

    static function conflict($message = 'Resource already exists') {
        $response = new ApiResponse();
        $response->setStatusCode(409);
        $response->setError('DUPLICATE_RESOURCE', $message);

        return $response;
    }

    static function unprocessableEntity($message = 'Unprocessable entity', $details = null) {
        $response = new ApiResponse();
        $response->setStatusCode(422);
        $response->setError('UNPROCESSABLE_ENTITY', $message, $details);

        return $response;
    }

    static function rateLimitExceeded($retry_after) {
        $response = new ApiResponse();
        $response->setStatusCode(429);
        $response->setError(
            'RATE_LIMIT_EXCEEDED',
            'Rate limit exceeded. Try again in ' . $retry_after . ' seconds.'
        );
        $response->error['retry_after'] = $retry_after;
        $response->addHeader('Retry-After', $retry_after);

        return $response;
    }

    static function internalError($message = 'Internal server error', $debug = null) {
        $response = new ApiResponse();
        $response->setStatusCode(500);
        $response->setError('INTERNAL_ERROR', $message);

        if ($debug && defined('DEBUG_MODE') && DEBUG_MODE) {
            $response->error['debug'] = $debug;
        }

        return $response;
    }

    static function serviceUnavailable($message = 'Service temporarily unavailable') {
        $response = new ApiResponse();
        $response->setStatusCode(503);
        $response->setError('SERVICE_UNAVAILABLE', $message);

        return $response;
    }

    static function json($data, $status_code = 200) {
        $response = new ApiResponse();
        $response->setStatusCode($status_code);
        $response->setData($data);
        $response->send();
    }

    static function error($code, $message, $status_code = 400, $details = null) {
        $response = new ApiResponse();
        $response->setStatusCode($status_code);
        $response->setError($code, $message, $details);
        $response->send();
    }

    static function paginated($data, $total, $page, $per_page) {
        $response = new ApiResponse();
        $response->setData($data);
        $response->setPagination($total, count($data), $per_page, $page);
        return $response;
    }
}

function api_response() {
    return new ApiResponse();
}

?>
