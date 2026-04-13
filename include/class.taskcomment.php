<?php
class TaskComment {

    public $id;
    public $row;

    public function __construct($id) {
        $this->id = 0;
        if ($id && ($info = $this->getInfoById($id))) {
            $this->row = $info;
            $this->id = $info['comment_id'];
        }
    }

    function getId() { return $this->id; }
    function getTaskId() { return $this->row['task_id']; }
    function getStaffId() { return $this->row['staff_id']; }
    function getText() { return $this->row['comment_text']; }
    function getCreated() { return $this->row['created']; }
    function getUpdated() { return $this->row['updated']; }
    function getStaffName() { return $this->row['staff_name']; }
    function getInfo() { return $this->row; }

    static function getInfoById($id) {
        $sql = 'SELECT c.*, CONCAT(s.firstname," ",s.lastname) as staff_name'
             . ' FROM ' . TASK_COMMENTS_TABLE . ' c'
             . ' LEFT JOIN ' . TABLE_PREFIX . 'staff s ON s.staff_id=c.staff_id'
             . ' WHERE c.comment_id=' . db_input($id);
        if (($res = db_query($sql)) && db_num_rows($res))
            return db_fetch_array($res);
        return null;
    }

    static function lookup($id) {
        $c = new TaskComment($id);
        return ($c && $c->getId()) ? $c : null;
    }

    static function getByTaskId($task_id, $limit = 0) {
        $comments = array();
        $sql = 'SELECT c.*, CONCAT(s.firstname," ",s.lastname) as staff_name'
             . ' FROM ' . TASK_COMMENTS_TABLE . ' c'
             . ' LEFT JOIN ' . TABLE_PREFIX . 'staff s ON s.staff_id=c.staff_id'
             . ' WHERE c.task_id=' . db_input($task_id)
             . ' ORDER BY c.created ASC';
        if ($limit > 0) $sql .= ' LIMIT ' . intval($limit);

        if (($res = db_query($sql)) && db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                $comments[] = $row;
            }
        }
        return $comments;
    }

    static function getCountByTaskId($task_id) {
        $sql = 'SELECT COUNT(*) FROM ' . TASK_COMMENTS_TABLE . ' WHERE task_id=' . db_input($task_id);
        if (($res = db_query($sql)) && db_num_rows($res)) {
            list($count) = db_fetch_row($res);
            return $count;
        }
        return 0;
    }

    static function create($data, &$errors) {

        if (!$data['task_id']) {
            $errors['err'] = 'Не указана задача';
        }

        if (!$data['staff_id']) {
            $errors['err'] = 'Ошибка идентификации пользователя';
        }

        if (!trim($data['comment_text'])) {
            $errors['comment_text'] = 'Текст комментария обязателен';
        }

        if ($errors) return false;

        $sql = sprintf(
            "INSERT INTO %s SET
                task_id=%d,
                staff_id=%d,
                comment_text=%s,
                created=NOW()",
            TASK_COMMENTS_TABLE,
            db_input($data['task_id']),
            db_input($data['staff_id']),
            db_input(Format::striptags($data['comment_text']))
        );

        if (!db_query($sql) || !($id = db_insert_id())) {
            $errors['err'] = 'Ошибка добавления комментария';
            return false;
        }

        TaskActivity::log($data['task_id'], $data['staff_id'], 'commented', $data['comment_text']);

        return $id;
    }

    static function update($id, $text, &$errors) {
        if (!$id) {
            $errors['err'] = 'Не указан ID комментария';
            return false;
        }

        if (!trim($text)) {
            $errors['comment_text'] = 'Текст комментария обязателен';
            return false;
        }

        $sql = sprintf(
            "UPDATE %s SET comment_text=%s, updated=NOW() WHERE comment_id=%d",
            TASK_COMMENTS_TABLE,
            db_input(Format::striptags($text)),
            db_input($id)
        );

        return db_query($sql) ? true : false;
    }

    static function delete($id) {
        $sql = 'DELETE FROM ' . TASK_COMMENTS_TABLE . ' WHERE comment_id=' . db_input($id);
        return db_query($sql) ? true : false;
    }
}
?>
