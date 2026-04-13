<?php

if (!defined('THAPIV1INC')) die('Access Denied');

require_once(INCLUDE_DIR.'class.apicontroller.php');
require_once(INCLUDE_DIR.'class.task.php');
require_once(INCLUDE_DIR.'class.taskboard.php');

class TasksController extends ApiController {

    function index() {
        $this->checkMethod(array('GET'));
        $this->requirePermission('tasks:read');

        $pagination = $this->getPaginationParams();

        $where_parts = array();
        $joins = array();

        $board_id = $this->getQuery('board_id');
        if ($board_id) {
            $where_parts[] = 't.board_id=' . db_input($board_id);
        }

        $list_id = $this->getQuery('list_id');
        if ($list_id) {
            $where_parts[] = 't.list_id=' . db_input($list_id);
        }

        $status = $this->getQuery('status');
        if ($status) {
            $where_parts[] = 't.status=' . db_input($status);
        }

        $priority = $this->getQuery('priority');
        if ($priority) {
            $where_parts[] = 't.priority=' . db_input($priority);
        }

        $assignee_id = $this->getQuery('assignee_id');
        if ($assignee_id) {
            $joins[] = 'INNER JOIN ' . TASK_ASSIGNEES_TABLE . ' ta ON ta.task_id = t.task_id AND ta.staff_id=' . db_input($assignee_id);
        }

        $created_by = $this->getQuery('created_by');
        if ($created_by) {
            $where_parts[] = 't.created_by=' . db_input($created_by);
        }

        $parent_task_id = $this->getQuery('parent_task_id');
        if ($parent_task_id !== null) {
            if ($parent_task_id == 0) {
                $where_parts[] = 't.parent_task_id IS NULL OR t.parent_task_id=0';
            } else {
                $where_parts[] = 't.parent_task_id=' . db_input($parent_task_id);
            }
        }

        $is_archived = $this->getQuery('is_archived');
        if ($is_archived !== null) {
            $where_parts[] = 't.is_archived=' . db_input($is_archived ? 1 : 0);
        }

        $is_overdue = $this->getQuery('is_overdue');
        if ($is_overdue) {
            $where_parts[] = "t.deadline IS NOT NULL AND t.deadline < NOW() AND t.status NOT IN ('completed','cancelled')";
        }

        $deadline_from = $this->getQuery('deadline_from');
        if ($deadline_from) {
            $where_parts[] = 't.deadline >= ' . db_input($deadline_from);
        }

        $deadline_to = $this->getQuery('deadline_to');
        if ($deadline_to) {
            $where_parts[] = 't.deadline <= ' . db_input($deadline_to);
        }

        $search = $this->getQuery('search');
        if ($search) {
            $search_term = db_real_escape(str_replace(array('%','_'), array('\\%','\\_'), $search));
            $where_parts[] = "(t.title LIKE '%{$search_term}%' OR t.description LIKE '%{$search_term}%')";
        }

        $where = empty($where_parts) ? '' : ' WHERE ' . implode(' AND ', $where_parts);
        $join = empty($joins) ? '' : ' ' . implode(' ', $joins);

        $count_sql = 'SELECT COUNT(DISTINCT t.task_id) as total FROM ' . TASKS_TABLE . ' t' . $join . $where;
        $count_result = db_query($count_sql);
        $count_row = db_fetch_array($count_result);
        $total = $count_row['total'];

        $allowed_sort = array('task_id', 'title', 'priority', 'status', 'deadline', 'created', 'updated', 'position');
        $sort_params = $this->getSortParams($allowed_sort, 'created', 'DESC');

        $sql = 'SELECT DISTINCT
                    t.*,
                    b.board_name,
                    l.list_name,
                    CONCAT(s.firstname, " ", s.lastname) as creator_name
                FROM ' . TASKS_TABLE . ' t'
                . $join
                . ' LEFT JOIN ' . TASK_BOARDS_TABLE . ' b ON t.board_id = b.board_id'
                . ' LEFT JOIN ' . TASK_LISTS_TABLE . ' l ON t.list_id = l.list_id'
                . ' LEFT JOIN ' . STAFF_TABLE . ' s ON t.created_by = s.staff_id'
                . $where
                . ' ORDER BY t.' . $sort_params['sort'] . ' ' . $sort_params['order']
                . $this->buildLimitClause();

        $result = db_query($sql);

        if (!$result) {
            $this->handleDbError('fetch tasks');
        }

        $tasks = array();
        while ($row = db_fetch_array($result)) {
            $tasks[] = $this->formatTaskListItem($row);
        }

        $this->paginated($tasks, $total)->send();
    }

    function formatTaskListItem($row) {
        $assignees = array();
        $asql = 'SELECT a.staff_id, a.role, CONCAT(s.firstname, " ", s.lastname) as name
                 FROM ' . TASK_ASSIGNEES_TABLE . ' a
                 LEFT JOIN ' . STAFF_TABLE . ' s ON a.staff_id = s.staff_id
                 WHERE a.task_id=' . db_input($row['task_id']) . ' AND a.role="assignee"';
        $ares = db_query($asql);
        if ($ares && db_num_rows($ares)) {
            while ($arow = db_fetch_array($ares)) {
                $assignees[] = array(
                    'staff_id' => (int)$arow['staff_id'],
                    'name' => $arow['name']
                );
            }
        }

        $subtask_count = 0;
        $completed_subtask_count = 0;
        $ssql = 'SELECT COUNT(*) as total, SUM(CASE WHEN status="completed" THEN 1 ELSE 0 END) as completed
                 FROM ' . TASKS_TABLE . '
                 WHERE parent_task_id=' . db_input($row['task_id']);
        $sres = db_query($ssql);
        if ($sres && db_num_rows($sres)) {
            $srow = db_fetch_array($sres);
            $subtask_count = (int)$srow['total'];
            $completed_subtask_count = (int)$srow['completed'];
        }

        return array(
            'task_id' => (int)$row['task_id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'board' => array(
                'id' => (int)$row['board_id'],
                'name' => $row['board_name']
            ),
            'list' => $row['list_id'] ? array(
                'id' => (int)$row['list_id'],
                'name' => $row['list_name']
            ) : null,
            'parent_task_id' => $row['parent_task_id'] ? (int)$row['parent_task_id'] : null,
            'ticket_id' => $row['ticket_id'] ? (int)$row['ticket_id'] : null,
            'task_type' => $row['task_type'],
            'priority' => $row['priority'],
            'status' => $row['status'],
            'start_date' => $this->formatDate($row['start_date']),
            'end_date' => $this->formatDate($row['end_date']),
            'deadline' => $this->formatDate($row['deadline']),
            'time_estimate' => (int)$row['time_estimate'],
            'position' => (int)$row['position'],
            'is_archived' => $this->formatBool($row['is_archived']),
            'is_overdue' => ($row['deadline'] && strtotime($row['deadline']) < time() && !in_array($row['status'], array('completed', 'cancelled'))),
            'assignees' => $assignees,
            'subtasks' => array(
                'total' => $subtask_count,
                'completed' => $completed_subtask_count
            ),
            'created_by' => array(
                'id' => (int)$row['created_by'],
                'name' => $row['creator_name']
            ),
            'created_at' => $this->formatDate($row['created']),
            'updated_at' => $this->formatDate($row['updated']),
            'completed_at' => $this->formatDate($row['completed_date'])
        );
    }

    function show() {
        $this->checkMethod(array('GET'));
        $this->requirePermission('tasks:read');

        $task_id = $this->getPathParam('id');

        if (!$task_id || !is_numeric($task_id)) {
            ApiResponse::badRequest('Invalid task ID')->send();
        }

        $task = Task::lookup($task_id);
        if (!$task) {
            ApiResponse::notFound('Task not found')->send();
        }

        $info = $task->getInfo();

        $assignees = array();
        $asql = 'SELECT a.*, CONCAT(s.firstname, " ", s.lastname) as name, s.email
                 FROM ' . TASK_ASSIGNEES_TABLE . ' a
                 LEFT JOIN ' . STAFF_TABLE . ' s ON a.staff_id = s.staff_id
                 WHERE a.task_id=' . db_input($task_id);
        $ares = db_query($asql);
        if ($ares && db_num_rows($ares)) {
            while ($arow = db_fetch_array($ares)) {
                $assignees[] = array(
                    'staff_id' => (int)$arow['staff_id'],
                    'name' => $arow['name'],
                    'email' => $arow['email'],
                    'role' => $arow['role'],
                    'assigned_at' => $this->formatDate($arow['assigned_date'])
                );
            }
        }

        $subtasks = array();
        $ssql = 'SELECT * FROM ' . TASKS_TABLE . '
                 WHERE parent_task_id=' . db_input($task_id) . '
                 ORDER BY position ASC';
        $sres = db_query($ssql);
        if ($sres && db_num_rows($sres)) {
            while ($srow = db_fetch_array($sres)) {
                $subtasks[] = array(
                    'task_id' => (int)$srow['task_id'],
                    'title' => $srow['title'],
                    'status' => $srow['status'],
                    'priority' => $srow['priority'],
                    'position' => (int)$srow['position']
                );
            }
        }

        $time_logs = array();
        $tsql = 'SELECT t.*, CONCAT(s.firstname, " ", s.lastname) as logged_by_name
                 FROM ' . TASK_TIME_LOGS_TABLE . ' t
                 LEFT JOIN ' . STAFF_TABLE . ' s ON t.logged_by = s.staff_id
                 WHERE t.task_id=' . db_input($task_id) . '
                 ORDER BY t.logged_date DESC';
        $tres = db_query($tsql);
        if ($tres && db_num_rows($tres)) {
            while ($trow = db_fetch_array($tres)) {
                $time_logs[] = array(
                    'log_id' => (int)$trow['log_id'],
                    'minutes' => (int)$trow['minutes'],
                    'description' => $trow['description'],
                    'logged_by' => array(
                        'id' => (int)$trow['logged_by'],
                        'name' => $trow['logged_by_name']
                    ),
                    'logged_at' => $this->formatDate($trow['logged_date'])
                );
            }
        }

        $time_spent = 0;
        $tspsql = 'SELECT SUM(minutes) as total FROM ' . TASK_TIME_LOGS_TABLE . ' WHERE task_id=' . db_input($task_id);
        $tspres = db_query($tspsql);
        if ($tspres && db_num_rows($tspres)) {
            $tsprow = db_fetch_array($tspres);
            $time_spent = (int)$tsprow['total'];
        }

        $tags = array();
        $tagsql = 'SELECT t.tag_id, t.tag_name, t.color
                   FROM ' . TASK_TAGS_TABLE . ' t
                   INNER JOIN ' . TASK_TAG_ASSOC_TABLE . ' ta ON ta.tag_id = t.tag_id
                   WHERE ta.task_id=' . db_input($task_id);
        $tagres = db_query($tagsql);
        if ($tagres && db_num_rows($tagres)) {
            while ($tagrow = db_fetch_array($tagres)) {
                $tags[] = array(
                    'tag_id' => (int)$tagrow['tag_id'],
                    'name' => $tagrow['tag_name'],
                    'color' => $tagrow['color']
                );
            }
        }

        $data = array(
            'task_id' => (int)$info['task_id'],
            'title' => $info['title'],
            'description' => $info['description'],
            'board' => array(
                'id' => (int)$info['board_id'],
                'name' => $info['board_name']
            ),
            'list' => $info['list_id'] ? array(
                'id' => (int)$info['list_id'],
                'name' => $info['list_name']
            ) : null,
            'parent_task_id' => $info['parent_task_id'] ? (int)$info['parent_task_id'] : null,
            'ticket_id' => $info['ticket_id'] ? (int)$info['ticket_id'] : null,
            'task_type' => $info['task_type'],
            'priority' => $info['priority'],
            'status' => $info['status'],
            'start_date' => $this->formatDate($info['start_date']),
            'end_date' => $this->formatDate($info['end_date']),
            'deadline' => $this->formatDate($info['deadline']),
            'time_estimate' => (int)$info['time_estimate'],
            'time_spent' => $time_spent,
            'position' => (int)$info['position'],
            'is_archived' => $this->formatBool($info['is_archived']),
            'is_overdue' => $task->isOverdue(),
            'assignees' => $assignees,
            'subtasks' => $subtasks,
            'time_logs' => $time_logs,
            'tags' => $tags,
            'created_by' => array(
                'id' => (int)$info['created_by'],
                'name' => $info['creator_name']
            ),
            'created_at' => $this->formatDate($info['created']),
            'updated_at' => $this->formatDate($info['updated']),
            'completed_at' => $this->formatDate($info['completed_date'])
        );

        ApiResponse::success($data)->send();
    }

    function create() {
        $this->checkMethod(array('POST'));
        $this->requirePermission('tasks:write');

        $errors = array();

        $this->validateRequired(array('title', 'board_id'), $errors);

        $staff = $this->getAuthUser();
        if (!$staff) {
            ApiResponse::unauthorized('Authentication required')->send();
        }

        $data = array(
            'title' => $this->getInput('title'),
            'description' => $this->getInput('description'),
            'board_id' => $this->getInput('board_id'),
            'list_id' => $this->getInput('list_id'),
            'parent_task_id' => $this->getInput('parent_task_id'),
            'ticket_id' => $this->getInput('ticket_id'),
            'task_type' => $this->getInput('task_type', 'action'),
            'priority' => $this->getInput('priority', 'normal'),
            'status' => $this->getInput('status', 'open'),
            'start_date' => $this->getInput('start_date'),
            'end_date' => $this->getInput('end_date'),
            'deadline' => $this->getInput('deadline'),
            'time_estimate' => $this->getInput('time_estimate', 0),
            'assignees' => $this->getInput('assignees'),
            'created_by' => $staff->getId()
        );

        if ($data['board_id']) {
            $board = TaskBoard::lookup($data['board_id']);
            if (!$board) {
                $errors['board_id'] = 'Board not found';
            }
        }

        if ($data['list_id'] && $data['board_id']) {
            $lsql = 'SELECT list_id FROM ' . TASK_LISTS_TABLE . ' WHERE list_id=' . db_input($data['list_id']) . ' AND board_id=' . db_input($data['board_id']);
            $lres = db_query($lsql);
            if (!$lres || !db_num_rows($lres)) {
                $errors['list_id'] = 'List does not belong to the specified board';
            }
        }

        if ($errors) {
            ApiResponse::validationError($errors)->send();
        }

        $task_id = Task::create($data, $errors);

        if (!$task_id) {
            ApiResponse::validationError($errors)->send();
        }

        $task = Task::lookup($task_id);
        $info = $task->getInfo();

        $response_data = array(
            'task_id' => (int)$task_id,
            'title' => $info['title'],
            'board_id' => (int)$info['board_id'],
            'status' => $info['status'],
            'created_at' => $this->formatDate($info['created'])
        );

        ApiResponse::created($response_data, 'Task created successfully')->send();
    }

    function update() {
        $this->checkMethod(array('PUT', 'PATCH'));
        $this->requirePermission('tasks:write');

        $task_id = $this->getPathParam('id');

        if (!$task_id || !is_numeric($task_id)) {
            ApiResponse::badRequest('Invalid task ID')->send();
        }

        $task = Task::lookup($task_id);
        if (!$task) {
            ApiResponse::notFound('Task not found')->send();
        }

        $staff = $this->getAuthUser();
        if (!$staff) {
            ApiResponse::unauthorized('Authentication required')->send();
        }

        $info = $task->getInfo();
        $errors = array();

        $data = array(
            'title' => $this->getInput('title', $info['title']),
            'description' => $this->getInput('description', $info['description']),
            'board_id' => $this->getInput('board_id', $info['board_id']),
            'list_id' => $this->getInput('list_id', $info['list_id']),
            'parent_task_id' => $this->getInput('parent_task_id', $info['parent_task_id']),
            'ticket_id' => $this->getInput('ticket_id', $info['ticket_id']),
            'task_type' => $this->getInput('task_type', $info['task_type']),
            'priority' => $this->getInput('priority', $info['priority']),
            'status' => $this->getInput('status', $info['status']),
            'start_date' => $this->getInput('start_date', $info['start_date']),
            'end_date' => $this->getInput('end_date', $info['end_date']),
            'deadline' => $this->getInput('deadline', $info['deadline']),
            'time_estimate' => $this->getInput('time_estimate', $info['time_estimate']),
            'assignees' => $this->getInput('assignees'),
            'created_by' => $staff->getId()
        );

        if (!Task::update($task_id, $data, $errors)) {
            ApiResponse::validationError($errors)->send();
        }

        $task = Task::lookup($task_id);
        $info = $task->getInfo();

        $response_data = array(
            'task_id' => (int)$task_id,
            'title' => $info['title'],
            'status' => $info['status'],
            'updated_at' => $this->formatDate($info['updated'])
        );

        ApiResponse::success($response_data, 'Task updated successfully')->send();
    }

    function delete() {
        $this->checkMethod(array('DELETE'));
        $this->requirePermission('tasks:write');

        $task_id = $this->getPathParam('id');

        if (!$task_id || !is_numeric($task_id)) {
            ApiResponse::badRequest('Invalid task ID')->send();
        }

        $task = Task::lookup($task_id);
        if (!$task) {
            ApiResponse::notFound('Task not found')->send();
        }

        if (!Task::delete($task_id)) {
            ApiResponse::error('Failed to delete task')->send();
        }

        ApiResponse::success(null, 'Task deleted successfully')->send();
    }

    function updateStatus() {
        $this->checkMethod(array('PUT', 'PATCH'));
        $this->requirePermission('tasks:write');

        $task_id = $this->getPathParam('id');

        if (!$task_id || !is_numeric($task_id)) {
            ApiResponse::badRequest('Invalid task ID')->send();
        }

        $task = Task::lookup($task_id);
        if (!$task) {
            ApiResponse::notFound('Task not found')->send();
        }

        $errors = array();
        $this->validateRequired(array('status'), $errors);

        $status = $this->getInput('status');

        $valid_statuses = array('open', 'in_progress', 'blocked', 'completed', 'cancelled');
        if (!in_array($status, $valid_statuses)) {
            $errors['status'] = 'Invalid status. Must be one of: ' . implode(', ', $valid_statuses);
        }

        if ($errors) {
            ApiResponse::validationError($errors)->send();
        }

        $staff = $this->getAuthUser();
        $staff_id = $staff ? $staff->getId() : 0;

        if (!Task::updateStatus($task_id, $status, $staff_id)) {
            ApiResponse::error('Failed to update task status')->send();
        }

        $task = Task::lookup($task_id);
        $info = $task->getInfo();

        $response_data = array(
            'task_id' => (int)$task_id,
            'status' => $info['status'],
            'completed_at' => $this->formatDate($info['completed_date'])
        );

        ApiResponse::success($response_data, 'Task status updated successfully')->send();
    }

    function addAssignee() {
        $this->checkMethod(array('POST'));
        $this->requirePermission('tasks:write');

        $task_id = $this->getPathParam('id');

        if (!$task_id || !is_numeric($task_id)) {
            ApiResponse::badRequest('Invalid task ID')->send();
        }

        $task = Task::lookup($task_id);
        if (!$task) {
            ApiResponse::notFound('Task not found')->send();
        }

        $errors = array();
        $this->validateRequired(array('staff_id'), $errors);

        $staff_id = $this->getInput('staff_id');
        $role = $this->getInput('role', 'assignee');

        if ($errors) {
            ApiResponse::validationError($errors)->send();
        }

        $staff_check = 'SELECT staff_id FROM ' . STAFF_TABLE . ' WHERE staff_id=' . db_input($staff_id);
        if (!db_num_rows(db_query($staff_check))) {
            ApiResponse::badRequest('Staff member not found')->send();
        }

        if (!Task::addAssignee($task_id, $staff_id, $role)) {
            ApiResponse::error('Failed to add assignee')->send();
        }

        ApiResponse::created(array('task_id' => (int)$task_id, 'staff_id' => (int)$staff_id, 'role' => $role), 'Assignee added successfully')->send();
    }

    function removeAssignee() {
        $this->checkMethod(array('DELETE'));
        $this->requirePermission('tasks:write');

        $task_id = $this->getPathParam('id');
        $staff_id = $this->getPathParam('staff_id');

        if (!$task_id || !is_numeric($task_id)) {
            ApiResponse::badRequest('Invalid task ID')->send();
        }

        if (!$staff_id || !is_numeric($staff_id)) {
            ApiResponse::badRequest('Invalid staff ID')->send();
        }

        $task = Task::lookup($task_id);
        if (!$task) {
            ApiResponse::notFound('Task not found')->send();
        }

        $role = $this->getInput('role', 'assignee');

        if (!Task::removeAssignee($task_id, $staff_id, $role)) {
            ApiResponse::error('Failed to remove assignee')->send();
        }

        ApiResponse::success(null, 'Assignee removed successfully')->send();
    }

    function listBoards() {
        $this->checkMethod(array('GET'));
        $this->requirePermission('tasks:read');

        $pagination = $this->getPaginationParams();

        $where_parts = array();

        $board_type = $this->getQuery('board_type');
        if ($board_type) {
            $where_parts[] = 'board_type=' . db_input($board_type);
        }

        $dept_id = $this->getQuery('dept_id');
        if ($dept_id) {
            $where_parts[] = 'dept_id=' . db_input($dept_id);
        }

        $is_archived = $this->getQuery('is_archived');
        if ($is_archived !== null) {
            $where_parts[] = 'is_archived=' . db_input($is_archived ? 1 : 0);
        }

        $search = $this->getQuery('search');
        if ($search) {
            $search_term = db_real_escape(str_replace(array('%','_'), array('\\%','\\_'), $search));
            $where_parts[] = "board_name LIKE '%{$search_term}%'";
        }

        $where = empty($where_parts) ? '' : ' WHERE ' . implode(' AND ', $where_parts);

        $count_sql = 'SELECT COUNT(*) as total FROM ' . TASK_BOARDS_TABLE . $where;
        $count_result = db_query($count_sql);
        $count_row = db_fetch_array($count_result);
        $total = $count_row['total'];

        $sql = 'SELECT
                    b.*,
                    d.dept_name,
                    CONCAT(s.firstname, " ", s.lastname) as creator_name
                FROM ' . TASK_BOARDS_TABLE . ' b
                LEFT JOIN ' . DEPT_TABLE . ' d ON b.dept_id = d.dept_id
                LEFT JOIN ' . STAFF_TABLE . ' s ON b.created_by = s.staff_id'
                . $where
                . ' ORDER BY b.board_name ASC'
                . $this->buildLimitClause();

        $result = db_query($sql);

        if (!$result) {
            $this->handleDbError('fetch taskboards');
        }

        $boards = array();
        while ($row = db_fetch_array($result)) {
            $boards[] = $this->formatTaskboardListItem($row);
        }

        $this->paginated($boards, $total)->send();
    }

    function formatTaskboardListItem($row) {
        $task_count = 0;
        $open_task_count = 0;
        $csql = 'SELECT COUNT(*) as total, SUM(CASE WHEN status NOT IN ("completed","cancelled") THEN 1 ELSE 0 END) as open
                 FROM ' . TASKS_TABLE . '
                 WHERE board_id=' . db_input($row['board_id']);
        $cres = db_query($csql);
        if ($cres && db_num_rows($cres)) {
            $crow = db_fetch_array($cres);
            $task_count = (int)$crow['total'];
            $open_task_count = (int)$crow['open'];
        }

        return array(
            'board_id' => (int)$row['board_id'],
            'name' => $row['board_name'],
            'type' => $row['board_type'],
            'department' => $row['dept_id'] ? array(
                'id' => (int)$row['dept_id'],
                'name' => $row['dept_name']
            ) : null,
            'description' => $row['description'],
            'color' => $row['color'],
            'is_archived' => $this->formatBool($row['is_archived']),
            'tasks' => array(
                'total' => $task_count,
                'open' => $open_task_count
            ),
            'created_by' => array(
                'id' => (int)$row['created_by'],
                'name' => $row['creator_name']
            ),
            'created_at' => $this->formatDate($row['created']),
            'updated_at' => $this->formatDate($row['updated'])
        );
    }

    function showBoard() {
        $this->checkMethod(array('GET'));
        $this->requirePermission('tasks:read');

        $board_id = $this->getPathParam('id');

        if (!$board_id || !is_numeric($board_id)) {
            ApiResponse::badRequest('Invalid board ID')->send();
        }

        $board = TaskBoard::lookup($board_id);
        if (!$board) {
            ApiResponse::notFound('Taskboard not found')->send();
        }

        $info = $board->getInfo();

        $lists = array();
        $lsql = 'SELECT * FROM ' . TASK_LISTS_TABLE . '
                 WHERE board_id=' . db_input($board_id) . ' AND is_archived=0
                 ORDER BY list_order ASC';
        $lres = db_query($lsql);
        if ($lres && db_num_rows($lres)) {
            while ($lrow = db_fetch_array($lres)) {
                $tcountsql = 'SELECT COUNT(*) as count FROM ' . TASKS_TABLE . ' WHERE list_id=' . db_input($lrow['list_id']);
                $tcountres = db_query($tcountsql);
                $task_count = 0;
                if ($tcountres && db_num_rows($tcountres)) {
                    $tcountrow = db_fetch_array($tcountres);
                    $task_count = (int)$tcountrow['count'];
                }

                $lists[] = array(
                    'list_id' => (int)$lrow['list_id'],
                    'name' => $lrow['list_name'],
                    'status' => $lrow['status'],
                    'order' => (int)$lrow['list_order'],
                    'task_count' => $task_count
                );
            }
        }

        $stats_sql = 'SELECT
                        COUNT(*) as total,
                        SUM(CASE WHEN status="open" THEN 1 ELSE 0 END) as open,
                        SUM(CASE WHEN status="in_progress" THEN 1 ELSE 0 END) as in_progress,
                        SUM(CASE WHEN status="blocked" THEN 1 ELSE 0 END) as blocked,
                        SUM(CASE WHEN status="completed" THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN status="cancelled" THEN 1 ELSE 0 END) as cancelled
                      FROM ' . TASKS_TABLE . '
                      WHERE board_id=' . db_input($board_id);
        $stats_result = db_query($stats_sql);
        $stats_row = db_fetch_array($stats_result);

        $data = array(
            'board_id' => (int)$info['board_id'],
            'name' => $info['board_name'],
            'type' => $info['board_type'],
            'department' => $info['dept_id'] ? array(
                'id' => (int)$info['dept_id']
            ) : null,
            'description' => $info['description'],
            'color' => $info['color'],
            'is_archived' => $this->formatBool($info['is_archived']),
            'lists' => $lists,
            'statistics' => array(
                'total' => (int)$stats_row['total'],
                'open' => (int)$stats_row['open'],
                'in_progress' => (int)$stats_row['in_progress'],
                'blocked' => (int)$stats_row['blocked'],
                'completed' => (int)$stats_row['completed'],
                'cancelled' => (int)$stats_row['cancelled']
            ),
            'created_by' => (int)$info['created_by'],
            'created_at' => $this->formatDate($info['created']),
            'updated_at' => $this->formatDate($info['updated'])
        );

        ApiResponse::success($data)->send();
    }

    function createBoard() {
        $this->checkMethod(array('POST'));
        $this->requirePermission('admin:*');

        $errors = array();

        $this->validateRequired(array('board_name', 'board_type'), $errors);

        $staff = $this->getAuthUser();
        if (!$staff) {
            ApiResponse::unauthorized('Authentication required')->send();
        }

        $data = array(
            'board_name' => $this->getInput('board_name'),
            'board_type' => $this->getInput('board_type'),
            'dept_id' => $this->getInput('dept_id'),
            'description' => $this->getInput('description'),
            'color' => $this->getInput('color', '#3498db'),
            'created_by' => $staff->getId()
        );

        if ($errors) {
            ApiResponse::validationError($errors)->send();
        }

        $board_id = TaskBoard::create($data, $errors);

        if (!$board_id) {
            ApiResponse::validationError($errors)->send();
        }

        $board = TaskBoard::lookup($board_id);
        $info = $board->getInfo();

        $response_data = array(
            'board_id' => (int)$board_id,
            'name' => $info['board_name'],
            'type' => $info['board_type'],
            'created_at' => $this->formatDate($info['created'])
        );

        ApiResponse::created($response_data, 'Taskboard created successfully')->send();
    }

    function updateBoard() {
        $this->checkMethod(array('PUT', 'PATCH'));
        $this->requirePermission('admin:*');

        $board_id = $this->getPathParam('id');

        if (!$board_id || !is_numeric($board_id)) {
            ApiResponse::badRequest('Invalid board ID')->send();
        }

        $board = TaskBoard::lookup($board_id);
        if (!$board) {
            ApiResponse::notFound('Taskboard not found')->send();
        }

        $info = $board->getInfo();
        $errors = array();

        $data = array(
            'board_name' => $this->getInput('board_name', $info['board_name']),
            'board_type' => $this->getInput('board_type', $info['board_type']),
            'dept_id' => $this->getInput('dept_id', $info['dept_id']),
            'description' => $this->getInput('description', $info['description']),
            'color' => $this->getInput('color', $info['color'])
        );

        if (!TaskBoard::update($board_id, $data, $errors)) {
            ApiResponse::validationError($errors)->send();
        }

        $board = TaskBoard::lookup($board_id);
        $info = $board->getInfo();

        $response_data = array(
            'board_id' => (int)$board_id,
            'name' => $info['board_name'],
            'updated_at' => $this->formatDate($info['updated'])
        );

        ApiResponse::success($response_data, 'Taskboard updated successfully')->send();
    }

    function deleteBoard() {
        $this->checkMethod(array('DELETE'));
        $this->requirePermission('admin:*');

        $board_id = $this->getPathParam('id');

        if (!$board_id || !is_numeric($board_id)) {
            ApiResponse::badRequest('Invalid board ID')->send();
        }

        $board = TaskBoard::lookup($board_id);
        if (!$board) {
            ApiResponse::notFound('Taskboard not found')->send();
        }

        if (!TaskBoard::delete($board_id)) {
            ApiResponse::error('Failed to delete taskboard')->send();
        }

        ApiResponse::success(null, 'Taskboard deleted successfully')->send();
    }
}

?>
