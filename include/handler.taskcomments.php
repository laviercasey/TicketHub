<?php
if(!defined('OSTAJAXINC') || !defined('INCLUDE_DIR')) die('!');

require_once(INCLUDE_DIR.'class.task.php');
require_once(INCLUDE_DIR.'class.taskcomment.php');
require_once(INCLUDE_DIR.'class.taskactivity.php');

class TaskcommentsAjaxAPI {

    function add($params) {
        global $thisuser;

        $errors = array();
        $task_id = isset($params['task_id']) ? intval($params['task_id']) : 0;
        $text = isset($params['comment_text']) ? trim($params['comment_text']) : '';

        if (!$task_id || !$text) {
            Http::response(400, json_encode(array('error' => 'Заполните все поля')));
            return;
        }

        $data = array(
            'task_id' => $task_id,
            'staff_id' => $thisuser->getId(),
            'comment_text' => $text
        );

        $id = TaskComment::create($data, $errors);
        if ($id) {
            $comment = TaskComment::lookup($id);
            $html = '<div class="task-comment" id="comment-' . $id . '">'
                  . '<div class="comment-header">'
                  . '<strong>' . Format::htmlchars($thisuser->getFirstname() . ' ' . $thisuser->getLastname()) . '</strong>'
                  . ' <small class="text-muted">' . date('d.m.Y H:i') . '</small>'
                  . ' <a href="#" class="text-danger pull-right delete-comment" data-id="' . $id . '" title="Удалить"><i class="fa fa-trash"></i></a>'
                  . '</div>'
                  . '<div class="comment-body">' . nl2br(Format::htmlchars($text)) . '</div>'
                  . '</div>';

            header('Content-Type: application/json');
            return json_encode(array('success' => true, 'comment_id' => $id, 'html' => $html));
        } else {
            Http::response(400, json_encode(array('error' => $errors['err'] ? $errors['err'] : 'Ошибка')));
        }
    }

    function remove($params) {
        global $thisuser;

        $id = isset($params['id']) ? intval($params['id']) : 0;
        if (!$id) {
            Http::response(400, 'Не указан ID');
            return;
        }

        $comment = TaskComment::lookup($id);
        if (!$comment) {
            Http::response(404, 'Комментарий не найден');
            return;
        }

        if ($comment->getStaffId() != $thisuser->getId() && !$thisuser->isadmin()) {
            Http::response(403, 'Нет прав');
            return;
        }

        if (TaskComment::delete($id)) {
            header('Content-Type: application/json');
            return json_encode(array('success' => true));
        } else {
            Http::response(500, 'Ошибка удаления');
        }
    }

    function edit($params) {
        global $thisuser;

        $id = isset($params['id']) ? intval($params['id']) : 0;
        $text = isset($params['comment_text']) ? trim($params['comment_text']) : '';

        if (!$id || !$text) {
            Http::response(400, 'Заполните все поля');
            return;
        }

        $comment = TaskComment::lookup($id);
        if (!$comment) {
            Http::response(404, 'Не найден');
            return;
        }

        if ($comment->getStaffId() != $thisuser->getId() && !$thisuser->isadmin()) {
            Http::response(403, 'Нет прав');
            return;
        }

        $errors = array();
        if (TaskComment::update($id, $text, $errors)) {
            header('Content-Type: application/json');
            return json_encode(array('success' => true, 'html' => nl2br(Format::htmlchars($text))));
        } else {
            Http::response(500, 'Ошибка обновления');
        }
    }
}
?>
