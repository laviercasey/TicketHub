<?php

define('THAPIV1INC', TRUE);

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', '../../');
}

require_once(ROOT_PATH . 'main.inc.php');

require_once(INCLUDE_DIR . 'class.apiresponse.php');
require_once(INCLUDE_DIR . 'class.apicontroller.php');
require_once(INCLUDE_DIR . 'class.apimiddleware.php');
require_once(INCLUDE_DIR . 'class.api.php');

global $cfg;
if (!$cfg->get('api_enabled', 1)) {
    ApiResponse::serviceUnavailable('API is currently disabled')->send();
}

class ApiRouter {

    public $routes;
    public $middleware;

    function __construct() {
        $this->routes = array();
        $this->middleware = null;
    }

    function register($method, $pattern, $controller, $action, $auth_required = true) {
        $this->routes[] = array(
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'controller' => $controller,
            'action' => $action,
            'auth_required' => $auth_required
        );
    }

    function match($method, $path) {
        foreach ($this->routes as $route) {
            if ($route['method'] != $method && $route['method'] != 'ANY') {
                continue;
            }

            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route['pattern']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $path, $matches)) {
                $params = array();
                foreach ($matches as $key => $value) {
                    if (!is_numeric($key)) {
                        $params[$key] = $value;
                    }
                }

                return array(
                    'controller' => $route['controller'],
                    'action' => $route['action'],
                    'params' => $params,
                    'auth_required' => $route['auth_required']
                );
            }
        }

        return null;
    }

    function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $request_uri = $_SERVER['REQUEST_URI'];

        $path = parse_url($request_uri, PHP_URL_PATH);

        $path = preg_replace('#^/api/v1#', '', $path);

        if (empty($path) || $path == '/') {
            $this->showApiInfo();
            return;
        }

        $route = $this->match($method, $path);

        if (!$route) {
            ApiResponse::notFound('Endpoint not found')->send();
        }

        $this->middleware = new ApiMiddleware();

        if (!$route['auth_required']) {
            $this->middleware->skipAuth();
        }

        $token = $this->middleware->handle();

        $controller_file = ROOT_PATH . 'api/v1/controllers/' . $route['controller'] . '.php';

        if (!file_exists($controller_file)) {
            error_log('TicketHub API: Controller not found: ' . $route['controller']);
            ApiResponse::internalError('Internal server error')->send();
        }

        require_once($controller_file);

        $controller_class = ucfirst($route['controller']) . 'Controller';

        if (!class_exists($controller_class)) {
            error_log('TicketHub API: Controller class not found: ' . $controller_class);
            ApiResponse::internalError('Internal server error')->send();
        }

        $controller = new $controller_class();

        $controller->setToken($token);
        $controller->setPathParams($route['params']);

        $action = $route['action'];

        if (!method_exists($controller, $action)) {
            error_log('TicketHub API: Action not found: ' . $action . ' on ' . $controller_class);
            ApiResponse::internalError('Internal server error')->send();
        }

        try {
            $controller->$action();
        } catch (Exception $e) {
            error_log('API Exception: ' . $e->getMessage());
            ApiResponse::internalError(
                'An error occurred while processing your request',
                defined('DEBUG_MODE') && DEBUG_MODE ? $e->getMessage() : null
            )->send();
        }
    }

    function showApiInfo() {
        global $cfg;
        $info = array(
            'name' => 'TicketHub REST API',
            'version' => '1.0',
            'status' => 'active',
            'endpoints' => array(
                'tickets' => '/api/v1/tickets',
                'users' => '/api/v1/users',
                'staff' => '/api/v1/staff',
                'departments' => '/api/v1/departments',
                'tasks' => '/api/v1/tasks',
                'kb' => '/api/v1/kb/documents',
                'current_token' => '/api/v1/tokens/current',
                'documentation' => '/scp/admin.php?t=api-docs'
            ),
            'authentication' => array(
                'type' => 'Bearer Token',
                'header' => 'Authorization: Bearer YOUR_TOKEN'
            ),
            'rate_limiting' => array(
                'enabled' => true,
                'default_limit' => (int)$cfg->get('api_default_rate_limit', 1000),
                'default_window' => (int)$cfg->get('api_default_rate_window', 3600)
            )
        );

        ApiResponse::success($info)->send();
    }
}

$router = new ApiRouter();

$router->register('GET', '/', 'api', 'info', false);
$router->register('GET', '/info', 'api', 'info', false);

$router->register('GET', '/tokens/current', 'tokens', 'current', true);
$router->register('GET', '/tokens/usage', 'tokens', 'usage', true);

$router->register('GET', '/tickets', 'tickets', 'index', true);
$router->register('GET', '/tickets/{id}', 'tickets', 'show', true);
$router->register('POST', '/tickets', 'tickets', 'create', true);
$router->register('PUT', '/tickets/{id}', 'tickets', 'update', true);
$router->register('PATCH', '/tickets/{id}', 'tickets', 'update', true);
$router->register('DELETE', '/tickets/{id}', 'tickets', 'delete', true);

$router->register('GET', '/tickets/{id}/messages', 'tickets', 'messages', true);
$router->register('POST', '/tickets/{id}/messages', 'tickets', 'addMessage', true);

$router->register('GET', '/tickets/{id}/notes', 'tickets', 'notes', true);
$router->register('POST', '/tickets/{id}/notes', 'tickets', 'addNote', true);

$router->register('GET', '/tickets/{id}/attachments', 'tickets', 'attachments', true);

$router->register('GET', '/users', 'users', 'index', true);
$router->register('GET', '/users/{id}', 'users', 'show', true);
$router->register('POST', '/users', 'users', 'create', true);
$router->register('PUT', '/users/{id}', 'users', 'update', true);
$router->register('DELETE', '/users/{id}', 'users', 'delete', true);

$router->register('GET', '/staff', 'staff', 'index', true);
$router->register('GET', '/staff/{id}', 'staff', 'show', true);
$router->register('POST', '/staff', 'staff', 'create', true);
$router->register('PUT', '/staff/{id}', 'staff', 'update', true);

$router->register('GET', '/departments', 'departments', 'index', true);
$router->register('GET', '/departments/{id}', 'departments', 'show', true);
$router->register('POST', '/departments', 'departments', 'create', true);
$router->register('PUT', '/departments/{id}', 'departments', 'update', true);

$router->register('GET', '/help-topics', 'helptopics', 'index', true);
$router->register('GET', '/help-topics/{id}', 'helptopics', 'show', true);

$router->register('GET', '/priorities', 'priorities', 'index', true);

$router->register('GET', '/tasks', 'tasks', 'index', true);
$router->register('GET', '/tasks/{id}', 'tasks', 'show', true);
$router->register('POST', '/tasks', 'tasks', 'create', true);
$router->register('PUT', '/tasks/{id}', 'tasks', 'update', true);
$router->register('PATCH', '/tasks/{id}', 'tasks', 'update', true);
$router->register('DELETE', '/tasks/{id}', 'tasks', 'delete', true);

$router->register('PUT', '/tasks/{id}/status', 'tasks', 'updateStatus', true);
$router->register('PATCH', '/tasks/{id}/status', 'tasks', 'updateStatus', true);

$router->register('POST', '/tasks/{id}/assignees', 'tasks', 'addAssignee', true);
$router->register('DELETE', '/tasks/{id}/assignees/{staff_id}', 'tasks', 'removeAssignee', true);

$router->register('GET', '/taskboards', 'tasks', 'listBoards', true);
$router->register('GET', '/taskboards/{id}', 'tasks', 'showBoard', true);
$router->register('POST', '/taskboards', 'tasks', 'createBoard', true);
$router->register('PUT', '/taskboards/{id}', 'tasks', 'updateBoard', true);
$router->register('PATCH', '/taskboards/{id}', 'tasks', 'updateBoard', true);
$router->register('DELETE', '/taskboards/{id}', 'tasks', 'deleteBoard', true);

$router->register('GET', '/kb/documents', 'kb', 'index', true);
$router->register('GET', '/kb/documents/{id}', 'kb', 'show', true);
$router->register('POST', '/kb/documents', 'kb', 'create', true);
$router->register('PUT', '/kb/documents/{id}', 'kb', 'update', true);
$router->register('PATCH', '/kb/documents/{id}', 'kb', 'update', true);
$router->register('DELETE', '/kb/documents/{id}', 'kb', 'delete', true);

$router->register('POST', '/kb/documents/search', 'kb', 'search', true);
$router->register('GET', '/kb/documents/search', 'kb', 'search', true);

$router->dispatch();

?>
