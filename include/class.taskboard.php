<?php
class TaskBoard {

    public $id;
    public $row;

    public function __construct($id) {
        $this->id = 0;
        if ($id && ($info = $this->getInfoById($id))) {
            $this->row = $info;
            $this->id = $info['board_id'];
        }
    }

    function getId() { return $this->id; }
    function getName() { return $this->row['board_name']; }
    function getType() { return $this->row['board_type']; }
    function getDeptId() { return $this->row['dept_id']; }
    function getDescription() { return $this->row['description']; }
    function getColor() { return $this->row['color']; }
    function isArchived() { return $this->row['is_archived'] ? true : false; }
    function getCreatedBy() { return $this->row['created_by']; }
    function getCreated() { return $this->row['created']; }
    function getUpdated() { return $this->row['updated']; }
    function getInfo() { return $this->row; }

    function isDepartment() { return $this->row['board_type'] == 'department'; }
    function isProject() { return $this->row['board_type'] == 'project'; }

    function getTypeLabel() {
        $labels = array('department' => 'Отдел', 'project' => 'Проект');
        return isset($labels[$this->row['board_type']]) ? $labels[$this->row['board_type']] : $this->row['board_type'];
    }

    function getTaskCount() {
        $sql = 'SELECT COUNT(*) FROM ' . TASKS_TABLE . ' WHERE board_id=' . db_input($this->id);
        if (($res = db_query($sql)) && db_num_rows($res)) {
            list($count) = db_fetch_row($res);
            return $count;
        }
        return 0;
    }

    function getOpenTaskCount() {
        $sql = 'SELECT COUNT(*) FROM ' . TASKS_TABLE
             . ' WHERE board_id=' . db_input($this->id)
             . " AND status NOT IN ('completed','cancelled')";
        if (($res = db_query($sql)) && db_num_rows($res)) {
            list($count) = db_fetch_row($res);
            return $count;
        }
        return 0;
    }

    function getLists() {
        $lists = array();
        $sql = 'SELECT * FROM ' . TASK_LISTS_TABLE
             . ' WHERE board_id=' . db_input($this->id)
             . ' AND is_archived=0'
             . ' ORDER BY list_order ASC, list_id ASC';
        if (($res = db_query($sql)) && db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                $lists[] = $row;
            }
        }
        return $lists;
    }

    static function getInfoById($id) {
        $sql = 'SELECT * FROM ' . TASK_BOARDS_TABLE . ' WHERE board_id=' . db_input($id);
        if (($res = db_query($sql)) && db_num_rows($res))
            return db_fetch_array($res);
        return null;
    }

    static function lookup($id) {
        $board = new TaskBoard($id);
        return ($board && $board->getId()) ? $board : null;
    }

    static function create($data, &$errors) {

        if (!$data['board_name']) {
            $errors['board_name'] = 'Название доски обязательно';
        }

        if (!$data['board_type'] || !in_array($data['board_type'], array('department', 'project'))) {
            $errors['board_type'] = 'Выберите тип доски';
        }

        if ($data['board_type'] == 'department' && !$data['dept_id']) {
            $errors['dept_id'] = 'Выберите отдел';
        }

        if (!$data['created_by']) {
            $errors['err'] = 'Ошибка идентификации пользователя';
        }

        if ($errors) return false;

        $sql = sprintf(
            "INSERT INTO %s SET
                board_name=%s,
                board_type=%s,
                dept_id=%d,
                description=%s,
                color=%s,
                created_by=%d,
                created=NOW()",
            TASK_BOARDS_TABLE,
            db_input(Format::striptags($data['board_name'])),
            db_input($data['board_type']),
            db_input($data['dept_id'] ? $data['dept_id'] : 0),
            db_input($data['description'] ? Format::striptags($data['description']) : ''),
            db_input($data['color'] ? $data['color'] : '#3498db'),
            db_input($data['created_by'])
        );

        if (!db_query($sql) || !($id = db_insert_id())) {
            $errors['err'] = 'Ошибка создания доски. Попробуйте снова.';
            return false;
        }

        $defaults = array('К выполнению', 'В работе', 'На проверке', 'Готово');
        foreach ($defaults as $order => $name) {
            $lsql = sprintf(
                "INSERT INTO %s SET board_id=%d, list_name=%s, list_order=%d, created=NOW()",
                TASK_LISTS_TABLE,
                $id,
                db_input($name),
                $order
            );
            db_query($lsql);
        }

        return $id;
    }

    static function update($id, $data, &$errors) {

        if (!$id) {
            $errors['err'] = 'Отсутствует ID доски';
            return false;
        }

        if (!$data['board_name']) {
            $errors['board_name'] = 'Название доски обязательно';
        }

        if ($errors) return false;

        $sql = sprintf(
            "UPDATE %s SET
                board_name=%s,
                board_type=%s,
                dept_id=%d,
                description=%s,
                color=%s,
                updated=NOW()
            WHERE board_id=%d",
            TASK_BOARDS_TABLE,
            db_input(Format::striptags($data['board_name'])),
            db_input($data['board_type'] ? $data['board_type'] : 'project'),
            db_input($data['dept_id'] ? $data['dept_id'] : 0),
            db_input($data['description'] ? Format::striptags($data['description']) : ''),
            db_input($data['color'] ? $data['color'] : '#3498db'),
            db_input($id)
        );

        if (!db_query($sql)) {
            $errors['err'] = 'Ошибка обновления доски.';
            return false;
        }

        return true;
    }

    static function delete($id) {
        $board = new TaskBoard($id);
        if (!$board->getId()) return false;

        $sql = 'DELETE FROM ' . TASKS_TABLE . ' WHERE board_id=' . db_input($id);
        db_query($sql);

        $sql = 'DELETE FROM ' . TASK_BOARDS_TABLE . ' WHERE board_id=' . db_input($id);
        return db_query($sql) ? true : false;
    }

    static function archive($id) {
        $sql = sprintf(
            "UPDATE %s SET is_archived=1, updated=NOW() WHERE board_id=%d",
            TASK_BOARDS_TABLE,
            db_input($id)
        );
        return db_query($sql) ? true : false;
    }

    static function addList($board_id, $name, &$errors) {
        if (!$name) {
            $errors['list_name'] = 'Название списка обязательно';
            return false;
        }

        $sql = 'SELECT MAX(list_order) FROM ' . TASK_LISTS_TABLE . ' WHERE board_id=' . db_input($board_id);
        $max = 0;
        if (($res = db_query($sql)) && db_num_rows($res)) {
            list($max) = db_fetch_row($res);
        }

        $sql = sprintf(
            "INSERT INTO %s SET board_id=%d, list_name=%s, list_order=%d, created=NOW()",
            TASK_LISTS_TABLE,
            db_input($board_id),
            db_input(Format::striptags($name)),
            $max + 1
        );

        if (!db_query($sql) || !($id = db_insert_id())) {
            $errors['err'] = 'Ошибка создания списка';
            return false;
        }

        return $id;
    }

    static function updateList($list_id, $name) {
        $sql = sprintf(
            "UPDATE %s SET list_name=%s, updated=NOW() WHERE list_id=%d",
            TASK_LISTS_TABLE,
            db_input(Format::striptags($name)),
            db_input($list_id)
        );
        return db_query($sql) ? true : false;
    }

    static function deleteList($list_id) {
        $sql = sprintf("UPDATE %s SET list_id=NULL WHERE list_id=%d", TASKS_TABLE, db_input($list_id));
        db_query($sql);

        $sql = 'DELETE FROM ' . TASK_LISTS_TABLE . ' WHERE list_id=' . db_input($list_id);
        return db_query($sql) ? true : false;
    }

    static function reorderLists($board_id, $order) {
        if (!is_array($order)) return false;
        foreach ($order as $pos => $list_id) {
            $sql = sprintf(
                "UPDATE %s SET list_order=%d WHERE list_id=%d AND board_id=%d",
                TASK_LISTS_TABLE,
                db_input($pos),
                db_input($list_id),
                db_input($board_id)
            );
            db_query($sql);
        }
        return true;
    }
}
?>
