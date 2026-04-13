<?php
require('staff.inc.php');
require_once(INCLUDE_DIR.'class.task.php');
require_once(INCLUDE_DIR.'class.taskboard.php');
require_once(INCLUDE_DIR.'class.taskpermission.php');
require_once(INCLUDE_DIR.'class.taskcomment.php');
require_once(INCLUDE_DIR.'class.taskattachment.php');
require_once(INCLUDE_DIR.'class.taskactivity.php');
require_once(INCLUDE_DIR.'class.tasktag.php');
require_once(INCLUDE_DIR.'class.taskcustomfield.php');
require_once(INCLUDE_DIR.'class.tasktimelog.php');
require_once(INCLUDE_DIR.'class.dept.php');

$page = '';
$task = null;
$board = null;

$board_id = isset($_REQUEST['board_id']) ? intval($_REQUEST['board_id']) : 0;
if ($board_id) {
    $board = TaskBoard::lookup($board_id);
    if (!$board)
        $errors['err'] = 'Доска не найдена #' . $board_id;
}

$viewMode = isset($_REQUEST['view']) && $_REQUEST['view'];
if (($id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : (isset($_POST['id']) ? $_POST['id'] : 0))) && is_numeric($id)) {
    $task = Task::lookup($id);
    if (!$task)
        $errors['err'] = 'Задача не найдена #' . $id;
    elseif ($viewMode)
        $page = 'task-view.inc.php';
    elseif (!isset($_REQUEST['a']) || $_REQUEST['a'] != 'add')
        $page = 'task.inc.php';
}

if ($_POST):
    $errors = array();
    if (!Misc::validateCSRFToken($_POST['csrf_token'])) {
        $errors['err'] = 'Ошибка проверки безопасности. Попробуйте снова.';
    } else {
    switch (strtolower($_POST['a'])):
    case 'add':
    case 'update':
        if (!$_POST['id'] && $_POST['a'] == 'update')
            $errors['err'] = 'Отсутствует ID задачи';

        $perm_board_id = ($_POST['a'] == 'update' && $task) ? $task->getBoardId() : intval($_POST['board_id']);
        if ($perm_board_id && !TaskPermission::canEdit($perm_board_id, $thisuser->getId())) {
            $errors['err'] = 'Недостаточно прав для изменения задач этой доски';
        }

        $data = array(
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'board_id' => $_POST['board_id'],
            'list_id' => $_POST['list_id'] ? $_POST['list_id'] : null,
            'parent_task_id' => $_POST['parent_task_id'] ? $_POST['parent_task_id'] : null,
            'ticket_id' => $_POST['ticket_id'] ? $_POST['ticket_id'] : null,
            'task_type' => $_POST['task_type'],
            'priority' => $_POST['priority'],
            'status' => $_POST['status'],
            'start_date' => !empty($_POST['start_date']) ? date('Y-m-d', strtotime($_POST['start_date'])) : null,
            'end_date' => !empty($_POST['end_date']) ? date('Y-m-d', strtotime($_POST['end_date'])) : null,
            'deadline' => !empty($_POST['deadline']) ? date('Y-m-d', strtotime($_POST['deadline'])) : null,
            'time_estimate' => $_POST['time_estimate'] ? intval($_POST['time_estimate']) : 0,
            'created_by' => $thisuser->getId()
        );

        $assignees = array();
        foreach ($_POST as $key => $val) {
            if (preg_match('/^assignee_\d+$/', $key) && intval($val)) {
                $assignees[] = intval($val);
            }
        }
        $data['assignees'] = $assignees;

        if (!$errors) {
            if ($_POST['a'] == 'add') {
                $taskId = Task::create($data, $errors);
                if ($taskId) {
                    $tagIds = isset($_POST['tags']) && is_array($_POST['tags']) ? $_POST['tags'] : array();
                    TaskTag::setTaskTags($taskId, $tagIds);
                    $cfValues = array();
                    foreach ($_POST as $pk => $pv) {
                        if (substr($pk, 0, 3) == 'cf_') {
                            $fid = intval(substr($pk, 3));
                            if ($fid) $cfValues[$fid] = $pv;
                        }
                    }
                    if (count($cfValues) > 0) {
                        TaskCustomField::setTaskValues($taskId, $cfValues);
                    }
                    $msg = 'Задача успешно создана';
                    header('Location: tasks.php?id=' . $taskId . '&msg=' . urlencode($msg));
                    exit;
                }
            } elseif ($_POST['a'] == 'update') {
                if (Task::update($_POST['id'], $data, $errors)) {
                    $msg = 'Задача успешно обновлена';
                    $tagIds = isset($_POST['tags']) && is_array($_POST['tags']) ? $_POST['tags'] : array();
                    TaskTag::setTaskTags($_POST['id'], $tagIds);
                    $cfValues = array();
                    foreach ($_POST as $pk => $pv) {
                        if (substr($pk, 0, 3) == 'cf_') {
                            $fid = intval(substr($pk, 3));
                            if ($fid) $cfValues[$fid] = $pv;
                        }
                    }
                    if (count($cfValues) > 0) {
                        TaskCustomField::setTaskValues($_POST['id'], $cfValues);
                    }
                    if (isset($_FILES['task_attachment']) && $_FILES['task_attachment']['error'] != UPLOAD_ERR_NO_FILE) {
                        $file_errors = array();
                        if (TaskAttachment::upload($_POST['id'], $_FILES['task_attachment'], $thisuser->getId(), $file_errors)) {
                            $msg .= '. Файл загружен';
                        } elseif ($file_errors['file']) {
                            $errors['err'] = $file_errors['file'];
                        }
                    }
                    $task = Task::lookup($_POST['id']);
                }
            }
        }

        if ($errors && !$errors['err'])
            $errors['err'] = 'Исправьте ошибки и попробуйте снова';

        break;

    case 'delete_attachment':
        $att_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;
        $att_task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        $success = false;
        if ($att_id && $att_task_id) {
            if (TaskAttachment::delete($att_id)) {
                TaskActivity::log($att_task_id, $thisuser->getId(), 'updated', 'Удалено вложение');
                $msg = 'Вложение удалено';
                $success = true;
            }
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            header('Content-Type: application/json');
            echo json_encode(array('success' => $success));
            exit;
        }
        $redirect = 'tasks.php?id=' . $att_task_id;
        if (isset($_POST['return_view'])) $redirect .= '&view=1';
        if ($msg) $redirect .= '&msg=' . urlencode($msg);
        header('Location: ' . $redirect);
        exit;

    case 'upload_attachment':
        $task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;

        if (!$task_id) {
            header('Content-Type: application/json');
            echo json_encode(array('error' => 'ID задачи не указан'));
            exit;
        }

        if (!isset($_FILES['file']) || !$_FILES['file']['name']) {
            header('Content-Type: application/json');
            echo json_encode(array('error' => 'Файл не выбран'));
            exit;
        }

        require_once(INCLUDE_DIR.'class.taskattachment.php');
        $file_errors = array();
        $att_id = TaskAttachment::upload($task_id, $_FILES['file'], $thisuser->getId(), $file_errors);

        if ($att_id) {
            TaskActivity::log($task_id, $thisuser->getId(), 'updated', 'Добавлено вложение: '.Format::htmlchars($_FILES['file']['name']));
            header('Content-Type: application/json');
            echo json_encode(array('success' => true, 'attachment_id' => $att_id));
        } else {
            $err_msg = !empty($file_errors['file']) ? $file_errors['file'] : 'Ошибка загрузки файла';
            header('Content-Type: application/json');
            echo json_encode(array('error' => $err_msg));
        }
        exit;

    case 'process':
        if (!$_POST['tids'] || !is_array($_POST['tids']))
            $errors['err'] = 'Выберите хотя бы одну задачу';
        else {
            $msg = '';
            $ids = array_map('intval', $_POST['tids']);
            $selected = count($ids);

            if (isset($_POST['complete'])) {
                $count = 0;
                foreach ($ids as $tid) {
                    if (Task::updateStatus($tid, 'completed', $thisuser->getId())) $count++;
                }
                $msg = "$count из $selected задач завершено";
            } elseif (isset($_POST['delete'])) {
                $count = 0;
                foreach ($ids as $tid) {
                    if (Task::delete($tid)) $count++;
                }
                $msg = "$count из $selected задач удалено";
            } elseif (isset($_POST['change_status']) && $_POST['bulk_status']) {
                $count = 0;
                foreach ($ids as $tid) {
                    if (Task::updateStatus($tid, $_POST['bulk_status'], $thisuser->getId())) $count++;
                }
                $msg = "Статус обновлен для $count из $selected задач";
            } elseif (isset($_POST['change_priority']) && $_POST['bulk_priority']) {
                $count = 0;
                foreach ($ids as $tid) {
                    $sql = 'UPDATE ' . TASKS_TABLE . ' SET priority=' . db_input($_POST['bulk_priority'])
                          . ', updated=NOW() WHERE task_id=' . db_input($tid);
                    if (db_query($sql)) $count++;
                }
                $msg = "Приоритет обновлен для $count из $selected задач";
            }

            if (!$msg)
                $errors['err'] = 'Ошибка выполнения. Попробуйте снова.';
        }
        break;

    case 'add_timelog':
        $tl_task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        $tl_hours = isset($_POST['tl_hours']) ? intval($_POST['tl_hours']) : 0;
        $tl_minutes = isset($_POST['tl_minutes']) ? intval($_POST['tl_minutes']) : 0;
        $tl_total = $tl_hours * 60 + $tl_minutes;

        if ($tl_total <= 0) {
            $errors['err'] = 'Укажите время (часы и/или минуты)';
        } elseif (!$tl_task_id) {
            $errors['err'] = 'Не указана задача';
        } else {
            $tl_date = date('Y-m-d');
            if (!empty($_POST['tl_date'])) {
                $parsed = strtotime($_POST['tl_date']);
                if ($parsed) $tl_date = date('Y-m-d', $parsed);
            }
            $tl_date .= ' ' . date('H:i:s');
            $tl_data = array(
                'task_id' => $tl_task_id,
                'staff_id' => $thisuser->getId(),
                'time_spent' => $tl_total,
                'notes' => isset($_POST['tl_notes']) ? trim($_POST['tl_notes']) : '',
                'log_date' => $tl_date
            );
            $tl_errors = array();
            $tl_id = TaskTimeLog::create($tl_data, $tl_errors);
            if ($tl_id) {
                TaskActivity::log($tl_task_id, $thisuser->getId(), 'updated', 'Записано время: ' . TaskTimeLog::formatMinutes($tl_total));
                $msg = 'Время записано: ' . TaskTimeLog::formatMinutes($tl_total);
                header('Location: tasks.php?id=' . $tl_task_id . '&msg=' . urlencode($msg));
                exit;
            } else {
                $errors['err'] = $tl_errors['err'] ?? 'Ошибка записи времени';
            }
        }
        if ($tl_task_id) {
            $task = Task::lookup($tl_task_id);
            $page = 'task-view.inc.php';
        }
        break;

    case 'update_status':
        $us_task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        $us_status = isset($_POST['status']) ? $_POST['status'] : '';
        if ($us_task_id && $us_status) {
            if (Task::updateStatus($us_task_id, $us_status, $thisuser->getId())) {
                $msg = 'Статус обновлён';
            } else {
                $errors['err'] = 'Ошибка обновления статуса';
            }
        } else {
            $errors['err'] = 'Не указаны параметры';
        }
        header('Location: tasks.php?id=' . $us_task_id . '&view=1&msg=' . urlencode($msg ?? ''));
        exit;

    case 'update_priority':
        $up_task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        $up_priority = isset($_POST['priority']) ? $_POST['priority'] : '';
        if ($up_task_id && $up_priority) {
            if (Task::updatePriority($up_task_id, $up_priority, $thisuser->getId())) {
                $msg = 'Приоритет обновлён';
            } else {
                $errors['err'] = 'Ошибка обновления приоритета';
            }
        } else {
            $errors['err'] = 'Не указаны параметры';
        }
        header('Location: tasks.php?id=' . $up_task_id . '&view=1&msg=' . urlencode($msg ?? ''));
        exit;

    case 'archive_task':
        $ar_task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        if ($ar_task_id && Task::archive($ar_task_id, $thisuser->getId())) {
            $msg = 'Задача архивирована';
        } else {
            $errors['err'] = 'Ошибка архивирования';
        }
        header('Location: tasks.php?msg=' . urlencode($msg ?? ''));
        exit;

    case 'unarchive_task':
        $ua_task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        if ($ua_task_id && Task::unarchive($ua_task_id, $thisuser->getId())) {
            $msg = 'Задача разархивирована';
        } else {
            $errors['err'] = 'Ошибка разархивирования';
        }
        header('Location: tasks.php?id=' . $ua_task_id . '&view=1&msg=' . urlencode($msg ?? ''));
        exit;

    case 'add_assignee':
        $aa_task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        $aa_staff_id = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;
        if ($aa_task_id && $aa_staff_id) {
            if (Task::addAssignee($aa_task_id, $aa_staff_id, 'assignee')) {
                TaskActivity::log($aa_task_id, $thisuser->getId(), 'updated', 'Добавлен исполнитель');
                $msg = 'Исполнитель добавлен';
            } else {
                $errors['err'] = 'Ошибка добавления исполнителя';
            }
        }
        header('Location: tasks.php?id=' . $aa_task_id . '&view=1&msg=' . urlencode($msg ?? ''));
        exit;

    case 'remove_assignee':
        $ra_task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        $ra_staff_id = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;
        if ($ra_task_id && $ra_staff_id) {
            if (Task::removeAssignee($ra_task_id, $ra_staff_id, 'assignee')) {
                TaskActivity::log($ra_task_id, $thisuser->getId(), 'updated', 'Удалён исполнитель');
                $msg = 'Исполнитель удалён';
            }
        }
        header('Location: tasks.php?id=' . $ra_task_id . '&view=1&msg=' . urlencode($msg ?? ''));
        exit;

    case 'add_comment':
        $ac_task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        $ac_text = isset($_POST['comment_text']) ? trim($_POST['comment_text']) : '';
        $ac_return = isset($_POST['return_view']) ? '&view=1' : '';
        if ($ac_task_id && $ac_text) {
            require_once(INCLUDE_DIR.'class.taskcomment.php');
            $c_data = array('task_id' => $ac_task_id, 'staff_id' => $thisuser->getId(), 'comment_text' => $ac_text);
            $c_errors = array();
            if (TaskComment::create($c_data, $c_errors)) {
                $msg = 'Комментарий добавлен';
            } else {
                $errors['err'] = isset($c_errors['err']) ? $c_errors['err'] : 'Ошибка добавления комментария';
            }
        } else {
            $errors['err'] = 'Введите текст комментария';
        }
        header('Location: tasks.php?id=' . $ac_task_id . $ac_return . '&msg=' . urlencode($msg ? $msg : ''));
        exit;

    case 'delete_comment':
        $dc_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
        $dc_task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        $dc_return = isset($_POST['return_view']) ? '&view=1' : '';
        if ($dc_id) {
            require_once(INCLUDE_DIR.'class.taskcomment.php');
            if (TaskComment::delete($dc_id)) {
                $msg = 'Комментарий удалён';
            }
        }
        header('Location: tasks.php?id=' . $dc_task_id . $dc_return . '&msg=' . urlencode($msg ? $msg : ''));
        exit;

    case 'toggle_subtask':
        $ts_id = isset($_POST['subtask_id']) ? intval($_POST['subtask_id']) : 0;
        $ts_task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        if ($ts_id) {
            $sub = Task::lookup($ts_id);
            if ($sub) {
                $new_status = ($sub->getStatus() == 'completed') ? 'open' : 'completed';
                Task::updateStatus($ts_id, $new_status, $thisuser->getId());
            }
        }
        header('Location: tasks.php?id=' . $ts_task_id . '&view=1');
        exit;

    case 'quick_subtask':
        $qs_task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        $qs_title = isset($_POST['subtask_title']) ? trim($_POST['subtask_title']) : '';
        if (!$qs_task_id) {
            $errors['err'] = 'Не указана родительская задача';
        } elseif (!$qs_title) {
            $errors['err'] = 'Введите название подзадачи';
        } else {
            $parent = Task::lookup($qs_task_id);
            if (!$parent) {
                $errors['err'] = 'Родительская задача не найдена';
            } else {
                $qs_data = array(
                    'title' => $qs_title,
                    'board_id' => $parent->getBoardId(),
                    'parent_task_id' => $qs_task_id,
                    'status' => 'open',
                    'created_by' => $thisuser->getId()
                );
                $qs_errors = array();
                $qs_id = Task::create($qs_data, $qs_errors);
                if ($qs_id) {
                    TaskActivity::log($qs_task_id, $thisuser->getId(), 'updated', 'Добавлена подзадача: ' . $qs_title);
                    $msg = 'Подзадача создана';
                    header('Location: tasks.php?id=' . $qs_task_id . '&msg=' . urlencode($msg));
                    exit;
                } else {
                    $errors['err'] = $qs_errors['err'] ?? 'Ошибка создания подзадачи';
                }
            }
        }
        if ($qs_task_id) {
            $task = Task::lookup($qs_task_id);
            $page = 'task.inc.php';
        }
        break;

    case 'save_recurring':
        require_once(INCLUDE_DIR.'class.taskrecurring.php');
        $sr_task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        $sr_frequency = isset($_POST['rec_frequency']) ? $_POST['rec_frequency'] : '';
        $sr_interval = isset($_POST['rec_interval']) ? intval($_POST['rec_interval']) : 1;
        $sr_days = isset($_POST['rec_days']) && is_array($_POST['rec_days']) ? implode(',', $_POST['rec_days']) : '';
        if (!$sr_task_id) {
            $errors['err'] = 'Не указана задача';
        } elseif (!$sr_frequency) {
            $errors['err'] = 'Укажите частоту повторения';
        } else {
            $existing = TaskRecurring::getByTaskId($sr_task_id);
            $sr_errors = array();
            if ($existing) {
                $sr_data = array(
                    'frequency' => $sr_frequency,
                    'interval_value' => $sr_interval,
                    'day_of_week' => $sr_days,
                    'is_active' => 1
                );
                $next = TaskRecurring::calculateNextOccurrence($sr_frequency, $sr_interval, $sr_days, date('Y-m-d H:i:s'));
                $sr_data['next_occurrence'] = $next;
                if (TaskRecurring::update($existing['recurring_id'], $sr_data, $sr_errors)) {
                    $msg = 'Повторение обновлено';
                } else {
                    $errors['err'] = 'Ошибка обновления повторения';
                }
            } else {
                $sr_data = array(
                    'task_id' => $sr_task_id,
                    'frequency' => $sr_frequency,
                    'interval_value' => $sr_interval,
                    'day_of_week' => $sr_days
                );
                $sr_id = TaskRecurring::create($sr_data, $sr_errors);
                if ($sr_id) {
                    $msg = 'Повторение настроено';
                } else {
                    $errors['err'] = $sr_errors['err'] ?? 'Ошибка создания повторения';
                }
            }
        }
        header('Location: tasks.php?id=' . $sr_task_id . '&msg=' . urlencode($msg ?? ''));
        exit;

    case 'toggle_recurring':
        require_once(INCLUDE_DIR.'class.taskrecurring.php');
        $tr_task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        $tr_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;
        if (!$tr_task_id) {
            $errors['err'] = 'Не указана задача';
        } else {
            $existing = TaskRecurring::getByTaskId($tr_task_id);
            if ($existing) {
                $tr_errors = array();
                if (TaskRecurring::update($existing['recurring_id'], array('is_active' => $tr_active), $tr_errors)) {
                    $msg = $tr_active ? 'Повторение активировано' : 'Повторение приостановлено';
                } else {
                    $errors['err'] = 'Ошибка обновления';
                }
            } else {
                $errors['err'] = 'Повторение не найдено';
            }
        }
        header('Location: tasks.php?id=' . $tr_task_id . '&msg=' . urlencode($msg ?? ''));
        exit;

    case 'remove_recurring':
        require_once(INCLUDE_DIR.'class.taskrecurring.php');
        $rr_task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        if (!$rr_task_id) {
            $errors['err'] = 'Не указана задача';
        } else {
            if (TaskRecurring::deleteByTaskId($rr_task_id)) {
                $msg = 'Повторение удалено';
            } else {
                $errors['err'] = 'Ошибка удаления повторения';
            }
        }
        header('Location: tasks.php?id=' . $rr_task_id . '&msg=' . urlencode($msg ?? ''));
        exit;

    case 'save_template':
        require_once(INCLUDE_DIR.'class.tasktemplate.php');
        $st_task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        $st_name = isset($_POST['template_name']) ? trim($_POST['template_name']) : '';
        if (!$st_task_id) {
            $errors['err'] = 'Не указана задача';
        } elseif (!$st_name) {
            $errors['err'] = 'Введите название шаблона';
        } else {
            $tpl_id = TaskTemplate::createFromTask($st_task_id, $st_name, $thisuser->getId());
            if ($tpl_id) {
                $msg = 'Шаблон сохранён';
            } else {
                $errors['err'] = 'Ошибка создания шаблона';
            }
        }
        header('Location: tasks.php?id=' . $st_task_id . '&msg=' . urlencode($msg ?? ''));
        exit;

    case 'delete_timelog':
        $dtl_id = isset($_POST['timelog_id']) ? intval($_POST['timelog_id']) : 0;
        $dtl_task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        if ($dtl_id && $dtl_task_id) {
            if (TaskTimeLog::deleteLog($dtl_id)) {
                TaskActivity::log($dtl_task_id, $thisuser->getId(), 'updated', 'Удалена запись времени');
                $msg = 'Запись времени удалена';
                header('Location: tasks.php?id=' . $dtl_task_id . '&msg=' . urlencode($msg));
                exit;
            }
        }
        $errors['err'] = 'Ошибка удаления записи времени';
        if ($dtl_task_id) {
            $task = Task::lookup($dtl_task_id);
            $page = 'task-view.inc.php';
        }
        break;

    case 'move_task':
        $mt_task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        $mt_list_id = isset($_POST['list_id']) ? intval($_POST['list_id']) : 0;
        $mt_position = isset($_POST['position']) ? intval($_POST['position']) : 0;
        if ($mt_task_id) {
            $mt_board_sql = 'SELECT board_id FROM ' . TASKS_TABLE . ' WHERE task_id=' . db_input($mt_task_id);
            if (($mt_board_res = db_query($mt_board_sql)) && db_num_rows($mt_board_res)) {
                $mt_board_row = db_fetch_array($mt_board_res);
                if (!TaskPermission::canEdit($mt_board_row['board_id'], $thisuser->getId())) {
                    header('Content-Type: application/json');
                    echo json_encode(array('error' => 'Недостаточно прав'));
                    exit;
                }
            }
            $mt_new_status = null;
            if ($mt_list_id) {
                $mt_list_sql = 'SELECT status FROM ' . TASK_LISTS_TABLE . ' WHERE list_id=' . db_input($mt_list_id);
                if (($mt_list_res = db_query($mt_list_sql)) && db_num_rows($mt_list_res)) {
                    $mt_list_row = db_fetch_array($mt_list_res);
                    $mt_valid = array('open', 'in_progress', 'review', 'blocked', 'completed', 'cancelled');
                    if (!empty($mt_list_row['status']) && in_array($mt_list_row['status'], $mt_valid)) {
                        $mt_new_status = $mt_list_row['status'];
                    }
                }
            }
            if ($mt_new_status) {
                $sql = sprintf("UPDATE %s SET list_id=%s, position=%d, status=%s, updated=NOW() WHERE task_id=%d",
                    TASKS_TABLE,
                    $mt_list_id ? db_input($mt_list_id) : 'NULL',
                    $mt_position,
                    db_input($mt_new_status),
                    db_input($mt_task_id));
            } else {
                $sql = sprintf("UPDATE %s SET list_id=%s, position=%d, updated=NOW() WHERE task_id=%d",
                    TASKS_TABLE,
                    $mt_list_id ? db_input($mt_list_id) : 'NULL',
                    $mt_position,
                    db_input($mt_task_id));
            }
            db_query($sql);
            if ($mt_new_status) {
                TaskActivity::log($mt_task_id, $thisuser->getId(), 'updated', 'Статус изменён через канбан: ' . $mt_new_status);
            }
        }
        header('Content-Type: application/json');
        echo json_encode(array('success' => true));
        exit;

    case 'kanban_quick_add':
        $ka_title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $ka_board_id = isset($_POST['board_id']) ? intval($_POST['board_id']) : 0;
        $ka_list_id = isset($_POST['list_id']) ? intval($_POST['list_id']) : 0;
        if (!$ka_board_id || !TaskPermission::canEdit($ka_board_id, $thisuser->getId())) {
            header('Content-Type: application/json');
            echo json_encode(array('error' => 'Недостаточно прав'));
            exit;
        }
        if ($ka_title && $ka_board_id) {
            $ka_data = array(
                'title' => $ka_title,
                'board_id' => $ka_board_id,
                'list_id' => $ka_list_id ? $ka_list_id : null,
                'status' => 'open',
                'priority' => 'normal',
                'created_by' => $thisuser->getId()
            );
            $ka_errors = array();
            $ka_id = Task::create($ka_data, $ka_errors);
            if ($ka_id) {
                header('Content-Type: application/json');
                echo json_encode(array('success' => true, 'task_id' => $ka_id));
                exit;
            }
        }
        header('Content-Type: application/json');
        echo json_encode(array('error' => 'Ошибка создания задачи'));
        exit;

    default:
        $errors['err'] = 'Неизвестное действие';
    endswitch;
    } // end CSRF else
endif;

if (!$msg && isset($_GET['msg'])) {
    $msg = Format::htmlchars($_GET['msg']);
}

if (!$page && isset($_REQUEST['a']) && $_REQUEST['a'] == 'add' && !isset($taskId))
    $page = 'task.inc.php';

if (!$page && isset($_REQUEST['a']) && $_REQUEST['a'] == 'automation' && $board) {
    $page = 'task-automation.inc.php';
}

$display = isset($_REQUEST['display']) ? $_REQUEST['display'] : '';
if (!$page && $display == 'dashboard')
    $page = 'tasks-dashboard.inc.php';
if (!$page && $display == 'kanban')
    $page = 'tasks-kanban.inc.php';
if (!$page && $display == 'calendar')
    $page = 'tasks-calendar.inc.php';

$inc = $page ? $page : 'tasks.inc.php';

$myTasksCount = 0;
$allTasksCount = 0;

$sql = 'SELECT COUNT(*) FROM ' . TASKS_TABLE
     . ' WHERE created_by=' . db_input($thisuser->getId())
     . " AND status NOT IN ('completed','cancelled')";
if (($res = db_query($sql)) && db_num_rows($res))
    list($myTasksCount) = db_fetch_row($res);

$sql = 'SELECT COUNT(*) FROM ' . TASK_ASSIGNEES_TABLE . ' a'
     . ' JOIN ' . TASKS_TABLE . ' t ON t.task_id=a.task_id'
     . ' WHERE a.staff_id=' . db_input($thisuser->getId())
     . " AND a.role='assignee'"
     . " AND t.status NOT IN ('completed','cancelled')";
if (($res = db_query($sql)) && db_num_rows($res))
    list($assignedCount) = db_fetch_row($res);

$nav->setTabActive('tasks');
$nav->addSubMenu(array('desc' => 'Мои задачи (' . intval($assignedCount) . ')', 'href' => 'tasks.php?view=mytasks', 'iconclass' => 'myTasks'));
$nav->addSubMenu(array('desc' => 'Все задачи', 'href' => 'tasks.php', 'iconclass' => 'allTasks'));
$nav->addSubMenu(array('desc' => 'Доски', 'href' => 'taskboards.php', 'iconclass' => 'taskBoards'));

require_once(STAFFINC_DIR . 'header.inc.php');
require_once(STAFFINC_DIR . $inc);
require_once(STAFFINC_DIR . 'footer.inc.php');
?>
