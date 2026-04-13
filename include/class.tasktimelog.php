<?php
class TaskTimeLog {

    public $id;
    public $row;

    public function __construct($id) {
        $this->id = 0;
        if ($id) {
            $sql = 'SELECT tl.*, CONCAT(s.firstname," ",s.lastname) as staff_name'
                 . ' FROM ' . TASK_TIME_LOGS_TABLE . ' tl'
                 . ' LEFT JOIN ' . TABLE_PREFIX . 'staff s ON s.staff_id=tl.staff_id'
                 . ' WHERE tl.log_id=' . db_input($id);
            if (($res = db_query($sql)) && db_num_rows($res)) {
                $this->row = db_fetch_array($res);
                $this->id = $this->row['log_id'];
            }
        }
    }

    function getId() { return $this->id; }
    function getTaskId() { return $this->row['task_id']; }
    function getStaffId() { return $this->row['staff_id']; }
    function getStaffName() { return $this->row['staff_name']; }
    function getTimeSpent() { return $this->row['time_spent']; }
    function getLogDate() { return $this->row['log_date']; }
    function getNotes() { return $this->row['notes']; }
    function getInfo() { return $this->row; }

    function getTimeFormatted() {
        return TaskTimeLog::formatMinutes($this->row['time_spent']);
    }

    static function lookup($id) {
        $log = new TaskTimeLog($id);
        return ($log && $log->getId()) ? $log : null;
    }

    static function create($data, &$errors) {
        if (!$data['task_id']) {
            $errors['err'] = 'Не указана задача';
        }
        if (!$data['staff_id']) {
            $errors['err'] = 'Не указан сотрудник';
        }
        if (!$data['time_spent'] || intval($data['time_spent']) <= 0) {
            $errors['time_spent'] = 'Укажите затраченное время';
        }
        if ($errors) return false;

        $log_date = !empty($data['log_date']) ? $data['log_date'] : date('Y-m-d H:i:s');
        if (strlen($log_date) <= 10) {
            $log_date .= ' ' . date('H:i:s');
        }

        $sql = 'INSERT INTO ' . TASK_TIME_LOGS_TABLE . ' SET'
             . ' task_id=' . db_input(intval($data['task_id']))
             . ', staff_id=' . db_input(intval($data['staff_id']))
             . ', time_spent=' . db_input(intval($data['time_spent']))
             . ', log_date=' . db_input($log_date)
             . ', notes=' . db_input($data['notes'] ? $data['notes'] : '');

        if (!db_query($sql) || !($id = db_insert_id())) {
            $errors['err'] = 'Ошибка записи времени';
            return false;
        }
        return $id;
    }

    static function deleteLog($id) {
        if (!$id) return false;
        $sql = 'DELETE FROM ' . TASK_TIME_LOGS_TABLE . ' WHERE log_id=' . db_input($id);
        return db_query($sql) ? true : false;
    }

    static function deleteByTaskId($task_id) {
        if (!$task_id) return false;
        $sql = 'DELETE FROM ' . TASK_TIME_LOGS_TABLE . ' WHERE task_id=' . db_input($task_id);
        return db_query($sql) ? true : false;
    }

    static function getByTaskId($task_id, $limit = 50) {
        $logs = array();
        if (!$task_id) return $logs;
        $sql = 'SELECT tl.*, CONCAT(s.firstname," ",s.lastname) as staff_name'
             . ' FROM ' . TASK_TIME_LOGS_TABLE . ' tl'
             . ' LEFT JOIN ' . TABLE_PREFIX . 'staff s ON s.staff_id=tl.staff_id'
             . ' WHERE tl.task_id=' . db_input($task_id)
             . ' ORDER BY tl.log_date DESC'
             . ' LIMIT ' . intval($limit);
        if (($res = db_query($sql)) && db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                $logs[] = $row;
            }
        }
        return $logs;
    }

    static function getTotalByTask($task_id) {
        if (!$task_id) return 0;
        $sql = 'SELECT SUM(time_spent) FROM ' . TASK_TIME_LOGS_TABLE
             . ' WHERE task_id=' . db_input($task_id);
        if (($res = db_query($sql)) && db_num_rows($res)) {
            list($total) = db_fetch_row($res);
            return intval($total);
        }
        return 0;
    }

    static function getTotalByStaffTask($staff_id, $task_id) {
        if (!$staff_id || !$task_id) return 0;
        $sql = 'SELECT SUM(time_spent) FROM ' . TASK_TIME_LOGS_TABLE
             . ' WHERE staff_id=' . db_input($staff_id)
             . ' AND task_id=' . db_input($task_id);
        if (($res = db_query($sql)) && db_num_rows($res)) {
            list($total) = db_fetch_row($res);
            return intval($total);
        }
        return 0;
    }

    static function getByStaffDateRange($staff_id, $start, $end) {
        $logs = array();
        if (!$staff_id) return $logs;
        $sql = 'SELECT tl.*, t.title as task_title, t.board_id,'
             . ' b.board_name, CONCAT(s.firstname," ",s.lastname) as staff_name'
             . ' FROM ' . TASK_TIME_LOGS_TABLE . ' tl'
             . ' LEFT JOIN ' . TASKS_TABLE . ' t ON t.task_id=tl.task_id'
             . ' LEFT JOIN ' . TASK_BOARDS_TABLE . ' b ON b.board_id=t.board_id'
             . ' LEFT JOIN ' . TABLE_PREFIX . 'staff s ON s.staff_id=tl.staff_id'
             . ' WHERE tl.staff_id=' . db_input($staff_id)
             . ' AND tl.log_date >= ' . db_input($start)
             . ' AND tl.log_date <= ' . db_input($end)
             . ' ORDER BY tl.log_date DESC';
        if (($res = db_query($sql)) && db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                $logs[] = $row;
            }
        }
        return $logs;
    }

    static function getTotalsByStaffDateRange($staff_id, $start, $end) {
        $total = 0;
        $sql = 'SELECT SUM(time_spent) FROM ' . TASK_TIME_LOGS_TABLE
             . ' WHERE staff_id=' . db_input($staff_id)
             . ' AND log_date >= ' . db_input($start)
             . ' AND log_date <= ' . db_input($end);
        if (($res = db_query($sql)) && db_num_rows($res)) {
            list($total) = db_fetch_row($res);
        }
        return intval($total);
    }

    static function formatMinutes($mins) {
        $mins = intval($mins);
        if ($mins <= 0) return '0м';
        $h = floor($mins / 60);
        $m = $mins % 60;
        if ($h > 0 && $m > 0) return $h . 'ч ' . $m . 'м';
        if ($h > 0) return $h . 'ч';
        return $m . 'м';
    }
}
?>
