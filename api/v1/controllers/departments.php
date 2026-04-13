<?php

if (!defined('THAPIV1INC')) die('Access Denied');

require_once(INCLUDE_DIR.'class.apicontroller.php');
require_once(INCLUDE_DIR.'class.dept.php');

class DepartmentsController extends ApiController {

    function index() {
        $this->checkMethod(array('GET'));
        $this->requirePermission('departments:read');

        $pagination = $this->getPaginationParams();

        $where_parts = array();

        $is_public = $this->getQuery('is_public');
        if ($is_public !== null) {
            $where_parts[] = 'ispublic=' . db_input($is_public ? 1 : 0);
        }

        $search = $this->getQuery('search');
        if ($search) {
            $search_term = db_real_escape(str_replace(array('%','_'), array('\\%','\\_'), $search));
            $where_parts[] = "dept_name LIKE '%{$search_term}%'";
        }

        $where = empty($where_parts) ? '' : ' WHERE ' . implode(' AND ', $where_parts);

        $count_sql = 'SELECT COUNT(*) as total FROM ' . DEPT_TABLE . $where;
        $count_result = db_query($count_sql);
        $count_row = db_fetch_array($count_result);
        $total = $count_row['total'];

        $sql = 'SELECT
                    dept.*,
                    email.email as dept_email,
                    email.name as email_name,
                    manager.firstname as manager_firstname,
                    manager.lastname as manager_lastname
                FROM ' . DEPT_TABLE . ' dept
                LEFT JOIN ' . EMAIL_TABLE . ' email ON dept.email_id = email.email_id
                LEFT JOIN ' . STAFF_TABLE . ' manager ON dept.manager_id = manager.staff_id'
                . $where
                . ' ORDER BY dept.dept_name ASC'
                . $this->buildLimitClause();

        $result = db_query($sql);

        if (!$result) {
            $this->handleDbError('fetch departments');
        }

        $departments = array();
        while ($row = db_fetch_array($result)) {
            $departments[] = $this->formatDepartmentListItem($row);
        }

        $this->paginated($departments, $total)->send();
    }

    function formatDepartmentListItem($row) {
        return array(
            'dept_id' => (int)$row['dept_id'],
            'name' => $row['dept_name'],
            'email' => array(
                'id' => (int)$row['email_id'],
                'address' => $row['dept_email'],
                'name' => $row['email_name']
            ),
            'manager' => $row['manager_id'] ? array(
                'id' => (int)$row['manager_id'],
                'name' => trim($row['manager_firstname'] . ' ' . $row['manager_lastname'])
            ) : null,
            'is_public' => $this->formatBool($row['ispublic']),
            'auto_response' => array(
                'ticket' => $this->formatBool($row['ticket_auto_response']),
                'message' => $this->formatBool($row['message_auto_response'])
            ),
            'can_append_signature' => $this->formatBool($row['can_append_signature']),
            'created_at' => $this->formatDate($row['created']),
            'updated_at' => $this->formatDate($row['updated'])
        );
    }

    function show() {
        $this->checkMethod(array('GET'));
        $this->requirePermission('departments:read');

        $dept_id = $this->getPathParam('id');

        if (!$dept_id || !is_numeric($dept_id)) {
            ApiResponse::badRequest('Invalid department ID')->send();
        }

        $sql = 'SELECT
                    dept.*,
                    email.email as dept_email,
                    email.name as email_name,
                    autoresp.email as autoresp_email,
                    autoresp.name as autoresp_name,
                    manager.firstname as manager_firstname,
                    manager.lastname as manager_lastname
                FROM ' . DEPT_TABLE . ' dept
                LEFT JOIN ' . EMAIL_TABLE . ' email ON dept.email_id = email.email_id
                LEFT JOIN ' . EMAIL_TABLE . ' autoresp ON dept.autoresp_email_id = autoresp.email_id
                LEFT JOIN ' . STAFF_TABLE . ' manager ON dept.manager_id = manager.staff_id
                WHERE dept.dept_id=' . db_input($dept_id);

        $result = db_query($sql);

        if (!$result || !db_num_rows($result)) {
            ApiResponse::notFound('Department not found')->send();
        }

        $row = db_fetch_array($result);

        $stats_sql = 'SELECT
                        COUNT(*) as total_tickets,
                        COUNT(CASE WHEN status="open" THEN 1 END) as open_tickets,
                        COUNT(CASE WHEN status="closed" THEN 1 END) as closed_tickets
                      FROM ' . TICKET_TABLE . '
                      WHERE dept_id=' . db_input($dept_id);

        $stats_result = db_query($stats_sql);
        $stats_row = db_fetch_array($stats_result);

        $staff_count_sql = 'SELECT COUNT(*) as count FROM ' . STAFF_TABLE .
                           ' WHERE dept_id=' . db_input($dept_id) . ' AND isactive=1';
        $staff_count_result = db_query($staff_count_sql);
        $staff_count_row = db_fetch_array($staff_count_result);

        $data = array(
            'dept_id' => (int)$row['dept_id'],
            'name' => $row['dept_name'],
            'signature' => $row['dept_signature'],
            'email' => array(
                'id' => (int)$row['email_id'],
                'address' => $row['dept_email'],
                'name' => $row['email_name']
            ),
            'autoresp_email' => $row['autoresp_email_id'] ? array(
                'id' => (int)$row['autoresp_email_id'],
                'address' => $row['autoresp_email'],
                'name' => $row['autoresp_name']
            ) : null,
            'manager' => $row['manager_id'] ? array(
                'id' => (int)$row['manager_id'],
                'name' => trim($row['manager_firstname'] . ' ' . $row['manager_lastname'])
            ) : null,
            'template_id' => (int)$row['tpl_id'],
            'is_public' => $this->formatBool($row['ispublic']),
            'auto_response' => array(
                'ticket' => $this->formatBool($row['ticket_auto_response']),
                'message' => $this->formatBool($row['message_auto_response'])
            ),
            'can_append_signature' => $this->formatBool($row['can_append_signature']),
            'statistics' => array(
                'staff_count' => (int)$staff_count_row['count'],
                'tickets' => array(
                    'total' => (int)$stats_row['total_tickets'],
                    'open' => (int)$stats_row['open_tickets'],
                    'closed' => (int)$stats_row['closed_tickets']
                )
            ),
            'created_at' => $this->formatDate($row['created']),
            'updated_at' => $this->formatDate($row['updated'])
        );

        ApiResponse::success($data)->send();
    }

    function create() {
        $this->checkMethod(array('POST'));
        $this->requirePermission('admin:*');

        $this->notImplemented();
    }

    function update() {
        $this->checkMethod(array('PUT', 'PATCH'));
        $this->requirePermission('admin:*');

        $this->notImplemented();
    }
}

?>
