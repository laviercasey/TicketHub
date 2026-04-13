<?php
if(!defined('OSTAJAXINC') || !defined('INCLUDE_DIR')) die('!');

require_once(INCLUDE_DIR.'class.taskautomation.php');
require_once(INCLUDE_DIR.'class.taskboard.php');
require_once(INCLUDE_DIR.'class.taskpermission.php');

class TaskautomationAjaxAPI {

    function add($params) {
        global $thisuser;

        $errors = array();

        $board_id = isset($params['board_id']) ? intval($params['board_id']) : 0;
        if (!$board_id) {
            Http::response(400, json_encode(array('success' => false, 'error' => 'Не указана доска')));
            return;
        }

        $trigger_config = array();
        $trigger_type = isset($params['trigger_type']) ? $params['trigger_type'] : '';

        if ($trigger_type == 'status_changed') {
            if (isset($params['from_status']) && $params['from_status'] !== '') {
                $trigger_config['from_status'] = $params['from_status'];
            }
            if (isset($params['to_status']) && $params['to_status'] !== '') {
                $trigger_config['to_status'] = $params['to_status'];
            }
        } elseif ($trigger_type == 'priority_changed') {
            if (isset($params['from_priority']) && $params['from_priority'] !== '') {
                $trigger_config['from_priority'] = $params['from_priority'];
            }
            if (isset($params['to_priority']) && $params['to_priority'] !== '') {
                $trigger_config['to_priority'] = $params['to_priority'];
            }
        } elseif ($trigger_type == 'deadline_passed') {
            if (isset($params['days_before'])) {
                $trigger_config['days_before'] = intval($params['days_before']);
            }
        }

        $action_config = array();
        $action_type = isset($params['action_type']) ? $params['action_type'] : '';

        if ($action_type == 'change_status' && isset($params['action_status'])) {
            $action_config['status'] = $params['action_status'];
        } elseif ($action_type == 'change_priority' && isset($params['action_priority'])) {
            $action_config['priority'] = $params['action_priority'];
        } elseif ($action_type == 'assign_to' && isset($params['action_staff_id'])) {
            $action_config['staff_id'] = intval($params['action_staff_id']);
        } elseif ($action_type == 'move_to_list' && isset($params['action_list_id'])) {
            $action_config['list_id'] = intval($params['action_list_id']);
        } elseif ($action_type == 'add_tag' && isset($params['action_tag_id'])) {
            $action_config['tag_id'] = intval($params['action_tag_id']);
        } elseif ($action_type == 'send_notification') {
            $action_config['message'] = isset($params['action_message']) ? $params['action_message'] : '';
        }

        $data = array(
            'rule_name'      => isset($params['rule_name']) ? $params['rule_name'] : '',
            'board_id'       => $board_id,
            'trigger_type'   => $trigger_type,
            'trigger_config' => $trigger_config,
            'action_type'    => $action_type,
            'action_config'  => $action_config,
            'is_enabled'     => 1
        );

        $id = TaskAutomation::create($data, $errors);
        if ($id) {
            $rule = new TaskAutomation($id);
            $triggerLabels = TaskAutomation::getTriggerLabels();
            $actionLabels = TaskAutomation::getActionLabels();
            header('Content-Type: application/json');
            return json_encode(array(
                'success'       => true,
                'rule_id'       => $id,
                'rule_name'     => Format::htmlchars($data['rule_name']),
                'trigger_type'  => $data['trigger_type'],
                'trigger_label' => isset($triggerLabels[$data['trigger_type']]) ? $triggerLabels[$data['trigger_type']] : $data['trigger_type'],
                'action_type'   => $data['action_type'],
                'action_label'  => isset($actionLabels[$data['action_type']]) ? $actionLabels[$data['action_type']] : $data['action_type'],
                'is_enabled'    => 1
            ));
        } else {
            header('Content-Type: application/json');
            Http::response(400, json_encode(array('success' => false, 'errors' => $errors)));
        }
    }

    function update($params) {
        global $thisuser;

        $id = isset($params['id']) ? intval($params['id']) : 0;
        if (!$id) {
            Http::response(400, json_encode(array('success' => false, 'error' => 'Не указан ID правила')));
            return;
        }

        $rule = TaskAutomation::lookup($id);
        if (!$rule) {
            Http::response(404, json_encode(array('success' => false, 'error' => 'Правило не найдено')));
            return;
        }

        if (!TaskPermission::canAdmin($rule->getBoardId(), $thisuser->getId())) {
            Http::response(403, json_encode(array('success' => false, 'error' => 'Недостаточно прав')));
            return;
        }

        $errors = array();

        $trigger_config = array();
        $trigger_type = isset($params['trigger_type']) ? $params['trigger_type'] : $rule->getTriggerType();

        if ($trigger_type == 'status_changed') {
            if (isset($params['from_status']) && $params['from_status'] !== '') {
                $trigger_config['from_status'] = $params['from_status'];
            }
            if (isset($params['to_status']) && $params['to_status'] !== '') {
                $trigger_config['to_status'] = $params['to_status'];
            }
        } elseif ($trigger_type == 'priority_changed') {
            if (isset($params['from_priority']) && $params['from_priority'] !== '') {
                $trigger_config['from_priority'] = $params['from_priority'];
            }
            if (isset($params['to_priority']) && $params['to_priority'] !== '') {
                $trigger_config['to_priority'] = $params['to_priority'];
            }
        } elseif ($trigger_type == 'deadline_passed') {
            if (isset($params['days_before'])) {
                $trigger_config['days_before'] = intval($params['days_before']);
            }
        }

        $action_config = array();
        $action_type = isset($params['action_type']) ? $params['action_type'] : $rule->getActionType();

        if ($action_type == 'change_status' && isset($params['action_status'])) {
            $action_config['status'] = $params['action_status'];
        } elseif ($action_type == 'change_priority' && isset($params['action_priority'])) {
            $action_config['priority'] = $params['action_priority'];
        } elseif ($action_type == 'assign_to' && isset($params['action_staff_id'])) {
            $action_config['staff_id'] = intval($params['action_staff_id']);
        } elseif ($action_type == 'move_to_list' && isset($params['action_list_id'])) {
            $action_config['list_id'] = intval($params['action_list_id']);
        } elseif ($action_type == 'add_tag' && isset($params['action_tag_id'])) {
            $action_config['tag_id'] = intval($params['action_tag_id']);
        } elseif ($action_type == 'send_notification') {
            $action_config['message'] = isset($params['action_message']) ? $params['action_message'] : '';
        }

        $data = array(
            'rule_name'      => isset($params['rule_name']) ? $params['rule_name'] : $rule->getName(),
            'trigger_type'   => $trigger_type,
            'trigger_config' => $trigger_config,
            'action_type'    => $action_type,
            'action_config'  => $action_config
        );

        if (TaskAutomation::update($id, $data, $errors)) {
            header('Content-Type: application/json');
            return json_encode(array('success' => true));
        } else {
            header('Content-Type: application/json');
            Http::response(400, json_encode(array('success' => false, 'errors' => $errors)));
        }
    }

    function remove($params) {
        global $thisuser;

        $id = isset($params['id']) ? intval($params['id']) : 0;
        if (!$id) {
            Http::response(400, json_encode(array('success' => false, 'error' => 'Не указан ID правила')));
            return;
        }

        $rule = TaskAutomation::lookup($id);
        if (!$rule) {
            Http::response(404, json_encode(array('success' => false, 'error' => 'Правило не найдено')));
            return;
        }
        if (!TaskPermission::canAdmin($rule->getBoardId(), $thisuser->getId())) {
            Http::response(403, json_encode(array('success' => false, 'error' => 'Недостаточно прав')));
            return;
        }

        if (TaskAutomation::delete($id)) {
            header('Content-Type: application/json');
            return json_encode(array('success' => true));
        } else {
            Http::response(500, json_encode(array('success' => false, 'error' => 'Ошибка удаления')));
        }
    }

    function toggle($params) {
        global $thisuser;

        $id = isset($params['id']) ? intval($params['id']) : 0;
        if (!$id) {
            Http::response(400, json_encode(array('success' => false, 'error' => 'Не указан ID правила')));
            return;
        }

        $rule = TaskAutomation::lookup($id);
        if (!$rule) {
            Http::response(404, json_encode(array('success' => false, 'error' => 'Правило не найдено')));
            return;
        }
        if (!TaskPermission::canAdmin($rule->getBoardId(), $thisuser->getId())) {
            Http::response(403, json_encode(array('success' => false, 'error' => 'Недостаточно прав')));
            return;
        }

        if (TaskAutomation::toggleEnabled($id)) {
            $rule = new TaskAutomation($id);
            header('Content-Type: application/json');
            return json_encode(array('success' => true, 'is_enabled' => $rule->isEnabled() ? 1 : 0));
        } else {
            Http::response(500, json_encode(array('success' => false, 'error' => 'Ошибка переключения')));
        }
    }

    function getByBoard($params) {
        $board_id = isset($params['board_id']) ? intval($params['board_id']) : 0;
        if (!$board_id) {
            header('Content-Type: application/json');
            return json_encode(array());
        }

        $rules = TaskAutomation::getByBoard($board_id);
        $triggerLabels = TaskAutomation::getTriggerLabels();
        $actionLabels = TaskAutomation::getActionLabels();

        $result = array();
        foreach ($rules as $r) {
            $result[] = array(
                'rule_id'       => $r['rule_id'],
                'rule_name'     => $r['rule_name'],
                'trigger_type'  => $r['trigger_type'],
                'trigger_label' => isset($triggerLabels[$r['trigger_type']]) ? $triggerLabels[$r['trigger_type']] : $r['trigger_type'],
                'action_type'   => $r['action_type'],
                'action_label'  => isset($actionLabels[$r['action_type']]) ? $actionLabels[$r['action_type']] : $r['action_type'],
                'is_enabled'    => $r['is_enabled']
            );
        }

        header('Content-Type: application/json');
        return json_encode($result);
    }
}
?>
