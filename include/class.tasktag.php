<?php
class TaskTag {

    public $id;
    public $row;

    public function __construct($id) {
        $this->id = 0;
        if ($id) {
            $sql = 'SELECT * FROM ' . TASK_TAGS_TABLE . ' WHERE tag_id=' . db_input($id);
            if (($res = db_query($sql)) && db_num_rows($res)) {
                $this->row = db_fetch_array($res);
                $this->id = $this->row['tag_id'];
            }
        }
    }

    function getId() { return $this->id; }
    function getName() { return $this->row['tag_name']; }
    function getColor() { return $this->row['tag_color']; }
    function getBoardId() { return $this->row['board_id']; }
    function getInfo() { return $this->row; }

    static function lookup($id) {
        $tag = new TaskTag($id);
        return ($tag && $tag->getId()) ? $tag : null;
    }

    static function create($data, &$errors) {
        if (!$data['tag_name']) {
            $errors['tag_name'] = 'Название тега обязательно';
        }
        if (!$data['board_id']) {
            $errors['board_id'] = 'Доска обязательна';
        }
        if ($errors) return false;

        $color = $data['tag_color'] ? $data['tag_color'] : '#3498db';
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#3498db';
        }

        $sql = sprintf(
            "INSERT INTO %s SET tag_name=%s, tag_color=%s, board_id=%d, created=NOW()",
            TASK_TAGS_TABLE,
            db_input(Format::striptags($data['tag_name'])),
            db_input($color),
            db_input($data['board_id'])
        );

        if (!db_query($sql) || !($id = db_insert_id())) {
            $errors['err'] = 'Ошибка создания тега';
            return false;
        }
        return $id;
    }

    static function updateTag($id, $data, &$errors) {
        if (!$id) return false;
        if (!$data['tag_name']) {
            $errors['tag_name'] = 'Название тега обязательно';
            return false;
        }

        $color = $data['tag_color'] ? $data['tag_color'] : '#3498db';
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#3498db';
        }

        $sql = sprintf(
            "UPDATE %s SET tag_name=%s, tag_color=%s WHERE tag_id=%d",
            TASK_TAGS_TABLE,
            db_input(Format::striptags($data['tag_name'])),
            db_input($color),
            db_input($id)
        );
        return db_query($sql) ? true : false;
    }

    static function deleteTag($id) {
        if (!$id) return false;
        $sql = 'DELETE FROM ' . TASK_TAG_ASSOC_TABLE . ' WHERE tag_id=' . db_input($id);
        db_query($sql);
        $sql = 'DELETE FROM ' . TASK_TAGS_TABLE . ' WHERE tag_id=' . db_input($id);
        return db_query($sql) ? true : false;
    }

    static function addToTask($task_id, $tag_id) {
        if (!$task_id || !$tag_id) return false;
        $sql = sprintf(
            "INSERT IGNORE INTO %s SET task_id=%d, tag_id=%d",
            TASK_TAG_ASSOC_TABLE,
            db_input($task_id),
            db_input($tag_id)
        );
        return db_query($sql) ? true : false;
    }

    static function removeFromTask($task_id, $tag_id) {
        if (!$task_id || !$tag_id) return false;
        $sql = sprintf(
            "DELETE FROM %s WHERE task_id=%d AND tag_id=%d",
            TASK_TAG_ASSOC_TABLE,
            db_input($task_id),
            db_input($tag_id)
        );
        return db_query($sql) ? true : false;
    }

    static function getByTask($task_id) {
        $tags = array();
        if (!$task_id) return $tags;
        $sql = 'SELECT t.* FROM ' . TASK_TAGS_TABLE . ' t'
             . ' JOIN ' . TASK_TAG_ASSOC_TABLE . ' a ON a.tag_id=t.tag_id'
             . ' WHERE a.task_id=' . db_input($task_id)
             . ' ORDER BY t.tag_name ASC';
        if (($res = db_query($sql)) && db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                $tags[] = $row;
            }
        }
        return $tags;
    }

    static function getByBoard($board_id) {
        $tags = array();
        if (!$board_id) return $tags;
        $sql = 'SELECT * FROM ' . TASK_TAGS_TABLE
             . ' WHERE board_id=' . db_input($board_id)
             . ' ORDER BY tag_name ASC';
        if (($res = db_query($sql)) && db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                $tags[] = $row;
            }
        }
        return $tags;
    }

    static function setTaskTags($task_id, $tag_ids) {
        if (!$task_id) return false;

        $sql = 'DELETE FROM ' . TASK_TAG_ASSOC_TABLE . ' WHERE task_id=' . db_input($task_id);
        db_query($sql);

        if (is_array($tag_ids) && count($tag_ids) > 0) {
            $values = array();
            foreach ($tag_ids as $tag_id) {
                $tag_id = intval($tag_id);
                if ($tag_id > 0) {
                    $values[] = '(' . db_input($task_id) . ',' . $tag_id . ')';
                }
            }

            if (count($values) > 0) {
                $sql = 'INSERT IGNORE INTO ' . TASK_TAG_ASSOC_TABLE
                     . ' (task_id, tag_id) VALUES ' . implode(',', $values);
                db_query($sql);
            }
        }

        return true;
    }

    static function deleteByTaskId($task_id) {
        if (!$task_id) return false;
        $sql = 'DELETE FROM ' . TASK_TAG_ASSOC_TABLE . ' WHERE task_id=' . db_input($task_id);
        return db_query($sql) ? true : false;
    }

    static function deleteByBoardId($board_id) {
        if (!$board_id) return false;
        $tag_ids = array();
        $sql = 'SELECT tag_id FROM ' . TASK_TAGS_TABLE . ' WHERE board_id=' . db_input($board_id);
        if (($res = db_query($sql)) && db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                $tag_ids[] = $row['tag_id'];
            }
        }
        if (count($tag_ids) > 0) {
            $sql = 'DELETE FROM ' . TASK_TAG_ASSOC_TABLE . ' WHERE tag_id IN(' . implode(',', $tag_ids) . ')';
            db_query($sql);
        }
        $sql = 'DELETE FROM ' . TASK_TAGS_TABLE . ' WHERE board_id=' . db_input($board_id);
        return db_query($sql) ? true : false;
    }
}
?>
