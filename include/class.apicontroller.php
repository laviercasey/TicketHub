<?php

require_once(INCLUDE_DIR.'class.apiresponse.php');
require_once(INCLUDE_DIR.'class.apitoken.php');

class ApiController {

    public $token;
    public $request_method;
    public $request_data;
    public $query_params;
    public $path_params;
    public $start_time;

    public function __construct() {
        $this->start_time = microtime(true);
        $this->request_method = $_SERVER['REQUEST_METHOD'];
        $this->query_params = $_GET;
        $this->path_params = array();
        $this->parseRequestData();
    }

    function parseRequestData() {
        $this->request_data = array();

        if ($this->request_method == 'GET') {
            $this->request_data = $_GET;
            return;
        }

        $raw_input = file_get_contents('php://input');

        $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

        if (strpos($content_type, 'application/json') !== false) {
            $this->request_data = json_decode($raw_input, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->request_data = array();
            }
        } else {
            parse_str($raw_input, $this->request_data);
        }

        if (!empty($_POST)) {
            $this->request_data = array_merge($this->request_data, $_POST);
        }
    }

    function getInput($key, $default = null) {
        return isset($this->request_data[$key]) ? $this->request_data[$key] : $default;
    }

    function getQuery($key, $default = null) {
        return isset($this->query_params[$key]) ? $this->query_params[$key] : $default;
    }

    function getPathParam($key, $default = null) {
        return isset($this->path_params[$key]) ? $this->path_params[$key] : $default;
    }

    function setPathParams($params) {
        $this->path_params = $params;
    }

    function validateRequired($fields, &$errors) {
        foreach ($fields as $field) {
            if (!isset($this->request_data[$field]) || $this->request_data[$field] === '') {
                $errors[$field] = ucfirst($field) . ' is required';
            }
        }

        return empty($errors);
    }

    function validateEmail($email, &$errors, $field_name = 'email') {
        if (!Validator::is_email($email)) {
            $errors[$field_name] = 'Invalid email address';
            return false;
        }
        return true;
    }

    function sanitize($value, $type = 'string') {
        switch ($type) {
            case 'int':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'bool':
                return (bool)$value;
            case 'email':
                return filter_var($value, FILTER_SANITIZE_EMAIL);
            case 'url':
                return filter_var($value, FILTER_SANITIZE_URL);
            case 'string':
            default:
                return Format::striptags($value);
        }
    }

    function getPaginationParams() {
        global $cfg;

        $page = (int)$this->getQuery('page', 1);
        $per_page = (int)$this->getQuery('per_page', 20);

        if ($page < 1) {
            $page = 1;
        }

        $max_per_page = $cfg->get('api_max_per_page', 100);
        if ($per_page < 1) {
            $per_page = 20;
        } elseif ($per_page > $max_per_page) {
            $per_page = $max_per_page;
        }

        $offset = ($page - 1) * $per_page;

        return array(
            'page' => $page,
            'per_page' => $per_page,
            'offset' => $offset,
            'limit' => $per_page
        );
    }

    function buildLimitClause() {
        $pagination = $this->getPaginationParams();
        return ' LIMIT ' . $pagination['offset'] . ', ' . $pagination['limit'];
    }

    function getSortParams($allowed_fields = array(), $default_sort = 'created', $default_order = 'DESC') {
        $sort = $this->getQuery('sort', $default_sort);
        $order = strtoupper($this->getQuery('order', $default_order));

        if (empty($allowed_fields) || !in_array($sort, $allowed_fields)) {
            $sort = $default_sort;
        }

        if (!in_array($order, array('ASC', 'DESC'))) {
            $order = $default_order;
        }

        return array(
            'sort' => $sort,
            'order' => $order
        );
    }

    function buildOrderClause($allowed_fields = array(), $default_sort = 'created', $default_order = 'DESC') {
        $sort_params = $this->getSortParams($allowed_fields, $default_sort, $default_order);
        return ' ORDER BY ' . $sort_params['sort'] . ' ' . $sort_params['order'];
    }

    function checkMethod($allowed_methods) {
        if (!in_array($this->request_method, $allowed_methods)) {
            ApiResponse::methodNotAllowed($allowed_methods)->send();
        }
    }

    function requireAuth() {
        if (!$this->token || !$this->token->isActive()) {
            ApiResponse::unauthorized('Authentication required')->send();
        }
    }

    function requirePermission($permission) {
        $this->requireAuth();

        if (!$this->token->hasPermission($permission)) {
            ApiResponse::insufficientPermissions(
                'This action requires permission: ' . $permission
            )->send();
        }
    }

    function setToken($token) {
        $this->token = $token;
    }

    function getAuthUser() {
        if (!$this->token || !$this->token->staff_id) {
            return null;
        }
        return Staff::lookup($this->token->staff_id);
    }

    function getResponseTime() {
        return round((microtime(true) - $this->start_time) * 1000);
    }

    function logRequest($response_code) {
        if (!$this->token) {
            return;
        }

        $endpoint = $_SERVER['REQUEST_URI'];
        $response_time = $this->getResponseTime();
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

        Api::logRequest(
            $this->token->getId(),
            $endpoint,
            $this->request_method,
            $this->request_data,
            $response_code,
            $response_time,
            $ip,
            $user_agent
        );
    }

    function buildFilters($allowed_filters, $table_prefix = '') {
        $where = array();

        foreach ($allowed_filters as $param => $config) {
            $value = $this->getQuery($param);

            if ($value === null || $value === '') {
                continue;
            }

            $column = isset($config['column']) ? $config['column'] : $param;
            $operator = isset($config['operator']) ? $config['operator'] : '=';
            $type = isset($config['type']) ? $config['type'] : 'string';

            if ($table_prefix) {
                $column = $table_prefix . '.' . $column;
            }

            $value = $this->sanitize($value, $type);

            switch ($operator) {
                case 'LIKE':
                    $escaped = db_real_escape(str_replace(array('%','_'), array('\\%','\\_'), $value));
                    $where[] = $column . " LIKE '%" . $escaped . "%'";
                    break;
                case 'IN':
                    $values = is_array($value) ? $value : explode(',', $value);
                    $values = array_map('db_input', $values);
                    $where[] = $column . ' IN (' . implode(',', $values) . ')';
                    break;
                case '>=':
                case '<=':
                case '>':
                case '<':
                case '!=':
                    $where[] = $column . ' ' . $operator . ' ' . db_input($value);
                    break;
                case '=':
                default:
                    $where[] = $column . '=' . db_input($value);
                    break;
            }
        }

        return empty($where) ? '' : ' WHERE ' . implode(' AND ', $where);
    }

    function buildSearchClause($search_term, $search_fields, $table_prefix = '') {
        if (!$search_term || empty($search_fields)) {
            return '';
        }

        $search_term = db_real_escape(str_replace(array('%','_'), array('\\%','\\_'), $search_term));
        $conditions = array();

        foreach ($search_fields as $field) {
            if ($table_prefix) {
                $field = $table_prefix . '.' . $field;
            }
            $conditions[] = $field . " LIKE '%" . $search_term . "%'";
        }

        return ' (' . implode(' OR ', $conditions) . ')';
    }

    function formatDate($date) {
        if (!$date || $date == '0000-00-00 00:00:00') {
            return null;
        }
        return gmdate('Y-m-d\TH:i:s\Z', strtotime($date));
    }

    function formatBool($value) {
        return (bool)$value;
    }

    function handleDbError($operation = 'operation') {
        $error = db_error();
        error_log("API Database Error [$operation]: " . $error);

        ApiResponse::internalError(
            'Database error occurred',
            DEBUG_MODE ? $error : null
        )->send();
    }

    function handleException($e, $operation = 'operation') {
        error_log("API Exception [$operation]: " . $e->getMessage());

        ApiResponse::internalError(
            'An error occurred while processing your request',
            DEBUG_MODE ? $e->getMessage() : null
        )->send();
    }

    function success($data = null, $message = null) {
        return ApiResponse::success($data, $message);
    }

    function error($code, $message, $status_code = 400) {
        return ApiResponse::error($code, $message, $status_code);
    }

    function paginated($data, $total) {
        $pagination = $this->getPaginationParams();
        return ApiResponse::paginated($data, $total, $pagination['page'], $pagination['per_page']);
    }

    function notImplemented() {
        ApiResponse::serviceUnavailable('This endpoint is not yet implemented')->send();
    }

    function handleRequest() {
        $this->notImplemented();
    }
}

?>
