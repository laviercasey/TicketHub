<?php
class TaskCustomField {

    public $id;
    public $row;

    public function __construct($id) {
        $this->id = 0;
        if ($id) {
            $sql = 'SELECT * FROM ' . TASK_CUSTOM_FIELDS_TABLE . ' WHERE field_id=' . db_input($id);
            if (($res = db_query($sql)) && db_num_rows($res)) {
                $this->row = db_fetch_array($res);
                $this->id = $this->row['field_id'];
            }
        }
    }

    function getId() { return $this->id; }
    function getName() { return $this->row['field_name']; }
    function getType() { return $this->row['field_type']; }
    function getBoardId() { return $this->row['board_id']; }
    function isRequired() { return $this->row['is_required'] ? true : false; }
    function getOrder() { return $this->row['field_order']; }
    function getInfo() { return $this->row; }

    function getOptions() {
        return self::safeDecodeConfig($this->row['field_options']);
    }

    private static function safeDecodeConfig($raw) {
        if (!$raw) return array();
        $val = json_decode($raw, true);
        if (is_array($val)) return $val;
        $val = @unserialize($raw, ['allowed_classes' => false]);
        return is_array($val) ? $val : array();
    }

    static function lookup($id) {
        $field = new TaskCustomField($id);
        return ($field && $field->getId()) ? $field : null;
    }

    static function create($data, &$errors) {
        if (!$data['field_name']) {
            $errors['field_name'] = 'Название поля обязательно';
        }
        if (!$data['board_id']) {
            $errors['board_id'] = 'Доска обязательна';
        }
        $valid_types = array('text', 'number', 'date', 'dropdown', 'checkbox', 'user', 'textarea');
        $field_type = (isset($data['field_type']) && in_array($data['field_type'], $valid_types)) ? $data['field_type'] : 'text';

        if ($errors) return false;

        $options = '';
        if ($field_type == 'dropdown' && isset($data['field_options'])) {
            if (is_array($data['field_options'])) {
                $options = json_encode($data['field_options']);
            } else {
                $lines = array_filter(array_map('trim', explode("\n", $data['field_options'])));
                $options = json_encode($lines);
            }
        }

        $order = 0;
        $sql = 'SELECT MAX(field_order) FROM ' . TASK_CUSTOM_FIELDS_TABLE . ' WHERE board_id=' . db_input($data['board_id']);
        if (($res = db_query($sql)) && db_num_rows($res)) {
            list($max) = db_fetch_row($res);
            $order = intval($max) + 1;
        }

        $sql = sprintf(
            "INSERT INTO %s SET board_id=%d, field_name=%s, field_type=%s, field_options=%s, is_required=%d, field_order=%d, created=NOW()",
            TASK_CUSTOM_FIELDS_TABLE,
            db_input($data['board_id']),
            db_input(Format::striptags($data['field_name'])),
            db_input($field_type),
            db_input($options),
            $data['is_required'] ? 1 : 0,
            $order
        );

        if (!db_query($sql) || !($id = db_insert_id())) {
            $errors['err'] = 'Ошибка создания поля';
            return false;
        }
        return $id;
    }

    static function updateField($id, $data, &$errors) {
        if (!$id) return false;
        if (!$data['field_name']) {
            $errors['field_name'] = 'Название поля обязательно';
            return false;
        }

        $options = '';
        if (isset($data['field_options'])) {
            if (is_array($data['field_options'])) {
                $options = json_encode($data['field_options']);
            } else {
                $lines = array_filter(array_map('trim', explode("\n", $data['field_options'])));
                $options = json_encode($lines);
            }
        }

        $sql = sprintf(
            "UPDATE %s SET field_name=%s, field_options=%s, is_required=%d WHERE field_id=%d",
            TASK_CUSTOM_FIELDS_TABLE,
            db_input(Format::striptags($data['field_name'])),
            db_input($options),
            $data['is_required'] ? 1 : 0,
            db_input($id)
        );
        return db_query($sql) ? true : false;
    }

    static function deleteField($id) {
        if (!$id) return false;
        $sql = 'DELETE FROM ' . TASK_CUSTOM_VALUES_TABLE . ' WHERE field_id=' . db_input($id);
        db_query($sql);
        $sql = 'DELETE FROM ' . TASK_CUSTOM_FIELDS_TABLE . ' WHERE field_id=' . db_input($id);
        return db_query($sql) ? true : false;
    }

    static function getByBoard($board_id) {
        $fields = array();
        if (!$board_id) return $fields;
        $sql = 'SELECT * FROM ' . TASK_CUSTOM_FIELDS_TABLE
             . ' WHERE board_id=' . db_input($board_id)
             . ' ORDER BY field_order ASC, field_id ASC';
        if (($res = db_query($sql)) && db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                $fields[] = $row;
            }
        }
        return $fields;
    }

    static function getValue($task_id, $field_id) {
        if (!$task_id || !$field_id) return '';
        $sql = 'SELECT field_value FROM ' . TASK_CUSTOM_VALUES_TABLE
             . ' WHERE task_id=' . db_input($task_id)
             . ' AND field_id=' . db_input($field_id);
        if (($res = db_query($sql)) && db_num_rows($res)) {
            $row = db_fetch_array($res);
            return $row['field_value'];
        }
        return '';
    }

    static function setValue($task_id, $field_id, $value) {
        if (!$task_id || !$field_id) return false;

        $sql = 'SELECT value_id FROM ' . TASK_CUSTOM_VALUES_TABLE
             . ' WHERE task_id=' . db_input($task_id)
             . ' AND field_id=' . db_input($field_id);
        if (($res = db_query($sql)) && db_num_rows($res)) {
            $sql = sprintf(
                "UPDATE %s SET field_value=%s WHERE task_id=%d AND field_id=%d",
                TASK_CUSTOM_VALUES_TABLE,
                db_input($value),
                db_input($task_id),
                db_input($field_id)
            );
        } else {
            $sql = sprintf(
                "INSERT INTO %s SET task_id=%d, field_id=%d, field_value=%s",
                TASK_CUSTOM_VALUES_TABLE,
                db_input($task_id),
                db_input($field_id),
                db_input($value)
            );
        }
        return db_query($sql) ? true : false;
    }

    static function getValuesByTask($task_id) {
        $values = array();
        if (!$task_id) return $values;
        $sql = 'SELECT v.*, f.field_name, f.field_type, f.field_options, f.is_required'
             . ' FROM ' . TASK_CUSTOM_VALUES_TABLE . ' v'
             . ' JOIN ' . TASK_CUSTOM_FIELDS_TABLE . ' f ON f.field_id=v.field_id'
             . ' WHERE v.task_id=' . db_input($task_id)
             . ' ORDER BY f.field_order ASC';
        if (($res = db_query($sql)) && db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                $values[$row['field_id']] = $row;
            }
        }
        return $values;
    }

    static function setTaskValues($task_id, $field_values) {
        if (!$task_id || !is_array($field_values)) return false;
        foreach ($field_values as $field_id => $value) {
            if (intval($field_id)) {
                TaskCustomField::setValue($task_id, intval($field_id), $value);
            }
        }
        return true;
    }

    static function deleteByTaskId($task_id) {
        if (!$task_id) return false;
        $sql = 'DELETE FROM ' . TASK_CUSTOM_VALUES_TABLE . ' WHERE task_id=' . db_input($task_id);
        return db_query($sql) ? true : false;
    }

    static function deleteByBoardId($board_id) {
        if (!$board_id) return false;
        $field_ids = array();
        $sql = 'SELECT field_id FROM ' . TASK_CUSTOM_FIELDS_TABLE . ' WHERE board_id=' . db_input($board_id);
        if (($res = db_query($sql)) && db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                $field_ids[] = $row['field_id'];
            }
        }
        if (count($field_ids) > 0) {
            $sql = 'DELETE FROM ' . TASK_CUSTOM_VALUES_TABLE . ' WHERE field_id IN(' . implode(',', $field_ids) . ')';
            db_query($sql);
        }
        $sql = 'DELETE FROM ' . TASK_CUSTOM_FIELDS_TABLE . ' WHERE board_id=' . db_input($board_id);
        return db_query($sql) ? true : false;
    }

    static function getTypeLabels() {
        return array(
            'text' => 'Текст',
            'number' => 'Число',
            'date' => 'Дата',
            'dropdown' => 'Выпадающий список',
            'checkbox' => 'Чекбокс',
            'user' => 'Сотрудник',
            'textarea' => 'Текстовое поле'
        );
    }

    static function formatValue($field_type, $value, $field_options_str) {
        if ($value === '' || $value === null) return '';
        switch ($field_type) {
            case 'checkbox':
                return $value ? 'Да' : 'Нет';
            case 'date':
                return $value ? date('d.m.Y', strtotime($value)) : '';
            case 'user':
                if (intval($value)) {
                    $sql = 'SELECT CONCAT(firstname," ",lastname) as name FROM ' . TABLE_PREFIX . 'staff WHERE staff_id=' . db_input($value);
                    if (($res = db_query($sql)) && db_num_rows($res)) {
                        $row = db_fetch_array($res);
                        return $row['name'];
                    }
                }
                return '';
            default:
                return $value;
        }
    }
}
?>
