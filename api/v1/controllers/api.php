<?php

if (!defined('THAPIV1INC')) die('Access Denied');

require_once(INCLUDE_DIR.'class.apicontroller.php');

class ApiController extends ApiController {

    function info() {
        global $cfg;

        $this->checkMethod(array('GET'));

        $info = array(
            'name' => 'TicketHub REST API',
            'version' => '1.0',
            'status' => 'active',
            'server_time' => gmdate('Y-m-d\TH:i:s\Z'),
            'endpoints' => array(
                'tickets' => array(
                    'list' => 'GET /api/v1/tickets',
                    'show' => 'GET /api/v1/tickets/{id}',
                    'create' => 'POST /api/v1/tickets',
                    'update' => 'PUT /api/v1/tickets/{id}',
                    'delete' => 'DELETE /api/v1/tickets/{id}'
                ),
                'users' => array(
                    'list' => 'GET /api/v1/users',
                    'show' => 'GET /api/v1/users/{id}',
                    'create' => 'POST /api/v1/users'
                ),
                'staff' => array(
                    'list' => 'GET /api/v1/staff',
                    'show' => 'GET /api/v1/staff/{id}'
                ),
                'departments' => array(
                    'list' => 'GET /api/v1/departments',
                    'show' => 'GET /api/v1/departments/{id}'
                ),
                'tasks' => array(
                    'list' => 'GET /api/v1/tasks',
                    'show' => 'GET /api/v1/tasks/{id}',
                    'create' => 'POST /api/v1/tasks'
                ),
                'kb' => array(
                    'list' => 'GET /api/v1/kb/documents',
                    'show' => 'GET /api/v1/kb/documents/{id}'
                ),
                'tokens' => array(
                    'current' => 'GET /api/v1/tokens/current',
                    'usage' => 'GET /api/v1/tokens/usage'
                )
            ),
            'authentication' => array(
                'type' => 'Bearer Token',
                'header' => 'Authorization: Bearer YOUR_TOKEN',
                'description' => 'Obtain API token from admin panel'
            ),
            'rate_limiting' => array(
                'enabled' => true,
                'default_limit' => (int)$cfg->get('api_default_rate_limit', 1000),
                'default_window' => (int)$cfg->get('api_default_rate_window', 3600),
                'headers' => array(
                    'X-RateLimit-Limit',
                    'X-RateLimit-Remaining',
                    'X-RateLimit-Reset',
                    'X-RateLimit-Window'
                )
            ),
            'response_format' => array(
                'success' => array(
                    'success' => true,
                    'data' => '{ ... }',
                    'meta' => '{ version, timestamp, pagination? }'
                ),
                'error' => array(
                    'success' => false,
                    'error' => array(
                        'code' => 'ERROR_CODE',
                        'message' => 'Error message',
                        'details' => '{ ... }'
                    ),
                    'meta' => '{ version, timestamp }'
                )
            ),
            'pagination' => array(
                'query_params' => array(
                    'page' => 'Page number (default: 1)',
                    'per_page' => 'Items per page (default: 20, max: 100)'
                ),
                'response_meta' => array(
                    'total',
                    'count',
                    'per_page',
                    'current_page',
                    'total_pages',
                    'links' => array('next', 'prev')
                )
            ),
            'links' => array(
                'documentation' => '/scp/admin.php?t=api-docs',
                'admin_panel' => '/scp/admin.php?t=api',
                'github' => 'https://github.com/LaverCasey/TicketHub'
            )
        );

        ApiResponse::success($info)->send();
    }
}

?>
