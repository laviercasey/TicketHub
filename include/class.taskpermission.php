<?php
class TaskPermission {

    public $id;
    public $row;

    static $permissionCache = array();
    static $staffInfoCache = array();
    static $boardInfoCache = array();

    public function __construct($id) {
        $this->id = 0;
        if ($id && ($info = TaskPermission::getInfoById($id))) {
            $this->row = $info;
            $this->id = $info['permission_id'];
        }
    }

    function getId() { return $this->id; }
    function getBoardId() { return $this->row['board_id']; }
    function getStaffId() { return $this->row['staff_id']; }
    function getDeptId() { return $this->row['dept_id']; }
    function getPermissionLevel() { return $this->row['permission_level']; }
    function getInfo() { return $this->row; }

    static function getInfoById($id) {
        $sql = 'SELECT * FROM ' . TASK_BOARD_PERMS_TABLE
             . ' WHERE permission_id=' . db_input($id);
        if (($res = db_query($sql)) && db_num_rows($res))
            return db_fetch_array($res);
        return null;
    }

    static function lookup($id) {
        $perm = new TaskPermission($id);
        return ($perm && $perm->getId()) ? $perm : null;
    }

    static function create($data, &$errors) {
        if (!$data['board_id']) {
            $errors['board_id'] = 'Не указана доска';
        }
        if (!$data['staff_id'] && !$data['dept_id']) {
            $errors['target'] = 'Укажите сотрудника или отдел';
        }
        if (!$data['permission_level'] || !in_array($data['permission_level'], array('view', 'edit', 'admin'))) {
            $errors['permission_level'] = 'Укажите уровень доступа';
        }

        if ($errors) return false;

        $chkSql = 'SELECT permission_id FROM ' . TASK_BOARD_PERMS_TABLE
                 . ' WHERE board_id=' . db_input($data['board_id']);
        if ($data['staff_id']) {
            $chkSql .= ' AND staff_id=' . db_input($data['staff_id']);
        } else {
            $chkSql .= ' AND dept_id=' . db_input($data['dept_id']);
        }
        if (($chkRes = db_query($chkSql)) && db_num_rows($chkRes)) {
            $existing = db_fetch_array($chkRes);
            $updSql = sprintf(
                "UPDATE %s SET permission_level=%s WHERE permission_id=%d",
                TASK_BOARD_PERMS_TABLE,
                db_input($data['permission_level']),
                db_input($existing['permission_id'])
            );
            db_query($updSql);
            return $existing['permission_id'];
        }

        $sql = sprintf(
            "INSERT INTO %s SET board_id=%d, staff_id=%s, dept_id=%s, permission_level=%s, created=NOW()",
            TASK_BOARD_PERMS_TABLE,
            db_input($data['board_id']),
            $data['staff_id'] ? db_input($data['staff_id']) : 'NULL',
            $data['dept_id'] ? db_input($data['dept_id']) : 'NULL',
            db_input($data['permission_level'])
        );

        if (!db_query($sql) || !($id = db_insert_id())) {
            $errors['err'] = 'Ошибка создания права доступа';
            return false;
        }

        return $id;
    }

    static function delete($id) {
        $sql = 'DELETE FROM ' . TASK_BOARD_PERMS_TABLE
             . ' WHERE permission_id=' . db_input($id);
        return db_query($sql) ? true : false;
    }

    static function deleteByBoardId($board_id) {
        $sql = 'DELETE FROM ' . TASK_BOARD_PERMS_TABLE
             . ' WHERE board_id=' . db_input($board_id);
        return db_query($sql) ? true : false;
    }

    static function getByBoard($board_id) {
        $perms = array();
        $sql = 'SELECT p.*, '
             . 'CONCAT(s.firstname," ",s.lastname) as staff_name, '
             . 'd.dept_name '
             . 'FROM ' . TASK_BOARD_PERMS_TABLE . ' p '
             . 'LEFT JOIN ' . TABLE_PREFIX . 'staff s ON s.staff_id=p.staff_id '
             . 'LEFT JOIN ' . TABLE_PREFIX . 'department d ON d.dept_id=p.dept_id '
             . 'WHERE p.board_id=' . db_input($board_id)
             . ' ORDER BY p.permission_id ASC';
        if (($res = db_query($sql)) && db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                $perms[] = $row;
            }
        }
        return $perms;
    }

    static function canView($board_id, $staff_id) {
        if (!$board_id || !$staff_id) return false;

        $cacheKey = 'view_' . $board_id . '_' . $staff_id;
        if (isset(TaskPermission::$permissionCache[$cacheKey])) {
            return TaskPermission::$permissionCache[$cacheKey];
        }

        if (!isset(TaskPermission::$staffInfoCache[$staff_id])) {
            $sql = 'SELECT isadmin, dept_id FROM ' . TABLE_PREFIX . 'staff'
                 . ' WHERE staff_id=' . db_input($staff_id);
            $staffRow = null;
            if (($res = db_query($sql)) && db_num_rows($res)) {
                $staffRow = db_fetch_array($res);
            }
            TaskPermission::$staffInfoCache[$staff_id] = $staffRow;
        }
        $staffRow = TaskPermission::$staffInfoCache[$staff_id];

        if (!$staffRow) {
            TaskPermission::$permissionCache[$cacheKey] = false;
            return false;
        }

        if ($staffRow['isadmin']) {
            TaskPermission::$permissionCache[$cacheKey] = true;
            return true;
        }

        if (!isset(TaskPermission::$boardInfoCache[$board_id])) {
            $bSql = 'SELECT board_type, dept_id, created_by FROM ' . TASK_BOARDS_TABLE
                  . ' WHERE board_id=' . db_input($board_id);
            $bRow = null;
            if (($bRes = db_query($bSql)) && db_num_rows($bRes)) {
                $bRow = db_fetch_array($bRes);
            }
            TaskPermission::$boardInfoCache[$board_id] = $bRow;
        }
        $bRow = TaskPermission::$boardInfoCache[$board_id];

        if (!$bRow) {
            TaskPermission::$permissionCache[$cacheKey] = false;
            return false;
        }

        if ($bRow['created_by'] == $staff_id) {
            TaskPermission::$permissionCache[$cacheKey] = true;
            return true;
        }

        $staffDeptId = $staffRow['dept_id'];
        $sql = 'SELECT permission_id FROM ' . TASK_BOARD_PERMS_TABLE
             . ' WHERE board_id=' . db_input($board_id)
             . ' AND (staff_id=' . db_input($staff_id);
        if ($staffDeptId) {
            $sql .= ' OR dept_id=' . db_input($staffDeptId);
        }
        $sql .= ')';

        if (($res = db_query($sql)) && db_num_rows($res)) {
            TaskPermission::$permissionCache[$cacheKey] = true;
            return true;
        }

        if ($bRow['board_type'] == 'department' && $bRow['dept_id'] && $bRow['dept_id'] == $staffDeptId) {
            TaskPermission::$permissionCache[$cacheKey] = true;
            return true;
        }

        TaskPermission::$permissionCache[$cacheKey] = false;
        return false;
    }

    static function canEdit($board_id, $staff_id) {
        if (!$board_id || !$staff_id) return false;

        $cacheKey = 'edit_' . $board_id . '_' . $staff_id;
        if (isset(TaskPermission::$permissionCache[$cacheKey])) {
            return TaskPermission::$permissionCache[$cacheKey];
        }

        if (!isset(TaskPermission::$staffInfoCache[$staff_id])) {
            $sql = 'SELECT isadmin, dept_id FROM ' . TABLE_PREFIX . 'staff'
                 . ' WHERE staff_id=' . db_input($staff_id);
            $staffRow = null;
            if (($res = db_query($sql)) && db_num_rows($res)) {
                $staffRow = db_fetch_array($res);
            }
            TaskPermission::$staffInfoCache[$staff_id] = $staffRow;
        }
        $staffRow = TaskPermission::$staffInfoCache[$staff_id];

        if (!$staffRow) {
            TaskPermission::$permissionCache[$cacheKey] = false;
            return false;
        }

        if ($staffRow['isadmin']) {
            TaskPermission::$permissionCache[$cacheKey] = true;
            return true;
        }

        if (!isset(TaskPermission::$boardInfoCache[$board_id])) {
            $bSql = 'SELECT board_type, dept_id, created_by FROM ' . TASK_BOARDS_TABLE
                  . ' WHERE board_id=' . db_input($board_id);
            $bRow = null;
            if (($bRes = db_query($bSql)) && db_num_rows($bRes)) {
                $bRow = db_fetch_array($bRes);
            }
            TaskPermission::$boardInfoCache[$board_id] = $bRow;
        }
        $bRow = TaskPermission::$boardInfoCache[$board_id];

        if ($bRow && $bRow['created_by'] == $staff_id) {
            TaskPermission::$permissionCache[$cacheKey] = true;
            return true;
        }

        $staffDeptId = $staffRow['dept_id'];
        $sql = 'SELECT permission_id FROM ' . TASK_BOARD_PERMS_TABLE
             . ' WHERE board_id=' . db_input($board_id)
             . " AND permission_level IN ('edit','admin')"
             . ' AND (staff_id=' . db_input($staff_id);
        if ($staffDeptId) {
            $sql .= ' OR dept_id=' . db_input($staffDeptId);
        }
        $sql .= ')';

        if (($res = db_query($sql)) && db_num_rows($res)) {
            TaskPermission::$permissionCache[$cacheKey] = true;
            return true;
        }

        TaskPermission::$permissionCache[$cacheKey] = false;
        return false;
    }

    static function canAdmin($board_id, $staff_id) {
        if (!$board_id || !$staff_id) return false;

        $cacheKey = 'admin_' . $board_id . '_' . $staff_id;
        if (isset(TaskPermission::$permissionCache[$cacheKey])) {
            return TaskPermission::$permissionCache[$cacheKey];
        }

        if (!isset(TaskPermission::$staffInfoCache[$staff_id])) {
            $sql = 'SELECT isadmin, dept_id FROM ' . TABLE_PREFIX . 'staff'
                 . ' WHERE staff_id=' . db_input($staff_id);
            $staffRow = null;
            if (($res = db_query($sql)) && db_num_rows($res)) {
                $staffRow = db_fetch_array($res);
            }
            TaskPermission::$staffInfoCache[$staff_id] = $staffRow;
        }
        $staffRow = TaskPermission::$staffInfoCache[$staff_id];

        if (!$staffRow) {
            TaskPermission::$permissionCache[$cacheKey] = false;
            return false;
        }

        if ($staffRow['isadmin']) {
            TaskPermission::$permissionCache[$cacheKey] = true;
            return true;
        }

        if (!isset(TaskPermission::$boardInfoCache[$board_id])) {
            $bSql = 'SELECT board_type, dept_id, created_by FROM ' . TASK_BOARDS_TABLE
                  . ' WHERE board_id=' . db_input($board_id);
            $bRow = null;
            if (($bRes = db_query($bSql)) && db_num_rows($bRes)) {
                $bRow = db_fetch_array($bRes);
            }
            TaskPermission::$boardInfoCache[$board_id] = $bRow;
        }
        $bRow = TaskPermission::$boardInfoCache[$board_id];

        if ($bRow && $bRow['created_by'] == $staff_id) {
            TaskPermission::$permissionCache[$cacheKey] = true;
            return true;
        }

        $staffDeptId = $staffRow['dept_id'];
        $sql = 'SELECT permission_id FROM ' . TASK_BOARD_PERMS_TABLE
             . ' WHERE board_id=' . db_input($board_id)
             . " AND permission_level='admin'"
             . ' AND (staff_id=' . db_input($staff_id);
        if ($staffDeptId) {
            $sql .= ' OR dept_id=' . db_input($staffDeptId);
        }
        $sql .= ')';

        if (($res = db_query($sql)) && db_num_rows($res)) {
            TaskPermission::$permissionCache[$cacheKey] = true;
            return true;
        }

        TaskPermission::$permissionCache[$cacheKey] = false;
        return false;
    }

    static function getStaffPermissionLevel($board_id, $staff_id) {
        $levels = array('admin' => 3, 'edit' => 2, 'view' => 1);
        $highest = null;
        $highestVal = 0;

        $sql = 'SELECT permission_level FROM ' . TASK_BOARD_PERMS_TABLE
             . ' WHERE board_id=' . db_input($board_id)
             . ' AND staff_id=' . db_input($staff_id);
        if (($res = db_query($sql)) && db_num_rows($res)) {
            $row = db_fetch_array($res);
            $val = isset($levels[$row['permission_level']]) ? $levels[$row['permission_level']] : 0;
            if ($val > $highestVal) {
                $highestVal = $val;
                $highest = $row['permission_level'];
            }
        }

        $dSql = 'SELECT dept_id FROM ' . TABLE_PREFIX . 'staff'
              . ' WHERE staff_id=' . db_input($staff_id);
        if (($dRes = db_query($dSql)) && db_num_rows($dRes)) {
            $dRow = db_fetch_array($dRes);
            if ($dRow['dept_id']) {
                $sql = 'SELECT permission_level FROM ' . TASK_BOARD_PERMS_TABLE
                     . ' WHERE board_id=' . db_input($board_id)
                     . ' AND dept_id=' . db_input($dRow['dept_id']);
                if (($res = db_query($sql)) && db_num_rows($res)) {
                    $row = db_fetch_array($res);
                    $val = isset($levels[$row['permission_level']]) ? $levels[$row['permission_level']] : 0;
                    if ($val > $highestVal) {
                        $highestVal = $val;
                        $highest = $row['permission_level'];
                    }
                }
            }
        }

        return $highest;
    }

    static function getLevelLabels() {
        return array(
            'view'  => 'Просмотр',
            'edit'  => 'Редактирование',
            'admin' => 'Администратор'
        );
    }
}
?>
