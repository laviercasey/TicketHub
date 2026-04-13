<?php
if(!defined('OSTAJAXINC') || !defined('OSTSCPINC')) die('Доступ запрещён');

require_once(INCLUDE_DIR.'class.task.php');
require_once(INCLUDE_DIR.'class.taskrecurring.php');
require_once(INCLUDE_DIR.'class.tasktemplate.php');

class TaskrecurringAjaxAPI {

    function setRecurring($params) {
        global $thisuser;

        $task_id = isset($params['task_id']) ? intval($params['task_id']) : 0;
        if (!$task_id) {
            return json_encode(array('error' => 'ID задачи не указан'));
        }

        if (isset($params['toggle_only']) && $params['toggle_only']) {
            $existing = TaskRecurring::getByTaskId($task_id);
            if ($existing) {
                $is_active = isset($params['is_active']) ? intval($params['is_active']) : 0;
                $errors = array();
                TaskRecurring::update($existing['recurring_id'], array('is_active' => $is_active), $errors);
                return json_encode(array('success' => true));
            }
            return json_encode(array('error' => 'Повторение не найдено'));
        }

        $frequency = isset($params['frequency']) ? $params['frequency'] : '';
        $interval = isset($params['interval_value']) ? intval($params['interval_value']) : 1;
        $day_of_week = isset($params['day_of_week']) ? $params['day_of_week'] : '';

        $existing = TaskRecurring::getByTaskId($task_id);

        $errors = array();

        if ($existing) {
            $data = array(
                'frequency' => $frequency,
                'interval_value' => $interval,
                'day_of_week' => $day_of_week,
                'is_active' => 1
            );
            $next = TaskRecurring::calculateNextOccurrence($frequency, $interval, $day_of_week, date('Y-m-d H:i:s'));
            $data['next_occurrence'] = $next;

            if (TaskRecurring::update($existing['recurring_id'], $data, $errors)) {
                return json_encode(array('success' => true, 'recurring_id' => $existing['recurring_id']));
            }
            return json_encode(array('error' => 'Ошибка обновления'));
        } else {
            $data = array(
                'task_id' => $task_id,
                'frequency' => $frequency,
                'interval_value' => $interval,
                'day_of_week' => $day_of_week
            );

            $id = TaskRecurring::create($data, $errors);
            if ($id) {
                return json_encode(array('success' => true, 'recurring_id' => $id));
            }
            $err_msg = '';
            if ($errors) {
                $err_vals = array_values($errors);
                $err_msg = $err_vals[0];
            }
            return json_encode(array('error' => $err_msg ? $err_msg : 'Ошибка создания'));
        }
    }

    function removeRecurring($params) {
        global $thisuser;

        $task_id = isset($params['task_id']) ? intval($params['task_id']) : 0;
        if (!$task_id) {
            return json_encode(array('error' => 'ID задачи не указан'));
        }

        if (TaskRecurring::deleteByTaskId($task_id)) {
            return json_encode(array('success' => true));
        }
        return json_encode(array('error' => 'Ошибка удаления'));
    }

    function saveAsTemplate($params) {
        global $thisuser;

        $task_id = isset($params['task_id']) ? intval($params['task_id']) : 0;
        $name = isset($params['template_name']) ? trim($params['template_name']) : '';

        if (!$task_id) {
            return json_encode(array('error' => 'ID задачи не указан'));
        }
        if (!$name) {
            return json_encode(array('error' => 'Название шаблона обязательно'));
        }

        $tpl_id = TaskTemplate::createFromTask($task_id, $name, $thisuser->getId());
        if ($tpl_id) {
            return json_encode(array('success' => true, 'template_id' => $tpl_id));
        }
        return json_encode(array('error' => 'Ошибка создания шаблона'));
    }

    function createFromTemplate($params) {
        global $thisuser;

        $template_id = isset($params['template_id']) ? intval($params['template_id']) : 0;
        $board_id = isset($params['board_id']) ? intval($params['board_id']) : 0;

        if (!$template_id) {
            return json_encode(array('error' => 'ID шаблона не указан'));
        }

        $task_id = TaskTemplate::createTaskFromTemplate($template_id, $board_id, $thisuser->getId());
        if ($task_id) {
            return json_encode(array('success' => true, 'task_id' => $task_id));
        }
        return json_encode(array('error' => 'Ошибка создания задачи из шаблона'));
    }

    function deleteTemplate($params) {
        global $thisuser;

        $template_id = isset($params['template_id']) ? intval($params['template_id']) : 0;
        if (!$template_id) {
            return json_encode(array('error' => 'ID шаблона не указан'));
        }

        if (TaskTemplate::delete($template_id)) {
            return json_encode(array('success' => true));
        }
        return json_encode(array('error' => 'Ошибка удаления шаблона'));
    }

    function getTemplates($params) {
        global $thisuser;

        $type = isset($params['type']) ? $params['type'] : null;
        $templates = TaskTemplate::getAll($type);

        $result = array();
        foreach ($templates as $tpl) {
            $result[] = array(
                'template_id' => $tpl['template_id'],
                'template_name' => $tpl['template_name'],
                'template_type' => $tpl['template_type'],
                'creator_name' => $tpl['creator_name'],
                'created' => $tpl['created']
            );
        }

        return json_encode(array('success' => true, 'templates' => $result));
    }
}
?>
