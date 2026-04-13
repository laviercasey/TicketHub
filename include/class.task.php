<?php
require_once(dirname(__FILE__).'/class.taskactivity.php');
require_once(dirname(__FILE__).'/class.taskattachment.php');
require_once(dirname(__FILE__).'/class.taskautomation.php');

class Task {

    public $id;
    public $row;

    public function __construct($id) {
        $this->id = 0;
        if ($id && ($info = $this->getInfoById($id))) {
            $this->row = $info;
            $this->id = $info['task_id'];
        }
    }

    function getId() { return $this->id; }
    function getTitle() { return $this->row['title']; }
    function getDescription() { return $this->row['description']; }
    function getBoardId() { return $this->row['board_id']; }
    function getListId() { return $this->row['list_id']; }
    function getParentTaskId() { return $this->row['parent_task_id']; }
    function getTicketId() { return $this->row['ticket_id']; }
    function getTaskType() { return $this->row['task_type']; }
    function getPriority() { return $this->row['priority']; }
    function getStatus() { return $this->row['status']; }
    function getStartDate() { return $this->row['start_date']; }
    function getEndDate() { return $this->row['end_date']; }
    function getDeadline() { return $this->row['deadline']; }
    function getTimeEstimate() { return $this->row['time_estimate']; }
    function getPosition() { return $this->row['position']; }
    function getCreatedBy() { return $this->row['created_by']; }
    function getCreated() { return $this->row['created']; }
    function getUpdated() { return $this->row['updated']; }
    function getCompletedDate() { return $this->row['completed_date']; }
    function getInfo() { return $this->row; }

    function getBoardName() { return $this->row['board_name']; }
    function getListName() { return $this->row['list_name']; }
    function getCreatorName() { return $this->row['creator_name']; }

    function isOpen() { return $this->row['status'] == 'open'; }
    function isInProgress() { return $this->row['status'] == 'in_progress'; }
    function isBlocked() { return $this->row['status'] == 'blocked'; }
    function isCompleted() { return $this->row['status'] == 'completed'; }
    function isCancelled() { return $this->row['status'] == 'cancelled'; }
    function isSubtask() { return $this->row['parent_task_id'] > 0; }
    function isArchived() { return $this->row['is_archived'] == 1; }

    function getPriorityLabel() {
        $labels = array(
            'low' => 'Низкий',
            'normal' => 'Обычный',
            'high' => 'Высокий',
            'urgent' => 'Срочный'
        );
        return isset($labels[$this->row['priority']]) ? $labels[$this->row['priority']] : $this->row['priority'];
    }

    function getStatusLabel() {
        $labels = array(
            'open' => 'Открыта',
            'in_progress' => 'В работе',
            'review' => 'На проверке',
            'blocked' => 'Заблокирована',
            'completed' => 'Завершена',
            'cancelled' => 'Отменена'
        );
        return isset($labels[$this->row['status']]) ? $labels[$this->row['status']] : $this->row['status'];
    }

    function getTaskTypeLabel() {
        $labels = array(
            'action' => 'Действие',
            'meeting' => 'Встреча',
            'call' => 'Звонок',
            'email' => 'Email',
            'other' => 'Другое'
        );
        return isset($labels[$this->row['task_type']]) ? $labels[$this->row['task_type']] : $this->row['task_type'];
    }

    function getPriorityClass() {
        $classes = array(
            'low' => 'default',
            'normal' => 'info',
            'high' => 'warning',
            'urgent' => 'danger'
        );
        return isset($classes[$this->row['priority']]) ? $classes[$this->row['priority']] : 'default';
    }

    function getStatusClass() {
        $classes = array(
            'open' => 'default',
            'in_progress' => 'primary',
            'review' => 'warning',
            'blocked' => 'danger',
            'completed' => 'success',
            'cancelled' => 'default'
        );
        return isset($classes[$this->row['status']]) ? $classes[$this->row['status']] : 'default';
    }

    function isOverdue() {
        if (!$this->row['deadline']) return false;
        if ($this->isCompleted() || $this->isCancelled()) return false;
        return strtotime($this->row['deadline']) < time();
    }

    function getTimeEstimateFormatted() {
        $mins = intval($this->row['time_estimate']);
        if ($mins <= 0) return '';
        $h = floor($mins / 60);
        $m = $mins % 60;
        if ($h > 0 && $m > 0) return $h . 'ч ' . $m . 'м';
        if ($h > 0) return $h . 'ч';
        return $m . 'м';
    }

    function getAssignees($role = null) {
        $assignees = array();
        $sql = 'SELECT a.*, s.firstname, s.lastname'
             . ' FROM ' . TASK_ASSIGNEES_TABLE . ' a'
             . ' LEFT JOIN ' . TABLE_PREFIX . 'staff s ON s.staff_id=a.staff_id'
             . ' WHERE a.task_id=' . db_input($this->id);
        if ($role) {
            $sql .= ' AND a.role=' . db_input($role);
        }
        $sql .= ' ORDER BY a.assigned_date ASC';

        if (($res = db_query($sql)) && db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                $row['name'] = trim($row['firstname'] . ' ' . $row['lastname']);
                $assignees[] = $row;
            }
        }
        return $assignees;
    }

    function getAssigneeNames() {
        $assignees = $this->getAssignees('assignee');
        $names = array();
        foreach ($assignees as $a) {
            $names[] = $a['name'];
        }
        return implode(', ', $names);
    }

    function getSubtasks() {
        $subtasks = array();
        $sql = 'SELECT * FROM ' . TASKS_TABLE
             . ' WHERE parent_task_id=' . db_input($this->id)
             . ' ORDER BY position ASC, task_id ASC';
        if (($res = db_query($sql)) && db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                $subtasks[] = $row;
            }
        }
        return $subtasks;
    }

    function getSubtaskCount() {
        $sql = 'SELECT COUNT(*) FROM ' . TASKS_TABLE . ' WHERE parent_task_id=' . db_input($this->id);
        if (($res = db_query($sql)) && db_num_rows($res)) {
            list($count) = db_fetch_row($res);
            return $count;
        }
        return 0;
    }

    function getCompletedSubtaskCount() {
        $sql = 'SELECT COUNT(*) FROM ' . TASKS_TABLE
             . ' WHERE parent_task_id=' . db_input($this->id)
             . " AND status='completed'";
        if (($res = db_query($sql)) && db_num_rows($res)) {
            list($count) = db_fetch_row($res);
            return $count;
        }
        return 0;
    }

    static function getInfoById($id) {
        $sql = 'SELECT t.*, b.board_name, l.list_name,'
             . ' CONCAT(s.firstname, " ", s.lastname) as creator_name'
             . ' FROM ' . TASKS_TABLE . ' t'
             . ' LEFT JOIN ' . TASK_BOARDS_TABLE . ' b ON b.board_id=t.board_id'
             . ' LEFT JOIN ' . TASK_LISTS_TABLE . ' l ON l.list_id=t.list_id'
             . ' LEFT JOIN ' . TABLE_PREFIX . 'staff s ON s.staff_id=t.created_by'
             . ' WHERE t.task_id=' . db_input($id);
        if (($res = db_query($sql)) && db_num_rows($res))
            return db_fetch_array($res);
        return null;
    }

    static function lookup($id) {
        $task = new Task($id);
        return ($task && $task->getId()) ? $task : null;
    }

    static function create($data, &$errors) {

        if (!$data['title']) {
            $errors['title'] = 'Название задачи обязательно';
        }

        if (!$data['board_id']) {
            $errors['board_id'] = 'Выберите доску';
        }

        if (!$data['created_by']) {
            $errors['err'] = 'Ошибка идентификации пользователя';
        }

        $valid_types = array('action', 'meeting', 'call', 'email', 'other');
        $valid_priorities = array('low', 'normal', 'high', 'urgent');
        $valid_statuses = array('open', 'in_progress', 'blocked', 'review', 'completed', 'cancelled');

        $task_type = (isset($data['task_type']) && in_array($data['task_type'], $valid_types)) ? $data['task_type'] : 'action';
        $priority = (isset($data['priority']) && in_array($data['priority'], $valid_priorities)) ? $data['priority'] : 'normal';
        $status = (isset($data['status']) && in_array($data['status'], $valid_statuses)) ? $data['status'] : 'open';

        if ($errors) return false;

        $position = 0;
        if ($data['list_id']) {
            $sql = 'SELECT MAX(position) FROM ' . TASKS_TABLE . ' WHERE list_id=' . db_input($data['list_id']);
            if (($res = db_query($sql)) && db_num_rows($res)) {
                list($max) = db_fetch_row($res);
                $position = intval($max) + 1;
            }
        }

        $sql = sprintf(
            "INSERT INTO %s SET
                board_id=%d,
                list_id=%s,
                parent_task_id=%s,
                ticket_id=%s,
                title=%s,
                description=%s,
                task_type=%s,
                priority=%s,
                status=%s,
                start_date=%s,
                end_date=%s,
                deadline=%s,
                time_estimate=%d,
                position=%d,
                created_by=%d,
                created=NOW()",
            TASKS_TABLE,
            db_input($data['board_id']),
            $data['list_id'] ? db_input($data['list_id']) : 'NULL',
            $data['parent_task_id'] ? db_input($data['parent_task_id']) : 'NULL',
            $data['ticket_id'] ? db_input($data['ticket_id']) : 'NULL',
            db_input(Format::striptags($data['title'])),
            db_input($data['description'] ? $data['description'] : ''),
            db_input($task_type),
            db_input($priority),
            db_input($status),
            $data['start_date'] ? db_input($data['start_date']) : 'NULL',
            $data['end_date'] ? db_input($data['end_date']) : 'NULL',
            $data['deadline'] ? db_input($data['deadline']) : 'NULL',
            db_input($data['time_estimate'] ? $data['time_estimate'] : 0),
            $position,
            db_input($data['created_by'])
        );

        if (!db_query($sql) || !($id = db_insert_id())) {
            $errors['err'] = 'Ошибка создания задачи. Попробуйте снова.';
            return false;
        }

        if (isset($data['assignees']) && is_array($data['assignees'])) {
            foreach ($data['assignees'] as $staff_id) {
                Task::addAssignee($id, $staff_id, 'assignee');
            }
        }

        TaskActivity::log($id, $data['created_by'], 'created', $data['title']);

        TaskAutomation::fireEvent($id, 'task_created', array());

        return $id;
    }

    static function update($id, $data, &$errors) {

        if (!$id) {
            $errors['err'] = 'Отсутствует ID задачи';
            return false;
        }

        if (!$data['title']) {
            $errors['title'] = 'Название задачи обязательно';
        }

        if ($errors) return false;

        $valid_types = array('action', 'meeting', 'call', 'email', 'other');
        $valid_priorities = array('low', 'normal', 'high', 'urgent');
        $valid_statuses = array('open', 'in_progress', 'blocked', 'review', 'completed', 'cancelled');

        $task_type = (isset($data['task_type']) && in_array($data['task_type'], $valid_types)) ? $data['task_type'] : 'action';
        $priority = (isset($data['priority']) && in_array($data['priority'], $valid_priorities)) ? $data['priority'] : 'normal';
        $status = (isset($data['status']) && in_array($data['status'], $valid_statuses)) ? $data['status'] : 'open';

        $completed_sql = '';
        $old_task = new Task($id);
        if ($old_task->getId()) {
            if ($status == 'completed' && $old_task->getStatus() != 'completed') {
                $completed_sql = ', completed_date=NOW()';
            } elseif ($status != 'completed' && $old_task->getStatus() == 'completed') {
                $completed_sql = ', completed_date=NULL';
            }
        }

        $sql = sprintf(
            "UPDATE %s SET
                board_id=%d,
                list_id=%s,
                parent_task_id=%s,
                ticket_id=%s,
                title=%s,
                description=%s,
                task_type=%s,
                priority=%s,
                status=%s,
                start_date=%s,
                end_date=%s,
                deadline=%s,
                time_estimate=%d,
                updated=NOW()
                %s
            WHERE task_id=%d",
            TASKS_TABLE,
            db_input($data['board_id'] ? $data['board_id'] : $old_task->getBoardId()),
            $data['list_id'] ? db_input($data['list_id']) : 'NULL',
            $data['parent_task_id'] ? db_input($data['parent_task_id']) : 'NULL',
            $data['ticket_id'] ? db_input($data['ticket_id']) : 'NULL',
            db_input(Format::striptags($data['title'])),
            db_input($data['description'] ? $data['description'] : ''),
            db_input($task_type),
            db_input($priority),
            db_input($status),
            $data['start_date'] ? db_input($data['start_date']) : 'NULL',
            $data['end_date'] ? db_input($data['end_date']) : 'NULL',
            $data['deadline'] ? db_input($data['deadline']) : 'NULL',
            db_input($data['time_estimate'] ? $data['time_estimate'] : 0),
            $completed_sql,
            db_input($id)
        );

        if (!db_query($sql)) {
            $errors['err'] = 'Ошибка обновления задачи.';
            return false;
        }

        if (isset($data['assignees']) && is_array($data['assignees'])) {
            $dsql = 'DELETE FROM ' . TASK_ASSIGNEES_TABLE
                  . ' WHERE task_id=' . db_input($id)
                  . " AND role='assignee'";
            db_query($dsql);

            foreach ($data['assignees'] as $staff_id) {
                Task::addAssignee($id, $staff_id, 'assignee');
            }
        }

        $staff_id = $data['created_by'] ? $data['created_by'] : ($old_task ? $old_task->getCreatedBy() : 0);
        if ($old_task && $old_task->getId()) {
            if ($status != $old_task->getStatus()) {
                $statusLabels = Task::getStatusLabels();
                $from = isset($statusLabels[$old_task->getStatus()]) ? $statusLabels[$old_task->getStatus()] : $old_task->getStatus();
                $to = isset($statusLabels[$status]) ? $statusLabels[$status] : $status;
                TaskActivity::log($id, $staff_id, 'status_changed', $from . ' -> ' . $to);
                if ($status == 'completed') {
                    TaskActivity::log($id, $staff_id, 'completed', '');
                }
                TaskAutomation::fireEvent($id, 'status_changed', array('from_status' => $old_task->getStatus(), 'to_status' => $status));
                if ($status == 'completed') {
                    TaskAutomation::fireEvent($id, 'task_completed', array());
                }
            } else {
                TaskActivity::log($id, $staff_id, 'updated', '');
            }
            if ($priority != $old_task->getPriority()) {
                TaskAutomation::fireEvent($id, 'priority_changed', array('from_priority' => $old_task->getPriority(), 'to_priority' => $priority));
            }
        }

        return true;
    }

    static function delete($id) {
        $task = new Task($id);
        if (!$task->getId()) return false;

        $sql = 'DELETE FROM ' . TASKS_TABLE . ' WHERE parent_task_id=' . db_input($id);
        db_query($sql);

        $sql = 'DELETE FROM ' . TASK_ASSIGNEES_TABLE . ' WHERE task_id=' . db_input($id);
        db_query($sql);

        $sql = 'DELETE FROM ' . TASK_COMMENTS_TABLE . ' WHERE task_id=' . db_input($id);
        db_query($sql);

        TaskAttachment::deleteByTaskId($id);

        TaskActivity::deleteByTaskId($id);

        $sql = 'DELETE FROM ' . TASK_TAG_ASSOC_TABLE . ' WHERE task_id=' . db_input($id);
        db_query($sql);
        $sql = 'DELETE FROM ' . TASK_CUSTOM_VALUES_TABLE . ' WHERE task_id=' . db_input($id);
        db_query($sql);

        $sql = 'DELETE FROM ' . TASK_TIME_LOGS_TABLE . ' WHERE task_id=' . db_input($id);
        db_query($sql);

        $sql = 'DELETE FROM ' . TASKS_TABLE . ' WHERE task_id=' . db_input($id);
        return db_query($sql) ? true : false;
    }

    static function addAssignee($task_id, $staff_id, $role = 'assignee') {
        if (!$task_id || !$staff_id) return false;
        $valid_roles = array('assignee', 'watcher', 'co-author');
        if (!in_array($role, $valid_roles)) $role = 'assignee';

        $sql = sprintf(
            "INSERT IGNORE INTO %s SET task_id=%d, staff_id=%d, role=%s, assigned_date=NOW()",
            TASK_ASSIGNEES_TABLE,
            db_input($task_id),
            db_input($staff_id),
            db_input($role)
        );
        return db_query($sql) ? true : false;
    }

    static function removeAssignee($task_id, $staff_id, $role = 'assignee') {
        $sql = sprintf(
            "DELETE FROM %s WHERE task_id=%d AND staff_id=%d AND role=%s",
            TASK_ASSIGNEES_TABLE,
            db_input($task_id),
            db_input($staff_id),
            db_input($role)
        );
        return db_query($sql) ? true : false;
    }

    static function moveToList($task_id, $list_id, $position = null) {
        if (!$task_id) return false;

        if ($position === null) {
            $sql = 'SELECT MAX(position) FROM ' . TASKS_TABLE . ' WHERE list_id=' . db_input($list_id);
            $max = 0;
            if (($res = db_query($sql)) && db_num_rows($res)) {
                list($max) = db_fetch_row($res);
            }
            $position = intval($max) + 1;
        }

        $newStatus = null;
        if ($list_id) {
            $statusSql = 'SELECT status FROM ' . TASK_LISTS_TABLE . ' WHERE list_id=' . db_input($list_id);
            $statusRes = db_query($statusSql);
            if ($statusRes && db_num_rows($statusRes)) {
                $statusRow = db_fetch_array($statusRes);
                $newStatus = $statusRow['status'];
            }
        }

        if ($newStatus) {
            $sql = sprintf(
                "UPDATE %s SET list_id=%s, position=%d, status=%s, updated=NOW() WHERE task_id=%d",
                TASKS_TABLE,
                $list_id ? db_input($list_id) : 'NULL',
                db_input($position),
                db_input($newStatus),
                db_input($task_id)
            );
        } else {
            $sql = sprintf(
                "UPDATE %s SET list_id=%s, position=%d, updated=NOW() WHERE task_id=%d",
                TASKS_TABLE,
                $list_id ? db_input($list_id) : 'NULL',
                db_input($position),
                db_input($task_id)
            );
        }

        return db_query($sql) ? true : false;
    }

    static function updateStatus($task_id, $status, $staff_id = 0) {
        $valid = array('open', 'in_progress', 'blocked', 'completed', 'cancelled');
        if (!in_array($status, $valid)) return false;

        $old_task = new Task($task_id);
        $old_status = $old_task->getId() ? $old_task->getStatus() : '';

        $completed_sql = '';
        if ($status == 'completed') {
            $completed_sql = ', completed_date=NOW()';
        } else {
            $completed_sql = ', completed_date=NULL';
        }

        $sql = sprintf(
            "UPDATE %s SET status=%s, updated=NOW() %s WHERE task_id=%d",
            TASKS_TABLE,
            db_input($status),
            $completed_sql,
            db_input($task_id)
        );

        if (!db_query($sql)) return false;

        if ($old_task->getId() && $old_status != $status) {
            $board_id = $old_task->getBoardId();
            if ($board_id) {
                $labels = Task::getStatusLabels();
                $statusName = isset($labels[$status]) ? $labels[$status] : '';
                if ($statusName) {
                    $lsql = 'SELECT list_id FROM ' . TASK_LISTS_TABLE
                          . ' WHERE board_id=' . db_input($board_id)
                          . ' AND is_archived=0'
                          . ' AND LOWER(list_name)=LOWER(' . db_input($statusName) . ')'
                          . ' LIMIT 1';
                    if (($lres = db_query($lsql)) && db_num_rows($lres)) {
                        $lrow = db_fetch_array($lres);
                        $usql = sprintf("UPDATE %s SET list_id=%d WHERE task_id=%d",
                            TASKS_TABLE, intval($lrow['list_id']), db_input($task_id));
                        db_query($usql);
                    }
                }
            }
        }

        if ($staff_id && $old_status != $status) {
            $labels = Task::getStatusLabels();
            $from = isset($labels[$old_status]) ? $labels[$old_status] : $old_status;
            $to = isset($labels[$status]) ? $labels[$status] : $status;
            TaskActivity::log($task_id, $staff_id, 'status_changed', $from . ' -> ' . $to);
        }

        if ($old_status != $status) {
            TaskAutomation::fireEvent($task_id, 'status_changed', array('from_status' => $old_status, 'to_status' => $status));
            if ($status == 'completed') {
                TaskAutomation::fireEvent($task_id, 'task_completed', array());
            }
        }

        return true;
    }

    static function archive($task_id, $staff_id) {
        global $ost;

        $task_id = intval($task_id);
        $staff_id = intval($staff_id);

        if (!$task_id || !$staff_id)
            return false;

        $sql = 'UPDATE '.TASKS_TABLE.' SET is_archived=1 WHERE task_id='.db_input($task_id);
        if (!db_query($sql) || !db_affected_rows())
            return false;

        require_once(INCLUDE_DIR.'class.taskactivity.php');
        TaskActivity::log($task_id, $staff_id, 'updated', 'Задача архивирована');

        require_once(INCLUDE_DIR.'class.taskautomation.php');
        TaskAutomation::fireEvent($task_id, 'task_archived', array());

        return true;
    }

    static function unarchive($task_id, $staff_id) {
        global $ost;

        $task_id = intval($task_id);
        $staff_id = intval($staff_id);

        if (!$task_id || !$staff_id)
            return false;

        $sql = 'UPDATE '.TASKS_TABLE.' SET is_archived=0 WHERE task_id='.db_input($task_id);
        if (!db_query($sql) || !db_affected_rows())
            return false;

        require_once(INCLUDE_DIR.'class.taskactivity.php');
        TaskActivity::log($task_id, $staff_id, 'updated', 'Задача разархивирована');

        require_once(INCLUDE_DIR.'class.taskautomation.php');
        TaskAutomation::fireEvent($task_id, 'task_unarchived', array());

        return true;
    }

    static function updatePriority($task_id, $priority, $staff_id) {
        global $ost;

        $task_id = intval($task_id);
        $staff_id = intval($staff_id);

        if (!$task_id || !$staff_id || !$priority)
            return false;

        $validPriorities = array('low', 'normal', 'high', 'urgent');
        if (!in_array($priority, $validPriorities))
            return false;

        $sql = 'SELECT priority FROM '.TASKS_TABLE.' WHERE task_id='.db_input($task_id);
        $res = db_query($sql);
        if (!$res || !db_num_rows($res))
            return false;

        $row = db_fetch_array($res);
        $old_priority = $row['priority'];

        if ($old_priority == $priority)
            return true;

        $sql = 'UPDATE '.TASKS_TABLE.' SET priority='.db_input($priority).', updated=NOW() WHERE task_id='.db_input($task_id);
        if (!db_query($sql))
            return false;

        $labels = Task::getPriorityLabels();
        $old_label = isset($labels[$old_priority]) ? $labels[$old_priority] : $old_priority;
        $new_label = isset($labels[$priority]) ? $labels[$priority] : $priority;

        require_once(INCLUDE_DIR.'class.taskactivity.php');
        TaskActivity::log($task_id, $staff_id, 'updated', 'Приоритет: '.$old_label.' -> '.$new_label);

        require_once(INCLUDE_DIR.'class.taskautomation.php');
        TaskAutomation::fireEvent($task_id, 'priority_changed', array('from_priority' => $old_priority, 'to_priority' => $priority));

        return true;
    }

    static function getPriorityLabels() {
        return array(
            'low' => 'Низкий',
            'normal' => 'Обычный',
            'high' => 'Высокий',
            'urgent' => 'Срочный'
        );
    }

    static function getStatusLabels() {
        return array(
            'open' => 'Открыта',
            'in_progress' => 'В работе',
            'review' => 'На проверке',
            'blocked' => 'Заблокирована',
            'completed' => 'Завершена',
            'cancelled' => 'Отменена'
        );
    }

    static function getTaskTypeLabels() {
        return array(
            'action' => 'Действие',
            'meeting' => 'Встреча',
            'call' => 'Звонок',
            'email' => 'Email',
            'other' => 'Другое'
        );
    }
}
?>
