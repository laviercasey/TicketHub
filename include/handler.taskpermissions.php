<?php
if(!defined('OSTAJAXINC') || !defined('INCLUDE_DIR')) die('!');

require_once(INCLUDE_DIR.'class.taskpermission.php');

class TaskpermissionsAjaxAPI {

    function add($params) {
        global $thisuser;

        $errors = array();
        $board_id = isset($params['board_id']) ? intval($params['board_id']) : 0;
        $perm_type = isset($params['perm_type']) ? $params['perm_type'] : 'staff';
        $staff_id = ($perm_type == 'staff' && isset($params['staff_id'])) ? intval($params['staff_id']) : 0;
        $dept_id = ($perm_type == 'dept' && isset($params['dept_id'])) ? intval($params['dept_id']) : 0;
        $permission_level = isset($params['permission_level']) ? $params['permission_level'] : '';

        if ($board_id && !TaskPermission::canAdmin($board_id, $thisuser->getId())) {
            Http::response(403, json_encode(array('success' => false, 'error' => 'Недостаточно прав')));
            return;
        }

        $data = array(
            'board_id' => $board_id,
            'staff_id' => $staff_id,
            'dept_id' => $dept_id,
            'permission_level' => $permission_level
        );

        $id = TaskPermission::create($data, $errors);
        if ($id) {
            $perms = TaskPermission::getByBoard($board_id);
            $created = null;
            foreach ($perms as $p) {
                if ($p['permission_id'] == $id) {
                    $created = $p;
                    break;
                }
            }

            $labels = TaskPermission::getLevelLabels();
            $levelLabel = isset($labels[$permission_level]) ? $labels[$permission_level] : $permission_level;
            $targetName = '';
            if ($created) {
                $targetName = $created['staff_name'] ? $created['staff_name'] : $created['dept_name'];
            }

            header('Content-Type: application/json');
            return json_encode(array(
                'success' => true,
                'permission_id' => $id,
                'target_name' => Format::htmlchars($targetName),
                'level_label' => Format::htmlchars($levelLabel),
                'permission_level' => $permission_level,
                'perm_type' => $perm_type
            ));
        } else {
            header('Content-Type: application/json');
            Http::response(400, json_encode(array('success' => false, 'errors' => $errors)));
        }
    }

    function remove($params) {
        global $thisuser;

        $id = isset($params['id']) ? intval($params['id']) : 0;
        if (!$id) {
            Http::response(400, 'Не указан ID');
            return;
        }

        $perm = TaskPermission::lookup($id);
        if ($perm && !TaskPermission::canAdmin($perm->getBoardId(), $thisuser->getId())) {
            Http::response(403, json_encode(array('success' => false, 'error' => 'Недостаточно прав')));
            return;
        }

        if (TaskPermission::delete($id)) {
            header('Content-Type: application/json');
            return json_encode(array('success' => true));
        } else {
            Http::response(500, 'Ошибка удаления');
        }
    }

    function getByBoard($params) {
        $board_id = isset($params['board_id']) ? intval($params['board_id']) : 0;
        if (!$board_id) {
            header('Content-Type: application/json');
            return json_encode(array());
        }

        $perms = TaskPermission::getByBoard($board_id);
        header('Content-Type: application/json');
        return json_encode($perms);
    }
}
?>
