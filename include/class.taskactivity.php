<?php
class TaskActivity {

    public $id;
    public $row;

    public function __construct($id) {
        $this->id = 0;
        if ($id && ($info = $this->getInfoById($id))) {
            $this->row = $info;
            $this->id = $info['activity_id'];
        }
    }

    function getId() { return $this->id; }
    function getTaskId() { return $this->row['task_id']; }
    function getStaffId() { return $this->row['staff_id']; }
    function getType() { return $this->row['activity_type']; }
    function getData() { return $this->row['activity_data']; }
    function getCreated() { return $this->row['created']; }
    function getStaffName() { return $this->row['staff_name']; }
    function getInfo() { return $this->row; }

    function getTypeLabel() {
        $labels = array(
            'created' => 'создал(а) задачу',
            'updated' => 'обновил(а) задачу',
            'assigned' => 'назначил(а)',
            'unassigned' => 'снял(а) назначение',
            'commented' => 'оставил(а) комментарий',
            'status_changed' => 'изменил(а) статус',
            'moved' => 'переместил(а) задачу',
            'deleted' => 'удалил(а)',
            'completed' => 'завершил(а) задачу',
            'automation' => 'автоматизация',
            'notification' => 'уведомление'
        );
        return isset($labels[$this->row['activity_type']]) ? $labels[$this->row['activity_type']] : $this->row['activity_type'];
    }

    function getTypeIcon() {
        $icons = array(
            'created' => 'plus-circle text-success',
            'updated' => 'pencil text-info',
            'assigned' => 'user-plus text-primary',
            'unassigned' => 'user-times text-warning',
            'commented' => 'comment text-info',
            'status_changed' => 'exchange text-primary',
            'moved' => 'arrows text-muted',
            'deleted' => 'trash text-danger',
            'completed' => 'check-circle text-success',
            'automation' => 'cog text-info',
            'notification' => 'bell text-warning'
        );
        return isset($icons[$this->row['activity_type']]) ? $icons[$this->row['activity_type']] : 'circle-o';
    }


    static function getInfoById($id) {
        $sql = 'SELECT a.*, CONCAT(s.firstname," ",s.lastname) as staff_name'
             . ' FROM ' . TASK_ACTIVITY_LOG_TABLE . ' a'
             . ' LEFT JOIN ' . TABLE_PREFIX . 'staff s ON s.staff_id=a.staff_id'
             . ' WHERE a.activity_id=' . db_input($id);
        if (($res = db_query($sql)) && db_num_rows($res))
            return db_fetch_array($res);
        return null;
    }

    static function getByTaskId($task_id, $limit = 50) {
        $activities = array();
        $sql = 'SELECT a.*, CONCAT(s.firstname," ",s.lastname) as staff_name'
             . ' FROM ' . TASK_ACTIVITY_LOG_TABLE . ' a'
             . ' LEFT JOIN ' . TABLE_PREFIX . 'staff s ON s.staff_id=a.staff_id'
             . ' WHERE a.task_id=' . db_input($task_id)
             . ' ORDER BY a.created DESC';
        if ($limit > 0) $sql .= ' LIMIT ' . intval($limit);

        if (($res = db_query($sql)) && db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                $activities[] = $row;
            }
        }
        return $activities;
    }

    static function log($task_id, $staff_id, $type, $data = '') {
        $valid_types = array('created', 'updated', 'assigned', 'unassigned',
                            'commented', 'status_changed', 'moved', 'deleted', 'completed',
                            'automation', 'notification');
        if (!in_array($type, $valid_types)) return false;

        $sql = sprintf(
            "INSERT INTO %s SET
                task_id=%d,
                staff_id=%d,
                activity_type=%s,
                activity_data=%s,
                created=NOW()",
            TASK_ACTIVITY_LOG_TABLE,
            db_input($task_id),
            db_input($staff_id),
            db_input($type),
            db_input($data ? Format::striptags(mb_substr($data, 0, 500)) : '')
        );

        return db_query($sql) ? true : false;
    }

    static function deleteByTaskId($task_id) {
        $sql = 'DELETE FROM ' . TASK_ACTIVITY_LOG_TABLE . ' WHERE task_id=' . db_input($task_id);
        return db_query($sql) ? true : false;
    }
}
?>
