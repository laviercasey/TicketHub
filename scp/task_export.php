<?php
require('staff.inc.php');
require_once(INCLUDE_DIR.'class.task.php');
require_once(INCLUDE_DIR.'class.tasktimelog.php');

$view = isset($_GET['view']) ? $_GET['view'] : '';
$filter_board = isset($_GET['board_id']) ? intval($_GET['board_id']) : 0;
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_priority = isset($_GET['priority']) ? $_GET['priority'] : '';
$filter_assignee = isset($_GET['assignee']) ? intval($_GET['assignee']) : 0;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

$qselect = 'SELECT t.*, b.board_name, l.list_name';
$qfrom = ' FROM ' . TASKS_TABLE . ' t'
       . ' LEFT JOIN ' . TASK_BOARDS_TABLE . ' b ON b.board_id=t.board_id'
       . ' LEFT JOIN ' . TASK_LISTS_TABLE . ' l ON l.list_id=t.list_id';
$qwhere = ' WHERE t.parent_task_id IS NULL';

if ($view == 'mytasks') {
    $qfrom .= ' JOIN ' . TASK_ASSIGNEES_TABLE . ' a ON a.task_id=t.task_id';
    $qwhere .= ' AND a.staff_id=' . db_input($thisuser->getId()) . " AND a.role='assignee'";
    if (!$filter_status) {
        $qwhere .= " AND t.status NOT IN ('completed','cancelled')";
    }
} elseif ($view == 'overdue') {
    $qwhere .= " AND t.deadline IS NOT NULL AND t.deadline < NOW() AND t.status NOT IN ('completed','cancelled')";
} elseif ($view == 'unassigned') {
    $qfrom .= ' LEFT JOIN ' . TASK_ASSIGNEES_TABLE . ' a2 ON a2.task_id=t.task_id AND a2.role=\'assignee\'';
    $qwhere .= ' AND a2.assignment_id IS NULL';
    $qwhere .= " AND t.status NOT IN ('completed','cancelled')";
}

if ($filter_board) {
    $qwhere .= ' AND t.board_id=' . db_input($filter_board);
}
if ($filter_status) {
    $qwhere .= ' AND t.status=' . db_input($filter_status);
}
if ($filter_priority) {
    $qwhere .= ' AND t.priority=' . db_input($filter_priority);
}
if ($filter_assignee) {
    if ($view != 'mytasks') {
        $qfrom .= ' JOIN ' . TASK_ASSIGNEES_TABLE . ' af ON af.task_id=t.task_id';
        $qwhere .= ' AND af.staff_id=' . db_input($filter_assignee) . " AND af.role='assignee'";
    }
}
if ($search) {
    $qwhere .= ' AND MATCH(t.title, t.description) AGAINST(' . db_input($search) . ')';
}

$query = $qselect . $qfrom . $qwhere . ' GROUP BY t.task_id ORDER BY t.created DESC LIMIT 50000';
$result = db_query($query);

$priorityLabels = Task::getPriorityLabels();
$statusLabels = Task::getStatusLabels();

$filename = 'tasks_export_' . date('Ymd') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

$csvHeaders = array('ID');
$csvHeaders[] = 'Название';
$csvHeaders[] = 'Доска';
$csvHeaders[] = 'Список';
$csvHeaders[] = 'Статус';
$csvHeaders[] = 'Приоритет';
$csvHeaders[] = 'Исполнители';
$csvHeaders[] = 'Дедлайн';
$csvHeaders[] = 'Создана';
$csvHeaders[] = 'Затрачено времени';
fputcsv($out, $csvHeaders, ';');

if ($result && db_num_rows($result)) {
    while ($row = db_fetch_array($result)) {
        $assigneeSql = 'SELECT CONCAT(s.firstname," ",s.lastname) as name'
                     . ' FROM ' . TASK_ASSIGNEES_TABLE . ' a'
                     . ' LEFT JOIN ' . TABLE_PREFIX . 'staff s ON s.staff_id=a.staff_id'
                     . ' WHERE a.task_id=' . db_input($row['task_id'])
                     . " AND a.role='assignee'";
        $aNames = array();
        if (($ares = db_query($assigneeSql)) && db_num_rows($ares)) {
            while ($arow = db_fetch_array($ares)) {
                $aNames[] = $arow['name'];
            }
        }

        $timeTotal = TaskTimeLog::getTotalByTask($row['task_id']);

        $statusLabel = isset($statusLabels[$row['status']]) ? $statusLabels[$row['status']] : $row['status'];
        $priorityLabel = isset($priorityLabels[$row['priority']]) ? $priorityLabels[$row['priority']] : $row['priority'];

        fputcsv($out, array(
            $row['task_id'],
            $row['title'],
            $row['board_name'],
            $row['list_name'],
            $statusLabel,
            $priorityLabel,
            implode(', ', $aNames),
            $row['deadline'] ? date('d.m.Y', strtotime($row['deadline'])) : '',
            date('d.m.Y', strtotime($row['created'])),
            $timeTotal > 0 ? TaskTimeLog::formatMinutes($timeTotal) : ''
        ), ';');
    }
}

fclose($out);
exit;
?>
