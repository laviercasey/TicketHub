<?php
if(!defined('OSTAJAXINC') || !defined('INCLUDE_DIR')) die('!');

require_once(INCLUDE_DIR.'class.tasktag.php');
require_once(INCLUDE_DIR.'class.taskcustomfield.php');
require_once(INCLUDE_DIR.'class.taskpermission.php');

class TasktagsAjaxAPI {

    function add($params) {
        global $thisuser;

        $errors = array();
        $data = array(
            'tag_name' => isset($params['tag_name']) ? $params['tag_name'] : '',
            'tag_color' => isset($params['tag_color']) ? $params['tag_color'] : '#3498db',
            'board_id' => isset($params['board_id']) ? intval($params['board_id']) : 0
        );

        $id = TaskTag::create($data, $errors);
        if ($id) {
            header('Content-Type: application/json');
            return json_encode(array(
                'success' => true,
                'tag_id' => $id,
                'tag_name' => Format::htmlchars($data['tag_name']),
                'tag_color' => $data['tag_color']
            ));
        } else {
            Http::response(400, json_encode(array('success' => false, 'errors' => $errors)));
        }
    }

    function update($params) {
        global $thisuser;

        $id = isset($params['id']) ? intval($params['id']) : 0;
        if (!$id) {
            Http::response(400, 'Не указан ID тега');
            return;
        }

        $tag = TaskTag::lookup($id);
        if (!$tag) {
            Http::response(404, 'Тег не найден');
            return;
        }
        if (!TaskPermission::canAdmin($tag->getBoardId(), $thisuser->getId())) {
            Http::response(403, json_encode(array('success' => false, 'error' => 'Недостаточно прав')));
            return;
        }

        $errors = array();
        $data = array(
            'tag_name' => isset($params['tag_name']) ? $params['tag_name'] : '',
            'tag_color' => isset($params['tag_color']) ? $params['tag_color'] : '#3498db'
        );

        if (TaskTag::updateTag($id, $data, $errors)) {
            header('Content-Type: application/json');
            return json_encode(array('success' => true));
        } else {
            Http::response(400, json_encode(array('success' => false, 'errors' => $errors)));
        }
    }

    function remove($params) {
        global $thisuser;

        $id = isset($params['id']) ? intval($params['id']) : 0;
        if (!$id) {
            Http::response(400, 'Не указан ID тега');
            return;
        }

        $tag = TaskTag::lookup($id);
        if (!$tag) {
            Http::response(404, 'Тег не найден');
            return;
        }
        if (!TaskPermission::canAdmin($tag->getBoardId(), $thisuser->getId())) {
            Http::response(403, json_encode(array('success' => false, 'error' => 'Недостаточно прав')));
            return;
        }

        if (TaskTag::deleteTag($id)) {
            header('Content-Type: application/json');
            return json_encode(array('success' => true));
        } else {
            Http::response(500, 'Ошибка удаления тега');
        }
    }

    function toggle($params) {
        global $thisuser;

        $task_id = isset($params['task_id']) ? intval($params['task_id']) : 0;
        $tag_id = isset($params['tag_id']) ? intval($params['tag_id']) : 0;

        if (!$task_id || !$tag_id) {
            Http::response(400, 'Недостаточно параметров');
            return;
        }

        $sql = 'SELECT association_id FROM ' . TASK_TAG_ASSOC_TABLE
             . ' WHERE task_id=' . db_input($task_id)
             . ' AND tag_id=' . db_input($tag_id);
        $exists = false;
        if (($res = db_query($sql)) && db_num_rows($res)) {
            $exists = true;
        }

        if ($exists) {
            TaskTag::removeFromTask($task_id, $tag_id);
            $action = 'removed';
        } else {
            TaskTag::addToTask($task_id, $tag_id);
            $action = 'added';
        }

        header('Content-Type: application/json');
        return json_encode(array('success' => true, 'action' => $action));
    }

    function getByBoard($params) {
        $board_id = isset($params['board_id']) ? intval($params['board_id']) : 0;
        if (!$board_id) {
            header('Content-Type: application/json');
            return json_encode(array());
        }

        $tags = TaskTag::getByBoard($board_id);
        header('Content-Type: application/json');
        return json_encode($tags);
    }

    function getByTask($params) {
        $task_id = isset($params['task_id']) ? intval($params['task_id']) : 0;
        if (!$task_id) {
            header('Content-Type: application/json');
            return json_encode(array());
        }

        $tags = TaskTag::getByTask($task_id);
        header('Content-Type: application/json');
        return json_encode($tags);
    }

    function addField($params) {
        global $thisuser;

        $errors = array();
        $data = array(
            'field_name' => isset($params['field_name']) ? $params['field_name'] : '',
            'field_type' => isset($params['field_type']) ? $params['field_type'] : 'text',
            'board_id' => isset($params['board_id']) ? intval($params['board_id']) : 0,
            'field_options' => isset($params['field_options']) ? $params['field_options'] : '',
            'is_required' => isset($params['is_required']) ? intval($params['is_required']) : 0
        );

        $id = TaskCustomField::create($data, $errors);
        if ($id) {
            header('Content-Type: application/json');
            return json_encode(array(
                'success' => true,
                'field_id' => $id,
                'field_name' => Format::htmlchars($data['field_name']),
                'field_type' => $data['field_type']
            ));
        } else {
            Http::response(400, json_encode(array('success' => false, 'errors' => $errors)));
        }
    }

    function updateField($params) {
        global $thisuser;

        $id = isset($params['id']) ? intval($params['id']) : 0;
        if (!$id) {
            Http::response(400, 'Не указан ID поля');
            return;
        }

        $field = TaskCustomField::lookup($id);
        if (!$field) {
            Http::response(404, 'Поле не найдено');
            return;
        }
        if (!TaskPermission::canAdmin($field->getBoardId(), $thisuser->getId())) {
            Http::response(403, json_encode(array('success' => false, 'error' => 'Недостаточно прав')));
            return;
        }

        $errors = array();
        $data = array(
            'field_name' => isset($params['field_name']) ? $params['field_name'] : '',
            'field_options' => isset($params['field_options']) ? $params['field_options'] : '',
            'is_required' => isset($params['is_required']) ? intval($params['is_required']) : 0
        );

        if (TaskCustomField::updateField($id, $data, $errors)) {
            header('Content-Type: application/json');
            return json_encode(array('success' => true));
        } else {
            Http::response(400, json_encode(array('success' => false, 'errors' => $errors)));
        }
    }

    function removeField($params) {
        global $thisuser;

        $id = isset($params['id']) ? intval($params['id']) : 0;
        if (!$id) {
            Http::response(400, 'Не указан ID поля');
            return;
        }

        $field = TaskCustomField::lookup($id);
        if (!$field) {
            Http::response(404, 'Поле не найдено');
            return;
        }
        if (!TaskPermission::canAdmin($field->getBoardId(), $thisuser->getId())) {
            Http::response(403, json_encode(array('success' => false, 'error' => 'Недостаточно прав')));
            return;
        }

        if (TaskCustomField::deleteField($id)) {
            header('Content-Type: application/json');
            return json_encode(array('success' => true));
        } else {
            Http::response(500, 'Ошибка удаления поля');
        }
    }
}
?>
