<?php
class TaskFilter {

    public $id;
    public $row;

    public function __construct($id) {
        $this->id = 0;
        if ($id) {
            $sql = 'SELECT * FROM ' . TASK_SAVED_FILTERS_TABLE . ' WHERE filter_id=' . db_input($id);
            if (($res = db_query($sql)) && db_num_rows($res)) {
                $this->row = db_fetch_array($res);
                $this->id = $this->row['filter_id'];
            }
        }
    }

    function getId() { return $this->id; }
    function getName() { return $this->row['filter_name']; }
    function getStaffId() { return $this->row['staff_id']; }
    function isDefault() { return $this->row['is_default'] ? true : false; }
    function getInfo() { return $this->row; }

    function getConfig() {
        $config = self::safeDecodeConfig($this->row['filter_config']);
        return $config;
    }

    private static function safeDecodeConfig($raw) {
        if (!$raw) return array();
        $val = json_decode($raw, true);
        if (is_array($val)) return $val;
        $val = @unserialize($raw, ['allowed_classes' => false]);
        return is_array($val) ? $val : array();
    }

    static function lookup($id) {
        $filter = new TaskFilter($id);
        return ($filter && $filter->getId()) ? $filter : null;
    }

    static function create($data, &$errors) {
        if (!$data['filter_name']) {
            $errors['filter_name'] = 'Название фильтра обязательно';
        }
        if (!$data['staff_id']) {
            $errors['staff_id'] = 'Не указан сотрудник';
        }
        if ($errors) return false;

        $config = isset($data['filter_config']) ? $data['filter_config'] : array();
        if (is_array($config)) {
            $config = json_encode($config);
        }

        $sql = sprintf(
            "INSERT INTO %s SET staff_id=%d, filter_name=%s, filter_config=%s, is_default=0, created=NOW(), updated=NOW()",
            TASK_SAVED_FILTERS_TABLE,
            db_input($data['staff_id']),
            db_input(Format::striptags($data['filter_name'])),
            db_input($config)
        );

        if (!db_query($sql) || !($id = db_insert_id())) {
            $errors['err'] = 'Ошибка сохранения фильтра';
            return false;
        }
        return $id;
    }

    static function update($id, $data, &$errors) {
        if (!$id) return false;
        if (!$data['filter_name']) {
            $errors['filter_name'] = 'Название фильтра обязательно';
            return false;
        }

        $sets = array(
            'filter_name=' . db_input(Format::striptags($data['filter_name'])),
            'updated=NOW()'
        );

        if (isset($data['filter_config'])) {
            $config = $data['filter_config'];
            if (is_array($config)) {
                $config = json_encode($config);
            }
            $sets[] = 'filter_config=' . db_input($config);
        }

        $sql = 'UPDATE ' . TASK_SAVED_FILTERS_TABLE . ' SET ' . implode(', ', $sets)
             . ' WHERE filter_id=' . db_input($id);
        return db_query($sql) ? true : false;
    }

    static function delete($id) {
        if (!$id) return false;
        $sql = 'DELETE FROM ' . TASK_SAVED_FILTERS_TABLE . ' WHERE filter_id=' . db_input($id);
        return db_query($sql) ? true : false;
    }

    static function getByStaff($staff_id) {
        $filters = array();
        if (!$staff_id) return $filters;
        $sql = 'SELECT * FROM ' . TASK_SAVED_FILTERS_TABLE
             . ' WHERE staff_id=' . db_input($staff_id)
             . ' ORDER BY is_default DESC, filter_name ASC';
        if (($res = db_query($sql)) && db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                $row['filter_config'] = self::safeDecodeConfig($row['filter_config']);
                $filters[] = $row;
            }
        }
        return $filters;
    }

    static function setDefault($id, $staff_id) {
        if (!$id || !$staff_id) return false;
        $sql = 'UPDATE ' . TASK_SAVED_FILTERS_TABLE . ' SET is_default=0 WHERE staff_id=' . db_input($staff_id);
        db_query($sql);
        $sql = 'UPDATE ' . TASK_SAVED_FILTERS_TABLE . ' SET is_default=1, updated=NOW()'
             . ' WHERE filter_id=' . db_input($id) . ' AND staff_id=' . db_input($staff_id);
        return db_query($sql) ? true : false;
    }

    static function unsetDefault($id, $staff_id) {
        if (!$id || !$staff_id) return false;
        $sql = 'UPDATE ' . TASK_SAVED_FILTERS_TABLE . ' SET is_default=0, updated=NOW()'
             . ' WHERE filter_id=' . db_input($id) . ' AND staff_id=' . db_input($staff_id);
        return db_query($sql) ? true : false;
    }

    static function getDefault($staff_id) {
        if (!$staff_id) return null;
        $sql = 'SELECT * FROM ' . TASK_SAVED_FILTERS_TABLE
             . ' WHERE staff_id=' . db_input($staff_id) . ' AND is_default=1 LIMIT 1';
        if (($res = db_query($sql)) && db_num_rows($res)) {
            $row = db_fetch_array($res);
            $row['filter_config'] = self::safeDecodeConfig($row['filter_config']);
            return $row;
        }
        return null;
    }

    static function applyFilter($config, &$qwhere, &$qfrom) {
        if (!is_array($config) || !count($config)) return;

        if (!empty($config['board_id'])) {
            $qwhere .= ' AND t.board_id=' . db_input(intval($config['board_id']));
        }

        if (!empty($config['status']) && is_array($config['status'])) {
            $statuses = array();
            foreach ($config['status'] as $s) {
                $statuses[] = db_input($s);
            }
            $qwhere .= ' AND t.status IN(' . implode(',', $statuses) . ')';
        }

        if (!empty($config['priority']) && is_array($config['priority'])) {
            $priorities = array();
            foreach ($config['priority'] as $p) {
                $priorities[] = db_input($p);
            }
            $qwhere .= ' AND t.priority IN(' . implode(',', $priorities) . ')';
        }

        if (!empty($config['assignee']) && is_array($config['assignee'])) {
            $aIds = array();
            foreach ($config['assignee'] as $a) {
                $aIds[] = intval($a);
            }
            if (count($aIds)) {
                $qfrom .= ' JOIN ' . TASK_ASSIGNEES_TABLE . ' fa ON fa.task_id=t.task_id';
                $qwhere .= ' AND fa.staff_id IN(' . implode(',', $aIds) . ") AND fa.role='assignee'";
            }
        }

        if (!empty($config['date_from'])) {
            $qwhere .= ' AND t.created >= ' . db_input($config['date_from'] . ' 00:00:00');
        }

        if (!empty($config['date_to'])) {
            $qwhere .= ' AND t.created <= ' . db_input($config['date_to'] . ' 23:59:59');
        }

        if (!empty($config['has_deadline'])) {
            $qwhere .= ' AND t.deadline IS NOT NULL';
        }

        if (!empty($config['is_overdue'])) {
            $qwhere .= " AND t.deadline IS NOT NULL AND t.deadline < NOW() AND t.status NOT IN ('completed','cancelled')";
        }

        if (!empty($config['tags']) && is_array($config['tags'])) {
            $tagIds = array();
            foreach ($config['tags'] as $tid) {
                $tagIds[] = intval($tid);
            }
            if (count($tagIds)) {
                $qfrom .= ' JOIN ' . TASK_TAG_ASSOC_TABLE . ' fta ON fta.task_id=t.task_id';
                $qwhere .= ' AND fta.tag_id IN(' . implode(',', $tagIds) . ')';
            }
        }

        if (!empty($config['search_text'])) {
            $qwhere .= ' AND MATCH(t.title, t.description) AGAINST(' . db_input($config['search_text']) . ')';
        }
    }
}
?>
