<?php
class TaskAutomation {

    public $id;
    public $row;

    public function __construct($id) {
        $this->id = 0;
        if ($id) {
            $sql = 'SELECT * FROM ' . TASK_AUTOMATION_TABLE . ' WHERE rule_id=' . db_input($id);
            if (($res = db_query($sql)) && db_num_rows($res)) {
                $this->row = db_fetch_array($res);
                $this->id = $this->row['rule_id'];
            }
        }
    }

    function getId() { return $this->id; }
    function getName() { return $this->row['rule_name']; }
    function getBoardId() { return $this->row['board_id']; }
    function getTriggerType() { return $this->row['trigger_type']; }
    function getActionType() { return $this->row['action_type']; }
    function isEnabled() { return $this->row['is_enabled'] ? true : false; }
    function getCreated() { return $this->row['created']; }
    function getUpdated() { return $this->row['updated']; }
    function getInfo() { return $this->row; }

    function getTriggerConfig() {
        $raw = $this->row['trigger_config'];
        if (!$raw) return array();
        $val = self::safeDecodeConfig($raw);
        return is_array($val) ? $val : array();
    }

    function getActionConfig() {
        $raw = $this->row['action_config'];
        if (!$raw) return array();
        $val = self::safeDecodeConfig($raw);
        return is_array($val) ? $val : array();
    }

    private static function safeDecodeConfig($raw) {
        if (!$raw) return array();
        $val = json_decode($raw, true);
        return is_array($val) ? $val : array();
    }

    static function lookup($id) {
        $rule = new TaskAutomation($id);
        return ($rule && $rule->getId()) ? $rule : null;
    }

    static function getTriggerLabels() {
        return array(
            'status_changed'   => 'Изменён статус',
            'priority_changed' => 'Изменён приоритет',
            'assignee_changed' => 'Изменён исполнитель',
            'deadline_passed'  => 'Просрочен дедлайн',
            'task_created'     => 'Задача создана',
            'task_completed'   => 'Задача завершена'
        );
    }

    static function getActionLabels() {
        return array(
            'change_status'    => 'Изменить статус',
            'change_priority'  => 'Изменить приоритет',
            'assign_to'        => 'Назначить на',
            'send_notification'=> 'Отправить уведомление',
            'move_to_list'     => 'Переместить в список',
            'add_tag'          => 'Добавить тег'
        );
    }

    static function create($data, &$errors) {
        if (!$data['rule_name']) {
            $errors['rule_name'] = 'Название правила обязательно';
        }
        if (!$data['board_id']) {
            $errors['board_id'] = 'Доска обязательна';
        }

        $valid_triggers = array('status_changed', 'priority_changed', 'assignee_changed', 'deadline_passed', 'task_created', 'task_completed');
        $valid_actions = array('change_status', 'change_priority', 'assign_to', 'send_notification', 'move_to_list', 'add_tag');

        if (!$data['trigger_type'] || !in_array($data['trigger_type'], $valid_triggers)) {
            $errors['trigger_type'] = 'Выберите триггер';
        }
        if (!$data['action_type'] || !in_array($data['action_type'], $valid_actions)) {
            $errors['action_type'] = 'Выберите действие';
        }

        if ($errors) return false;

        $trigger_config = isset($data['trigger_config']) && is_array($data['trigger_config']) ? $data['trigger_config'] : array();
        $action_config = isset($data['action_config']) && is_array($data['action_config']) ? $data['action_config'] : array();

        $sql = sprintf(
            "INSERT INTO %s SET
                board_id=%d,
                rule_name=%s,
                trigger_type=%s,
                trigger_config=%s,
                action_type=%s,
                action_config=%s,
                is_enabled=%d,
                created=NOW(),
                updated=NOW()",
            TASK_AUTOMATION_TABLE,
            db_input($data['board_id']),
            db_input(Format::striptags($data['rule_name'])),
            db_input($data['trigger_type']),
            db_input(json_encode($trigger_config)),
            db_input($data['action_type']),
            db_input(json_encode($action_config)),
            isset($data['is_enabled']) ? intval($data['is_enabled']) : 1
        );

        if (!db_query($sql) || !($id = db_insert_id())) {
            $errors['err'] = 'Ошибка создания правила автоматизации';
            return false;
        }

        return $id;
    }

    static function update($id, $data, &$errors) {
        if (!$id) {
            $errors['err'] = 'Отсутствует ID правила';
            return false;
        }
        if (!$data['rule_name']) {
            $errors['rule_name'] = 'Название правила обязательно';
        }

        $valid_triggers = array('status_changed', 'priority_changed', 'assignee_changed', 'deadline_passed', 'task_created', 'task_completed');
        $valid_actions = array('change_status', 'change_priority', 'assign_to', 'send_notification', 'move_to_list', 'add_tag');

        if (!$data['trigger_type'] || !in_array($data['trigger_type'], $valid_triggers)) {
            $errors['trigger_type'] = 'Выберите триггер';
        }
        if (!$data['action_type'] || !in_array($data['action_type'], $valid_actions)) {
            $errors['action_type'] = 'Выберите действие';
        }

        if ($errors) return false;

        $trigger_config = isset($data['trigger_config']) && is_array($data['trigger_config']) ? $data['trigger_config'] : array();
        $action_config = isset($data['action_config']) && is_array($data['action_config']) ? $data['action_config'] : array();

        $sql = sprintf(
            "UPDATE %s SET
                rule_name=%s,
                trigger_type=%s,
                trigger_config=%s,
                action_type=%s,
                action_config=%s,
                updated=NOW()
            WHERE rule_id=%d",
            TASK_AUTOMATION_TABLE,
            db_input(Format::striptags($data['rule_name'])),
            db_input($data['trigger_type']),
            db_input(json_encode($trigger_config)),
            db_input($data['action_type']),
            db_input(json_encode($action_config)),
            db_input($id)
        );

        return db_query($sql) ? true : false;
    }

    static function delete($id) {
        if (!$id) return false;
        $sql = 'DELETE FROM ' . TASK_AUTOMATION_TABLE . ' WHERE rule_id=' . db_input($id);
        return db_query($sql) ? true : false;
    }

    static function deleteByBoardId($board_id) {
        if (!$board_id) return false;
        $sql = 'DELETE FROM ' . TASK_AUTOMATION_TABLE . ' WHERE board_id=' . db_input($board_id);
        return db_query($sql) ? true : false;
    }

    static function toggleEnabled($id) {
        if (!$id) return false;
        $sql = sprintf(
            "UPDATE %s SET is_enabled = IF(is_enabled=1, 0, 1), updated=NOW() WHERE rule_id=%d",
            TASK_AUTOMATION_TABLE,
            db_input($id)
        );
        return db_query($sql) ? true : false;
    }

    static function getByBoard($board_id) {
        $rules = array();
        if (!$board_id) return $rules;
        $sql = 'SELECT * FROM ' . TASK_AUTOMATION_TABLE
             . ' WHERE board_id=' . db_input($board_id)
             . ' ORDER BY rule_name ASC';
        if (($res = db_query($sql)) && db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                $row['trigger_config_arr'] = $row['trigger_config'] ? self::safeDecodeConfig($row['trigger_config']) : array();
                $row['action_config_arr'] = $row['action_config'] ? self::safeDecodeConfig($row['action_config']) : array();
                if (!is_array($row['trigger_config_arr'])) $row['trigger_config_arr'] = array();
                if (!is_array($row['action_config_arr'])) $row['action_config_arr'] = array();
                $rules[] = $row;
            }
        }
        return $rules;
    }

    static function getEnabledByBoard($board_id) {
        $rules = array();
        if (!$board_id) return $rules;
        $sql = 'SELECT * FROM ' . TASK_AUTOMATION_TABLE
             . ' WHERE board_id=' . db_input($board_id)
             . ' AND is_enabled=1'
             . ' ORDER BY rule_id ASC';
        if (($res = db_query($sql)) && db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                $row['trigger_config_arr'] = $row['trigger_config'] ? self::safeDecodeConfig($row['trigger_config']) : array();
                $row['action_config_arr'] = $row['action_config'] ? self::safeDecodeConfig($row['action_config']) : array();
                if (!is_array($row['trigger_config_arr'])) $row['trigger_config_arr'] = array();
                if (!is_array($row['action_config_arr'])) $row['action_config_arr'] = array();
                $rules[] = $row;
            }
        }
        return $rules;
    }

    static function fireEvent($task_id, $trigger_type, $context) {
        if (!$task_id || !$trigger_type) return;
        if (!is_array($context)) $context = array();

        $sql = 'SELECT board_id FROM ' . TASKS_TABLE . ' WHERE task_id=' . db_input($task_id);
        $board_id = 0;
        if (($res = db_query($sql)) && db_num_rows($res)) {
            $row = db_fetch_array($res);
            $board_id = $row['board_id'];
        }
        if (!$board_id) return;

        $rules = TaskAutomation::getEnabledByBoard($board_id);
        if (!$rules || count($rules) == 0) return;

        foreach ($rules as $rule) {
            if ($rule['trigger_type'] != $trigger_type) continue;

            if (!TaskAutomation::checkTriggerConditions($rule['trigger_config_arr'], $context)) continue;

            TaskAutomation::executeAction($task_id, $rule['action_type'], $rule['action_config_arr']);

            if (class_exists('TaskActivity')) {
                TaskActivity::log($task_id, 0, 'automation',
                    'Автоматизация: ' . Format::htmlchars($rule['rule_name']));
            }
        }
    }

    static function checkTriggerConditions($trigger_config, $context) {
        if (!is_array($trigger_config) || count($trigger_config) == 0) {
            return true;
        }

        if (isset($trigger_config['from_status']) && $trigger_config['from_status'] !== '') {
            if (!isset($context['from_status']) || $context['from_status'] != $trigger_config['from_status']) {
                return false;
            }
        }

        if (isset($trigger_config['to_status']) && $trigger_config['to_status'] !== '') {
            if (!isset($context['to_status']) || $context['to_status'] != $trigger_config['to_status']) {
                return false;
            }
        }

        if (isset($trigger_config['from_priority']) && $trigger_config['from_priority'] !== '') {
            if (!isset($context['from_priority']) || $context['from_priority'] != $trigger_config['from_priority']) {
                return false;
            }
        }

        if (isset($trigger_config['to_priority']) && $trigger_config['to_priority'] !== '') {
            if (!isset($context['to_priority']) || $context['to_priority'] != $trigger_config['to_priority']) {
                return false;
            }
        }

        if (isset($trigger_config['days_before'])) {
            return true;
        }

        return true;
    }

    static function executeAction($task_id, $action_type, $action_config) {
        if (!$task_id || !$action_type) return false;
        if (!is_array($action_config)) $action_config = array();

        switch ($action_type) {
            case 'change_status':
                if (isset($action_config['status']) && $action_config['status']) {
                    $valid = array('open', 'in_progress', 'blocked', 'completed', 'cancelled');
                    if (in_array($action_config['status'], $valid)) {
                        $completed_sql = '';
                        if ($action_config['status'] == 'completed') {
                            $completed_sql = ', completed_date=NOW()';
                        } else {
                            $completed_sql = ', completed_date=NULL';
                        }
                        $sql = sprintf(
                            "UPDATE %s SET status=%s, updated=NOW() %s WHERE task_id=%d",
                            TASKS_TABLE,
                            db_input($action_config['status']),
                            $completed_sql,
                            db_input($task_id)
                        );
                        db_query($sql);
                    }
                }
                break;

            case 'change_priority':
                if (isset($action_config['priority']) && $action_config['priority']) {
                    $valid = array('low', 'normal', 'high', 'urgent');
                    if (in_array($action_config['priority'], $valid)) {
                        $sql = sprintf(
                            "UPDATE %s SET priority=%s, updated=NOW() WHERE task_id=%d",
                            TASKS_TABLE,
                            db_input($action_config['priority']),
                            db_input($task_id)
                        );
                        db_query($sql);
                    }
                }
                break;

            case 'assign_to':
                if (isset($action_config['staff_id']) && intval($action_config['staff_id'])) {
                    $sql = sprintf(
                        "INSERT IGNORE INTO %s SET task_id=%d, staff_id=%d, role='assignee', assigned_date=NOW()",
                        TASK_ASSIGNEES_TABLE,
                        db_input($task_id),
                        db_input($action_config['staff_id'])
                    );
                    db_query($sql);
                }
                break;

            case 'move_to_list':
                if (isset($action_config['list_id']) && intval($action_config['list_id'])) {
                    $sql = 'SELECT MAX(position) FROM ' . TASKS_TABLE . ' WHERE list_id=' . db_input($action_config['list_id']);
                    $max = 0;
                    if (($res = db_query($sql)) && db_num_rows($res)) {
                        list($max) = db_fetch_row($res);
                    }
                    $sql = sprintf(
                        "UPDATE %s SET list_id=%d, position=%d, updated=NOW() WHERE task_id=%d",
                        TASKS_TABLE,
                        db_input($action_config['list_id']),
                        intval($max) + 1,
                        db_input($task_id)
                    );
                    db_query($sql);
                }
                break;

            case 'add_tag':
                if (isset($action_config['tag_id']) && intval($action_config['tag_id'])) {
                    if (class_exists('TaskTag')) {
                        TaskTag::addToTask($task_id, intval($action_config['tag_id']));
                    }
                }
                break;

            case 'send_notification':
                if (class_exists('TaskActivity')) {
                    $msg = isset($action_config['message']) ? $action_config['message'] : 'Уведомление';
                    TaskActivity::log($task_id, 0, 'notification', $msg);
                }
                break;

            default:
                return false;
        }

        return true;
    }
}
?>
