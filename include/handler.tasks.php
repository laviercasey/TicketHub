<?php
if(!defined('OSTAJAXINC') || !defined('INCLUDE_DIR')) die('!');

require_once(INCLUDE_DIR.'class.task.php');
require_once(INCLUDE_DIR.'class.taskboard.php');
require_once(INCLUDE_DIR.'class.taskactivity.php');
require_once(INCLUDE_DIR.'class.tasktimelog.php');

class TasksAjaxAPI {

    function preview($params) {
        global $thisuser;

        $id = isset($params['id']) ? intval($params['id']) : 0;
        if (!$id) return 'Не указан ID задачи';

        $task = Task::lookup($id);
        if (!$task || !$task->getId()) return 'Задача не найдена';

        $html = '<div class="task-preview">';
        $html .= '<h4>' . Format::htmlchars($task->getTitle()) . '</h4>';
        $html .= '<p><span class="label label-' . $task->getPriorityClass() . '">' . Format::htmlchars($task->getPriorityLabel()) . '</span>';
        $html .= ' <span class="label label-' . $task->getStatusClass() . '">' . Format::htmlchars($task->getStatusLabel()) . '</span></p>';

        if ($task->getDescription()) {
            $html .= '<p>' . Format::htmlchars(mb_substr(strip_tags($task->getDescription()), 0, 200)) . '</p>';
        }

        $assignees = $task->getAssigneeNames();
        if ($assignees) {
            $html .= '<p><strong>Исполнители:</strong> ' . Format::htmlchars($assignees) . '</p>';
        }

        if ($task->getDeadline()) {
            $html .= '<p><strong>Дедлайн:</strong> ' . Format::htmlchars($task->getDeadline()) . '</p>';
        }

        $html .= '</div>';
        return $html;
    }

    function updateStatus($params) {
        global $thisuser;

        $id = isset($params['id']) ? intval($params['id']) : 0;
        $status = isset($params['status']) ? $params['status'] : '';

        if (!$id || !$status) {
            Http::response(400, 'Недостаточно параметров');
            return;
        }

        if (Task::updateStatus($id, $status, $thisuser->getId())) {
            Http::response(200, json_encode(array('success' => true)));
        } else {
            Http::response(500, 'Ошибка обновления статуса');
        }
    }

    function moveTask($params) {
        global $thisuser;

        $task_id = isset($params['task_id']) ? intval($params['task_id']) : 0;
        $list_id = isset($params['list_id']) ? intval($params['list_id']) : 0;
        $position = isset($params['position']) ? intval($params['position']) : null;

        if (!$task_id) {
            Http::response(400, 'Не указан ID задачи');
            return;
        }

        if (Task::moveToList($task_id, $list_id, $position)) {
            TaskActivity::log($task_id, $thisuser->getId(), 'moved', '');
            Http::response(200, json_encode(array('success' => true)));
        } else {
            Http::response(500, 'Ошибка перемещения');
        }
    }

    function quickCreate($params) {
        global $thisuser;

        $errors = array();
        $data = array(
            'title' => isset($params['title']) ? $params['title'] : '',
            'board_id' => isset($params['board_id']) ? intval($params['board_id']) : 0,
            'list_id' => isset($params['list_id']) ? intval($params['list_id']) : 0,
            'priority' => isset($params['priority']) ? $params['priority'] : 'normal',
            'created_by' => $thisuser->getId()
        );

        $id = Task::create($data, $errors);
        if ($id) {
            header('Content-Type: application/json');
            return json_encode(array('success' => true, 'task_id' => $id));
        } else {
            Http::response(400, json_encode(array('success' => false, 'errors' => $errors)));
        }
    }

    function search($params) {
        global $thisuser;

        $q = isset($params['q']) ? trim($params['q']) : '';
        if (strlen($q) < 2) return json_encode(array());

        $results = array();
        $sql = 'SELECT task_id, title, status, priority FROM ' . TASKS_TABLE
             . ' WHERE MATCH(title, description) AGAINST(' . db_input($q) . ')'
             . ' LIMIT 20';
        if (($res = db_query($sql)) && db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                $results[] = array(
                    'id' => $row['task_id'],
                    'title' => $row['title'],
                    'status' => $row['status'],
                    'priority' => $row['priority']
                );
            }
        }

        header('Content-Type: application/json');
        return json_encode($results);
    }

    function calendarEvents($params) {
        global $thisuser;

        $start = isset($params['start']) ? $params['start'] : '';
        $end = isset($params['end']) ? $params['end'] : '';
        $board_id = isset($params['board_id']) ? intval($params['board_id']) : 0;

        if (!$start || !$end) {
            header('Content-Type: application/json');
            return json_encode(array());
        }

        $events = array();

        $priorityColors = array(
            'urgent' => '#d9534f',
            'high' => '#f0ad4e',
            'normal' => '#5bc0de',
            'low' => '#999'
        );
        $priorityLabels = Task::getPriorityLabels();
        $statusLabels = Task::getStatusLabels();

        $sql = 'SELECT t.task_id, t.title, t.description, t.status, t.priority, t.start_date, t.end_date, t.deadline,'
             . ' b.board_name'
             . ' FROM ' . TASKS_TABLE . ' t'
             . ' LEFT JOIN ' . TASK_BOARDS_TABLE . ' b ON b.board_id=t.board_id'
             . ' WHERE t.parent_task_id IS NULL'
             . ' AND ('
             . '   (t.start_date IS NOT NULL AND t.start_date <= ' . db_input($end) . ' AND (t.end_date >= ' . db_input($start) . ' OR t.end_date IS NULL))'
             . '   OR (t.deadline IS NOT NULL AND t.deadline >= ' . db_input($start) . ' AND t.deadline <= ' . db_input($end) . ')'
             . '   OR (t.start_date IS NOT NULL AND t.start_date >= ' . db_input($start) . ' AND t.start_date <= ' . db_input($end) . ')'
             . ' )';

        if ($board_id) {
            $sql .= ' AND t.board_id=' . db_input($board_id);
        }

        $sql .= " AND t.status NOT IN ('cancelled')"
              . ' AND t.is_archived=0'
              . ' ORDER BY t.start_date ASC, t.deadline ASC';

        if (($res = db_query($sql)) && db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                $asql = 'SELECT CONCAT(s.firstname," ",s.lastname) as name'
                      . ' FROM ' . TASK_ASSIGNEES_TABLE . ' a'
                      . ' LEFT JOIN ' . TABLE_PREFIX . 'staff s ON s.staff_id=a.staff_id'
                      . ' WHERE a.task_id=' . db_input($row['task_id'])
                      . " AND a.role='assignee'";
                $aNames = array();
                if (($ares = db_query($asql)) && db_num_rows($ares)) {
                    while ($arow = db_fetch_array($ares)) {
                        $aNames[] = $arow['name'];
                    }
                }

                $eventStart = $row['start_date'] ? $row['start_date'] : $row['deadline'];
                $eventEnd = $row['end_date'] ? $row['end_date'] : ($row['deadline'] ? $row['deadline'] : $eventStart);

                if (!$eventStart) continue;

                $color = isset($priorityColors[$row['priority']]) ? $priorityColors[$row['priority']] : '#5bc0de';
                $pLabel = isset($priorityLabels[$row['priority']]) ? $priorityLabels[$row['priority']] : '';
                $sLabel = isset($statusLabels[$row['status']]) ? $statusLabels[$row['status']] : '';

                $event = array(
                    'id' => $row['task_id'],
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'start' => substr($eventStart, 0, 10),
                    'end' => substr($eventEnd, 0, 10),
                    'color' => $color,
                    'textColor' => ($row['priority'] == 'high') ? '#333' : '#fff',
                    'className' => 'priority-' . $row['priority'] . ($row['status'] == 'completed' ? ' status-completed' : ''),
                    'assignees' => implode(', ', $aNames),
                    'priorityLabel' => $pLabel,
                    'statusLabel' => $sLabel,
                    'boardName' => $row['board_name']
                );

                if ($eventEnd != $eventStart) {
                    $endTs = strtotime($eventEnd);
                    $event['end'] = date('Y-m-d', $endTs + 86400);
                }

                $events[] = $event;
            }
        }

        header('Content-Type: application/json');
        return json_encode($events);
    }

    function updateDates($params) {
        global $thisuser;

        $id = isset($params['id']) ? intval($params['id']) : 0;
        $start_date = isset($params['start_date']) ? $params['start_date'] : '';
        $end_date = isset($params['end_date']) ? $params['end_date'] : '';
        $deadline = isset($params['deadline']) ? $params['deadline'] : '';

        if (!$id) {
            Http::response(400, 'Не указан ID задачи');
            return;
        }

        $sets = array('updated=NOW()');
        if ($start_date) {
            $sets[] = 'start_date=' . db_input($start_date);
        }
        if ($end_date) {
            $sets[] = 'end_date=' . db_input($end_date);
        }
        if ($deadline) {
            $sets[] = 'deadline=' . db_input($deadline);
        }

        $sql = 'UPDATE ' . TASKS_TABLE . ' SET ' . implode(', ', $sets) . ' WHERE task_id=' . db_input($id);

        if (db_query($sql)) {
            TaskActivity::log($id, $thisuser->getId(), 'updated', 'Изменены даты');
            header('Content-Type: application/json');
            return json_encode(array('success' => true));
        } else {
            Http::response(500, 'Ошибка обновления');
        }
    }

    function addTimeLog($params) {
        global $thisuser;

        $task_id = isset($params['task_id']) ? intval($params['task_id']) : 0;
        $time_spent = isset($params['time_spent']) ? intval($params['time_spent']) : 0;
        $notes = isset($params['notes']) ? trim($params['notes']) : '';
        $log_date = isset($params['log_date']) && $params['log_date'] ? $params['log_date'] : date('Y-m-d H:i:s');

        $errors = array();
        $data = array(
            'task_id' => $task_id,
            'staff_id' => $thisuser->getId(),
            'time_spent' => $time_spent,
            'notes' => $notes,
            'log_date' => $log_date
        );

        $id = TaskTimeLog::create($data, $errors);
        if ($id) {
            TaskActivity::log($task_id, $thisuser->getId(), 'updated', 'Записано время: ' . TaskTimeLog::formatMinutes($time_spent));
            $total = TaskTimeLog::getTotalByTask($task_id);
            header('Content-Type: application/json');
            return json_encode(array(
                'success' => true,
                'log_id' => $id,
                'total' => $total,
                'total_formatted' => TaskTimeLog::formatMinutes($total),
                'staff_name' => $thisuser->getFirstname() . ' ' . $thisuser->getLastname(),
                'time_formatted' => TaskTimeLog::formatMinutes($time_spent)
            ));
        } else {
            Http::response(400, json_encode(array('success' => false, 'errors' => $errors)));
        }
    }

    function deleteTimeLog($params) {
        global $thisuser;

        $id = isset($params['id']) ? intval($params['id']) : 0;
        if (!$id) {
            Http::response(400, 'Не указан ID записи');
            return;
        }

        $log = TaskTimeLog::lookup($id);
        if (!$log) {
            Http::response(404, 'Запись не найдена');
            return;
        }

        $task_id = $log->getTaskId();
        if (TaskTimeLog::deleteLog($id)) {
            TaskActivity::log($task_id, $thisuser->getId(), 'updated', 'Удалена запись времени');
            $total = TaskTimeLog::getTotalByTask($task_id);
            header('Content-Type: application/json');
            return json_encode(array(
                'success' => true,
                'total' => $total,
                'total_formatted' => TaskTimeLog::formatMinutes($total)
            ));
        } else {
            Http::response(500, 'Ошибка удаления');
        }
    }

    function getTimeLogs($params) {
        $task_id = isset($params['task_id']) ? intval($params['task_id']) : 0;
        if (!$task_id) {
            Http::response(400, 'Не указана задача');
            return;
        }

        $logs = TaskTimeLog::getByTaskId($task_id);
        $total = TaskTimeLog::getTotalByTask($task_id);

        header('Content-Type: application/json');
        return json_encode(array(
            'logs' => $logs,
            'total' => $total,
            'total_formatted' => TaskTimeLog::formatMinutes($total)
        ));
    }

    function quickCreateSubtask($params) {
        global $thisuser;

        $errors = array();
        $parent_id = isset($params['parent_task_id']) ? intval($params['parent_task_id']) : 0;
        $title = isset($params['title']) ? trim($params['title']) : '';

        if (!$parent_id) {
            Http::response(400, 'Не указана родительская задача');
            return;
        }
        if (!$title) {
            Http::response(400, 'Введите название подзадачи');
            return;
        }

        $parent = Task::lookup($parent_id);
        if (!$parent) {
            Http::response(404, 'Родительская задача не найдена');
            return;
        }

        $data = array(
            'title' => $title,
            'board_id' => $parent->getBoardId(),
            'list_id' => $parent->getListId(),
            'parent_task_id' => $parent_id,
            'priority' => 'normal',
            'created_by' => $thisuser->getId()
        );

        $id = Task::create($data, $errors);
        if ($id) {
            TaskActivity::log($parent_id, $thisuser->getId(), 'updated', 'Добавлена подзадача: ' . $title);
            header('Content-Type: application/json');
            return json_encode(array('success' => true, 'task_id' => $id, 'title' => $title));
        } else {
            Http::response(400, json_encode(array('success' => false, 'errors' => $errors)));
        }
    }

    function toggleSubtaskStatus($params) {
        global $thisuser;

        $id = isset($params['id']) ? intval($params['id']) : 0;
        if (!$id) {
            Http::response(400, 'Не указан ID подзадачи');
            return;
        }

        $task = Task::lookup($id);
        if (!$task) {
            Http::response(404, 'Подзадача не найдена');
            return;
        }

        $newStatus = ($task->getStatus() == 'completed') ? 'open' : 'completed';
        if (Task::updateStatus($id, $newStatus, $thisuser->getId())) {
            header('Content-Type: application/json');
            return json_encode(array('success' => true, 'status' => $newStatus));
        } else {
            Http::response(500, 'Ошибка обновления статуса');
        }
    }

    function getBoardTasks($params) {
        global $thisuser;

        $board_id = isset($params['board_id']) ? intval($params['board_id']) : 0;
        if (!$board_id) {
            Http::response(400, 'Не указана доска');
            return;
        }

        $tasks = array();
        $sql = 'SELECT t.task_id, t.title, t.status, t.priority, t.list_id, t.position,'
             . ' t.deadline, t.start_date, t.end_date'
             . ' FROM ' . TASKS_TABLE . ' t'
             . ' WHERE t.board_id=' . db_input($board_id)
             . ' AND t.parent_task_id IS NULL'
             . ' ORDER BY t.position ASC, t.task_id ASC';

        if (($res = db_query($sql)) && db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                $tasks[] = $row;
            }
        }

        header('Content-Type: application/json');
        return json_encode($tasks);
    }

    function saveFilter($params) {
        global $thisuser;

        require_once(INCLUDE_DIR . 'class.taskfilter.php');

        $filter_name = isset($params['filter_name']) ? trim($params['filter_name']) : '';
        $filter_id = isset($params['filter_id']) ? intval($params['filter_id']) : 0;

        if (!$filter_name) {
            Http::response(400, json_encode(array('success' => false, 'error' => 'Введите название фильтра')));
            return;
        }

        $config = array();
        if (!empty($params['board_id'])) $config['board_id'] = intval($params['board_id']);
        if (!empty($params['status'])) {
            $config['status'] = is_array($params['status']) ? $params['status'] : array($params['status']);
        }
        if (!empty($params['priority'])) {
            $config['priority'] = is_array($params['priority']) ? $params['priority'] : array($params['priority']);
        }
        if (!empty($params['assignee'])) {
            $config['assignee'] = is_array($params['assignee']) ? $params['assignee'] : array($params['assignee']);
        }
        if (!empty($params['date_from'])) $config['date_from'] = $params['date_from'];
        if (!empty($params['date_to'])) $config['date_to'] = $params['date_to'];
        if (!empty($params['has_deadline'])) $config['has_deadline'] = 1;
        if (!empty($params['is_overdue'])) $config['is_overdue'] = 1;
        if (!empty($params['tags'])) {
            $config['tags'] = is_array($params['tags']) ? $params['tags'] : array($params['tags']);
        }
        if (!empty($params['search_text'])) $config['search_text'] = $params['search_text'];
        if (!empty($params['view'])) $config['view'] = $params['view'];

        $errors = array();
        $data = array(
            'staff_id' => $thisuser->getId(),
            'filter_name' => $filter_name,
            'filter_config' => $config
        );

        if ($filter_id) {
            $filter = TaskFilter::lookup($filter_id);
            if (!$filter || $filter->getStaffId() != $thisuser->getId()) {
                Http::response(403, json_encode(array('success' => false, 'error' => 'Доступ запрещён')));
                return;
            }
            $ok = TaskFilter::update($filter_id, $data, $errors);
            if ($ok) {
                header('Content-Type: application/json');
                return json_encode(array('success' => true, 'filter_id' => $filter_id));
            }
        } else {
            $id = TaskFilter::create($data, $errors);
            if ($id) {
                header('Content-Type: application/json');
                return json_encode(array('success' => true, 'filter_id' => $id));
            }
        }

        Http::response(400, json_encode(array('success' => false, 'errors' => $errors)));
    }

    function deleteFilter($params) {
        global $thisuser;

        require_once(INCLUDE_DIR . 'class.taskfilter.php');

        $filter_id = isset($params['filter_id']) ? intval($params['filter_id']) : 0;
        if (!$filter_id) {
            Http::response(400, json_encode(array('success' => false, 'error' => 'Не указан ID фильтра')));
            return;
        }

        $filter = TaskFilter::lookup($filter_id);
        if (!$filter || $filter->getStaffId() != $thisuser->getId()) {
            Http::response(403, json_encode(array('success' => false, 'error' => 'Доступ запрещён')));
            return;
        }

        if (TaskFilter::delete($filter_id)) {
            header('Content-Type: application/json');
            return json_encode(array('success' => true));
        } else {
            Http::response(500, json_encode(array('success' => false, 'error' => 'Ошибка удаления')));
        }
    }

    function setDefaultFilter($params) {
        global $thisuser;

        require_once(INCLUDE_DIR . 'class.taskfilter.php');

        $filter_id = isset($params['filter_id']) ? intval($params['filter_id']) : 0;
        $unset = isset($params['unset']) ? intval($params['unset']) : 0;

        if (!$filter_id) {
            Http::response(400, json_encode(array('success' => false, 'error' => 'Не указан ID фильтра')));
            return;
        }

        $filter = TaskFilter::lookup($filter_id);
        if (!$filter || $filter->getStaffId() != $thisuser->getId()) {
            Http::response(403, json_encode(array('success' => false, 'error' => 'Доступ запрещён')));
            return;
        }

        if ($unset) {
            $ok = TaskFilter::unsetDefault($filter_id, $thisuser->getId());
        } else {
            $ok = TaskFilter::setDefault($filter_id, $thisuser->getId());
        }

        if ($ok) {
            header('Content-Type: application/json');
            return json_encode(array('success' => true));
        } else {
            Http::response(500, json_encode(array('success' => false, 'error' => 'Ошибка обновления')));
        }
    }

    function updatePriority($params) {
        global $thisuser;

        $id = isset($params['id']) ? intval($params['id']) : 0;
        $priority = isset($params['priority']) ? $params['priority'] : '';

        if (!$id || !$priority) {
            Http::response(400, json_encode(array('error' => 'Не указаны обязательные параметры')));
            return;
        }

        if (Task::updatePriority($id, $priority, $thisuser->getId())) {
            header('Content-Type: application/json');
            return json_encode(array('success' => true));
        } else {
            Http::response(500, json_encode(array('error' => 'Ошибка изменения приоритета')));
        }
    }

    function addAssignee($params) {
        global $thisuser;

        $task_id = isset($params['task_id']) ? intval($params['task_id']) : 0;
        $staff_id = isset($params['staff_id']) ? intval($params['staff_id']) : 0;

        if (!$task_id || !$staff_id) {
            Http::response(400, json_encode(array('error' => 'Не указаны обязательные параметры')));
            return;
        }

        $sql = 'SELECT COUNT(*) as cnt FROM '.TASK_ASSIGNEES_TABLE.' WHERE task_id='.db_input($task_id).' AND staff_id='.db_input($staff_id);
        $res = db_query($sql);
        if ($res && db_num_rows($res) > 0) {
            $row = db_fetch_array($res);
            if ($row['cnt'] > 0) {
                Http::response(400, json_encode(array('error' => 'Исполнитель уже назначен')));
                return;
            }
        }

        $sql = 'SELECT CONCAT(firstname, " ", lastname) as name FROM '.STAFF_TABLE.' WHERE staff_id='.db_input($staff_id);
        $res = db_query($sql);
        $staff_name = '';
        if ($res && db_num_rows($res) > 0) {
            $row = db_fetch_array($res);
            $staff_name = $row['name'];
        }

        if (Task::addAssignee($task_id, $staff_id, 'assignee')) {
            header('Content-Type: application/json');
            return json_encode(array('success' => true, 'staff_name' => $staff_name));
        } else {
            Http::response(500, json_encode(array('error' => 'Ошибка добавления исполнителя')));
        }
    }

    function removeAssignee($params) {
        global $thisuser;

        $task_id = isset($params['task_id']) ? intval($params['task_id']) : 0;
        $staff_id = isset($params['staff_id']) ? intval($params['staff_id']) : 0;

        if (!$task_id || !$staff_id) {
            Http::response(400, json_encode(array('error' => 'Не указаны обязательные параметры')));
            return;
        }

        $sql = 'SELECT CONCAT(firstname, " ", lastname) as name FROM '.STAFF_TABLE.' WHERE staff_id='.db_input($staff_id);
        $res = db_query($sql);
        $staff_name = '';
        if ($res && db_num_rows($res) > 0) {
            $row = db_fetch_array($res);
            $staff_name = $row['name'];
        }

        if (Task::removeAssignee($task_id, $staff_id, 'assignee')) {
            header('Content-Type: application/json');
            return json_encode(array('success' => true));
        } else {
            Http::response(500, json_encode(array('error' => 'Ошибка удаления исполнителя')));
        }
    }

    function archiveTask($params) {
        global $thisuser;

        $id = isset($params['id']) ? intval($params['id']) : 0;

        if (!$id) {
            Http::response(400, json_encode(array('error' => 'ID задачи не указан')));
            return;
        }

        if (Task::archive($id, $thisuser->getId())) {
            header('Content-Type: application/json');
            return json_encode(array('success' => true));
        } else {
            Http::response(500, json_encode(array('error' => 'Ошибка архивирования задачи')));
        }
    }

    function unarchiveTask($params) {
        global $thisuser;

        $id = isset($params['id']) ? intval($params['id']) : 0;

        if (!$id) {
            Http::response(400, json_encode(array('error' => 'ID задачи не указан')));
            return;
        }

        if (Task::unarchive($id, $thisuser->getId())) {
            header('Content-Type: application/json');
            return json_encode(array('success' => true));
        } else {
            Http::response(500, json_encode(array('error' => 'Ошибка разархивирования задачи')));
        }
    }
}
?>
