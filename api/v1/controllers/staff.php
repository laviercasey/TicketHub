<?php

if (!defined('THAPIV1INC')) die('Access Denied');

require_once(INCLUDE_DIR.'class.apicontroller.php');
require_once(INCLUDE_DIR.'class.staff.php');

class StaffController extends ApiController {

    function index() {
        $this->checkMethod(array('GET'));
        $this->requirePermission('staff:read');

        $pagination = $this->getPaginationParams();

        $where_parts = array();

        $dept_id = $this->getQuery('dept_id');
        if ($dept_id) {
            $where_parts[] = 'dept_id=' . db_input($dept_id);
        }

        $is_active = $this->getQuery('is_active');
        if ($is_active !== null) {
            $where_parts[] = 'isactive=' . db_input($is_active ? 1 : 0);
        }

        $is_admin = $this->getQuery('is_admin');
        if ($is_admin !== null) {
            $where_parts[] = 'isadmin=' . db_input($is_admin ? 1 : 0);
        }

        $search = $this->getQuery('search');
        if ($search) {
            $search_term = db_real_escape(str_replace(array('%','_'), array('\\%','\\_'), $search));
            $where_parts[] = "(firstname LIKE '%{$search_term}%' OR lastname LIKE '%{$search_term}%' OR email LIKE '%{$search_term}%' OR username LIKE '%{$search_term}%')";
        }

        $where = empty($where_parts) ? '' : ' WHERE ' . implode(' AND ', $where_parts);

        $count_sql = 'SELECT COUNT(*) as total FROM ' . STAFF_TABLE . $where;
        $count_result = db_query($count_sql);
        $count_row = db_fetch_array($count_result);
        $total = $count_row['total'];

        $allowed_sort = array('firstname', 'lastname', 'email', 'username', 'created', 'lastlogin');
        $sort_params = $this->getSortParams($allowed_sort, 'lastname', 'ASC');

        $sql = 'SELECT
                    staff.*,
                    dept.dept_name,
                    grp.group_name
                FROM ' . STAFF_TABLE . ' staff
                LEFT JOIN ' . DEPT_TABLE . ' dept ON staff.dept_id = dept.dept_id
                LEFT JOIN ' . GROUP_TABLE . ' grp ON staff.group_id = grp.group_id'
                . $where
                . ' ORDER BY ' . $sort_params['sort'] . ' ' . $sort_params['order']
                . $this->buildLimitClause();

        $result = db_query($sql);

        if (!$result) {
            $this->handleDbError('fetch staff');
        }

        $staff_list = array();
        while ($row = db_fetch_array($result)) {
            $staff_list[] = $this->formatStaffListItem($row);
        }

        $this->paginated($staff_list, $total)->send();
    }

    function formatStaffListItem($row) {
        return array(
            'staff_id' => (int)$row['staff_id'],
            'username' => $row['username'],
            'firstname' => $row['firstname'],
            'lastname' => $row['lastname'],
            'name' => trim($row['firstname'] . ' ' . $row['lastname']),
            'email' => $row['email'],
            'phone' => $row['phone'],
            'mobile' => $row['mobile'],
            'department' => array(
                'id' => (int)$row['dept_id'],
                'name' => $row['dept_name']
            ),
            'group' => $row['group_id'] ? array(
                'id' => (int)$row['group_id'],
                'name' => $row['group_name']
            ) : null,
            'is_active' => $this->formatBool($row['isactive']),
            'is_admin' => $this->formatBool($row['isadmin']),
            'is_visible' => $this->formatBool($row['isvisible']),
            'on_vacation' => $this->formatBool($row['onvacation']),
            'created_at' => $this->formatDate($row['created']),
            'last_login_at' => $this->formatDate($row['lastlogin'])
        );
    }

    function show() {
        $this->checkMethod(array('GET'));
        $this->requirePermission('staff:read');

        $staff_id = $this->getPathParam('id');

        if (!$staff_id || !is_numeric($staff_id)) {
            ApiResponse::badRequest('Invalid staff ID')->send();
        }

        $sql = 'SELECT
                    staff.*,
                    dept.dept_name,
                    grp.group_name
                FROM ' . STAFF_TABLE . ' staff
                LEFT JOIN ' . DEPT_TABLE . ' dept ON staff.dept_id = dept.dept_id
                LEFT JOIN ' . GROUP_TABLE . ' grp ON staff.group_id = grp.group_id
                WHERE staff.staff_id=' . db_input($staff_id);

        $result = db_query($sql);

        if (!$result || !db_num_rows($result)) {
            ApiResponse::notFound('Staff member not found')->send();
        }

        $row = db_fetch_array($result);

        $tickets_sql = 'SELECT
                            COUNT(*) as total,
                            COUNT(CASE WHEN status="open" THEN 1 END) as open,
                            COUNT(CASE WHEN status="closed" THEN 1 END) as closed
                        FROM ' . TICKET_TABLE . '
                        WHERE staff_id=' . db_input($staff_id);

        $tickets_result = db_query($tickets_sql);
        $tickets_row = db_fetch_array($tickets_result);

        $data = array(
            'staff_id' => (int)$row['staff_id'],
            'username' => $row['username'],
            'firstname' => $row['firstname'],
            'lastname' => $row['lastname'],
            'name' => trim($row['firstname'] . ' ' . $row['lastname']),
            'email' => $row['email'],
            'phone' => $row['phone'],
            'phone_ext' => $row['phone_ext'],
            'mobile' => $row['mobile'],
            'signature' => $row['signature'],
            'department' => array(
                'id' => (int)$row['dept_id'],
                'name' => $row['dept_name']
            ),
            'group' => $row['group_id'] ? array(
                'id' => (int)$row['group_id'],
                'name' => $row['group_name']
            ) : null,
            'is_active' => $this->formatBool($row['isactive']),
            'is_admin' => $this->formatBool($row['isadmin']),
            'is_visible' => $this->formatBool($row['isvisible']),
            'on_vacation' => $this->formatBool($row['onvacation']),
            'settings' => array(
                'max_page_size' => (int)$row['max_page_size'],
                'auto_refresh_rate' => (int)$row['auto_refresh_rate'],
                'daylight_saving' => $this->formatBool($row['daylight_saving']),
                'append_signature' => $this->formatBool($row['append_signature']),
                'timezone_offset' => (float)$row['timezone_offset']
            ),
            'assigned_tickets' => array(
                'total' => (int)$tickets_row['total'],
                'open' => (int)$tickets_row['open'],
                'closed' => (int)$tickets_row['closed']
            ),
            'created_at' => $this->formatDate($row['created']),
            'last_login_at' => $this->formatDate($row['lastlogin']),
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
