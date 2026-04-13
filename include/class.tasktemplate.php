<?php
class TaskTemplate {

    public $id;
    public $row;

    public function __construct($id) {
        $this->id = 0;
        if ($id && ($info = TaskTemplate::getInfoById($id))) {
            $this->row = $info;
            $this->id = $info['template_id'];
        }
    }

    function getId() { return $this->id; }
    function getName() { return $this->row['template_name']; }
    function getType() { return $this->row['template_type']; }

    function getData() {
        return self::safeDecodeConfig($this->row['template_data']);
    }

    private static function safeDecodeConfig($raw) {
        if (!$raw) return array();
        $val = json_decode($raw, true);
        if (is_array($val)) return $val;
        $val = @unserialize($raw, ['allowed_classes' => false]);
        return is_array($val) ? $val : array();
    }

    function getCreatedBy() { return $this->row['created_by']; }
    function getCreated() { return $this->row['created']; }
    function getInfo() { return $this->row; }

    function getCreatorName() { return $this->row['creator_name']; }

    static function getInfoById($id) {
        $sql = 'SELECT t.*, CONCAT(s.firstname, " ", s.lastname) as creator_name'
             . ' FROM ' . TASK_TEMPLATES_TABLE . ' t'
             . ' LEFT JOIN ' . TABLE_PREFIX . 'staff s ON s.staff_id=t.created_by'
             . ' WHERE t.template_id=' . db_input($id);
        if (($res = db_query($sql)) && db_num_rows($res))
            return db_fetch_array($res);
        return null;
    }

    static function lookup($id) {
        $obj = new TaskTemplate($id);
        return ($obj && $obj->getId()) ? $obj : null;
    }

    static function create($data, &$errors) {

        if (!$data['template_name']) {
            $errors['template_name'] = 'Название шаблона обязательно';
        }

        $valid_types = array('task', 'project', 'board');
        $type = (isset($data['template_type']) && in_array($data['template_type'], $valid_types))
              ? $data['template_type'] : 'task';

        if ($errors) return false;

        $template_data = '';
        if (isset($data['template_data'])) {
            $template_data = is_array($data['template_data'])
                           ? json_encode($data['template_data'])
                           : $data['template_data'];
        }

        $sql = sprintf(
            "INSERT INTO %s SET
                template_name=%s,
                template_type=%s,
                template_data=%s,
                created_by=%d,
                created=NOW()",
            TASK_TEMPLATES_TABLE,
            db_input(Format::striptags($data['template_name'])),
            db_input($type),
            db_input($template_data),
            db_input($data['created_by'] ? $data['created_by'] : 0)
        );

        if (!db_query($sql) || !($id = db_insert_id())) {
            $errors['err'] = 'Ошибка создания шаблона';
            return false;
        }

        return $id;
    }

    static function update($id, $data, &$errors) {

        if (!$id) {
            $errors['err'] = 'Отсутствует ID шаблона';
            return false;
        }

        $sets = array();
        if (isset($data['template_name']))
            $sets[] = 'template_name=' . db_input(Format::striptags($data['template_name']));
        if (isset($data['template_type'])) {
            $valid_types = array('task', 'project', 'board');
            if (in_array($data['template_type'], $valid_types))
                $sets[] = 'template_type=' . db_input($data['template_type']);
        }
        if (isset($data['template_data'])) {
            $td = is_array($data['template_data'])
                ? json_encode($data['template_data'])
                : $data['template_data'];
            $sets[] = 'template_data=' . db_input($td);
        }

        $sql = 'UPDATE ' . TASK_TEMPLATES_TABLE
             . ' SET ' . implode(', ', $sets)
             . ' WHERE template_id=' . db_input($id);

        return db_query($sql) ? true : false;
    }

    static function delete($id) {
        $sql = 'DELETE FROM ' . TASK_TEMPLATES_TABLE
             . ' WHERE template_id=' . db_input($id);
        return db_query($sql) ? true : false;
    }

    static function getAll($type = null) {
        $templates = array();
        $sql = 'SELECT t.*, CONCAT(s.firstname, " ", s.lastname) as creator_name'
             . ' FROM ' . TASK_TEMPLATES_TABLE . ' t'
             . ' LEFT JOIN ' . TABLE_PREFIX . 'staff s ON s.staff_id=t.created_by';
        if ($type) {
            $sql .= ' WHERE t.template_type=' . db_input($type);
        }
        $sql .= ' ORDER BY t.template_name ASC';

        if (($res = db_query($sql)) && db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                $templates[] = $row;
            }
        }
        return $templates;
    }

    static function getByType($type) {
        return TaskTemplate::getAll($type);
    }

    static function createFromTask($task_id, $name, $staff_id) {

        $task = new Task($task_id);
        if (!$task->getId()) return false;

        $info = $task->getInfo();
        $assignees = $task->getAssignees('assignee');

        $assignee_ids = array();
        foreach ($assignees as $a) {
            $assignee_ids[] = $a['staff_id'];
        }

        $template_data = array(
            'title' => $info['title'],
            'description' => $info['description'],
            'board_id' => $info['board_id'],
            'list_id' => $info['list_id'],
            'task_type' => $info['task_type'],
            'priority' => $info['priority'],
            'time_estimate' => $info['time_estimate'],
            'assignees' => $assignee_ids
        );

        $errors = array();
        $tpl_id = TaskTemplate::create(array(
            'template_name' => $name ? $name : $info['title'],
            'template_type' => 'task',
            'template_data' => $template_data,
            'created_by' => $staff_id
        ), $errors);

        return $tpl_id;
    }

    static function createTaskFromTemplate($template_id, $board_id, $staff_id) {

        $tpl = new TaskTemplate($template_id);
        if (!$tpl->getId()) return false;

        $data = $tpl->getData();
        if (!$data || !is_array($data)) return false;

        $use_board_id = $board_id ? $board_id : (isset($data['board_id']) ? $data['board_id'] : 0);

        $task_data = array(
            'title' => isset($data['title']) ? $data['title'] : $tpl->getName(),
            'description' => isset($data['description']) ? $data['description'] : '',
            'board_id' => $use_board_id,
            'list_id' => isset($data['list_id']) ? $data['list_id'] : null,
            'task_type' => isset($data['task_type']) ? $data['task_type'] : 'action',
            'priority' => isset($data['priority']) ? $data['priority'] : 'normal',
            'status' => 'open',
            'time_estimate' => isset($data['time_estimate']) ? $data['time_estimate'] : 0,
            'created_by' => $staff_id,
            'assignees' => isset($data['assignees']) ? $data['assignees'] : array()
        );

        $errors = array();
        $new_id = Task::create($task_data, $errors);

        return $new_id ? $new_id : false;
    }

    static function getTypeLabels() {
        return array(
            'task' => 'Задача',
            'project' => 'Проект',
            'board' => 'Доска'
        );
    }

    function getTypeLabel() {
        $labels = TaskTemplate::getTypeLabels();
        return isset($labels[$this->row['template_type']]) ? $labels[$this->row['template_type']] : $this->row['template_type'];
    }
}
?>
