<?php
class TaskRecurring {

    public $id;
    public $row;

    public function __construct($id) {
        $this->id = 0;
        if ($id && ($info = TaskRecurring::getInfoById($id))) {
            $this->row = $info;
            $this->id = $info['recurring_id'];
        }
    }

    function getId() { return $this->id; }
    function getTaskId() { return $this->row['task_id']; }
    function getFrequency() { return $this->row['frequency']; }
    function getIntervalValue() { return $this->row['interval_value']; }
    function getDayOfWeek() { return $this->row['day_of_week']; }
    function getNextOccurrence() { return $this->row['next_occurrence']; }
    function isActive() { return $this->row['is_active'] ? true : false; }
    function getLastCreated() { return $this->row['last_created']; }
    function getInfo() { return $this->row; }

    static function getInfoById($id) {
        $sql = 'SELECT * FROM ' . TASK_RECURRING_TABLE
             . ' WHERE recurring_id=' . db_input($id);
        if (($res = db_query($sql)) && db_num_rows($res))
            return db_fetch_array($res);
        return null;
    }

    static function lookup($id) {
        $obj = new TaskRecurring($id);
        return ($obj && $obj->getId()) ? $obj : null;
    }

    static function create($data, &$errors) {

        if (!$data['task_id']) {
            $errors['task_id'] = 'ID задачи обязателен';
        }

        $valid_freq = array('daily', 'weekly', 'monthly', 'yearly');
        if (!$data['frequency'] || !in_array($data['frequency'], $valid_freq)) {
            $errors['frequency'] = 'Укажите частоту повторения';
        }

        if ($errors) return false;

        $interval = isset($data['interval_value']) ? intval($data['interval_value']) : 1;
        if ($interval < 1) $interval = 1;

        $day_of_week = isset($data['day_of_week']) ? Format::striptags($data['day_of_week']) : '';

        $next = TaskRecurring::calculateNextOccurrence(
            $data['frequency'], $interval, $day_of_week, date('Y-m-d H:i:s')
        );

        $sql = sprintf(
            "INSERT INTO %s SET
                task_id=%d,
                frequency=%s,
                interval_value=%d,
                day_of_week=%s,
                next_occurrence=%s,
                is_active=1",
            TASK_RECURRING_TABLE,
            db_input($data['task_id']),
            db_input($data['frequency']),
            $interval,
            db_input($day_of_week),
            db_input($next)
        );

        if (!db_query($sql) || !($id = db_insert_id())) {
            $errors['err'] = 'Ошибка создания повторения';
            return false;
        }

        return $id;
    }

    static function update($id, $data, &$errors) {

        if (!$id) {
            $errors['err'] = 'Отсутствует ID';
            return false;
        }

        $valid_freq = array('daily', 'weekly', 'monthly', 'yearly');
        if ($data['frequency'] && !in_array($data['frequency'], $valid_freq)) {
            $errors['frequency'] = 'Некорректная частота';
            return false;
        }

        $sets = array();
        if (isset($data['frequency']))
            $sets[] = 'frequency=' . db_input($data['frequency']);
        if (isset($data['interval_value']))
            $sets[] = 'interval_value=' . intval($data['interval_value']);
        if (isset($data['day_of_week']))
            $sets[] = 'day_of_week=' . db_input(Format::striptags($data['day_of_week']));
        if (isset($data['is_active']))
            $sets[] = 'is_active=' . ($data['is_active'] ? 1 : 0);
        if (isset($data['next_occurrence']))
            $sets[] = 'next_occurrence=' . db_input($data['next_occurrence']);

        $sql = 'UPDATE ' . TASK_RECURRING_TABLE
             . ' SET ' . implode(', ', $sets)
             . ' WHERE recurring_id=' . db_input($id);

        return db_query($sql) ? true : false;
    }

    static function delete($id) {
        $sql = 'DELETE FROM ' . TASK_RECURRING_TABLE
             . ' WHERE recurring_id=' . db_input($id);
        return db_query($sql) ? true : false;
    }

    static function deleteByTaskId($task_id) {
        $sql = 'DELETE FROM ' . TASK_RECURRING_TABLE
             . ' WHERE task_id=' . db_input($task_id);
        return db_query($sql) ? true : false;
    }

    static function getByTaskId($task_id) {
        $sql = 'SELECT * FROM ' . TASK_RECURRING_TABLE
             . ' WHERE task_id=' . db_input($task_id)
             . ' LIMIT 1';
        if (($res = db_query($sql)) && db_num_rows($res))
            return db_fetch_array($res);
        return null;
    }

    static function getActive() {
        $rules = array();
        $sql = 'SELECT r.*, t.title as task_title'
             . ' FROM ' . TASK_RECURRING_TABLE . ' r'
             . ' LEFT JOIN ' . TASKS_TABLE . ' t ON t.task_id=r.task_id'
             . ' WHERE r.is_active=1'
             . ' ORDER BY r.next_occurrence ASC';
        if (($res = db_query($sql)) && db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                $rules[] = $row;
            }
        }
        return $rules;
    }

    static function getDue() {
        $rules = array();
        $sql = 'SELECT r.*, t.title as task_title'
             . ' FROM ' . TASK_RECURRING_TABLE . ' r'
             . ' LEFT JOIN ' . TASKS_TABLE . ' t ON t.task_id=r.task_id'
             . ' WHERE r.is_active=1'
             . ' AND r.next_occurrence <= NOW()'
             . ' ORDER BY r.next_occurrence ASC';
        if (($res = db_query($sql)) && db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                $rules[] = $row;
            }
        }
        return $rules;
    }

    static function calculateNextOccurrence($frequency, $interval, $day_of_week, $from_date) {
        $from = strtotime($from_date);
        if (!$from) $from = time();

        switch ($frequency) {
            case 'daily':
                $next = strtotime('+' . intval($interval) . ' days', $from);
                break;

            case 'weekly':
                if ($day_of_week) {
                    $days = explode(',', $day_of_week);
                    $days = array_map('intval', $days);
                    sort($days);

                    $current_dow = intval(date('N', $from));
                    $found = false;

                    foreach ($days as $d) {
                        if ($d > $current_dow) {
                            $diff = $d - $current_dow;
                            $next = strtotime('+' . $diff . ' days', $from);
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        $days_to_monday = 8 - $current_dow;
                        $extra_weeks = ($interval - 1) * 7;
                        $first_day = $days[0];
                        $next = strtotime('+' . ($days_to_monday + $extra_weeks + $first_day - 1) . ' days', $from);
                    }
                } else {
                    $next = strtotime('+' . intval($interval) . ' weeks', $from);
                }
                break;

            case 'monthly':
                $next = strtotime('+' . intval($interval) . ' months', $from);
                break;

            case 'yearly':
                $next = strtotime('+' . intval($interval) . ' years', $from);
                break;

            default:
                $next = strtotime('+1 day', $from);
                break;
        }

        return date('Y-m-d H:i:s', $next);
    }

    static function processRecurring() {
        $due = TaskRecurring::getDue();
        $created = 0;

        foreach ($due as $rule) {
            $task_id = $rule['task_id'];

            $template_task = new Task($task_id);
            if (!$template_task->getId()) {
                TaskRecurring::update($rule['recurring_id'], array('is_active' => 0), $e);
                continue;
            }

            $info = $template_task->getInfo();
            $assignees = $template_task->getAssignees('assignee');

            $assignee_ids = array();
            foreach ($assignees as $a) {
                $assignee_ids[] = $a['staff_id'];
            }

            $data = array(
                'title' => $info['title'],
                'description' => $info['description'],
                'board_id' => $info['board_id'],
                'list_id' => $info['list_id'],
                'parent_task_id' => $info['parent_task_id'],
                'task_type' => $info['task_type'],
                'priority' => $info['priority'],
                'status' => 'open',
                'time_estimate' => $info['time_estimate'],
                'created_by' => $info['created_by'],
                'assignees' => $assignee_ids
            );

            if ($info['start_date'] && $info['end_date']) {
                $duration = strtotime($info['end_date']) - strtotime($info['start_date']);
                $data['start_date'] = date('Y-m-d', time());
                $data['end_date'] = date('Y-m-d', time() + $duration);
            }
            if ($info['deadline']) {
                $data['deadline'] = date('Y-m-d', time());
            }

            $task_errors = array();
            $new_id = Task::create($data, $task_errors);

            if ($new_id) {
                $created++;
            }

            $next = TaskRecurring::calculateNextOccurrence(
                $rule['frequency'],
                $rule['interval_value'],
                $rule['day_of_week'],
                $rule['next_occurrence']
            );

            $upd_errors = array();
            TaskRecurring::update($rule['recurring_id'], array(
                'next_occurrence' => $next
            ), $upd_errors);
        }

        return $created;
    }

    static function getFrequencyLabels() {
        return array(
            'daily' => 'Ежедневно',
            'weekly' => 'Еженедельно',
            'monthly' => 'Ежемесячно',
            'yearly' => 'Ежегодно'
        );
    }

    static function getDayLabels() {
        return array(
            1 => 'Пн',
            2 => 'Вт',
            3 => 'Ср',
            4 => 'Чт',
            5 => 'Пт',
            6 => 'Сб',
            7 => 'Вс'
        );
    }

    function getFrequencyLabel() {
        $labels = TaskRecurring::getFrequencyLabels();
        return isset($labels[$this->row['frequency']]) ? $labels[$this->row['frequency']] : $this->row['frequency'];
    }
}
?>
