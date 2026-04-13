<?php
require('staff.inc.php');
require_once(INCLUDE_DIR.'class.taskboard.php');
require_once(INCLUDE_DIR.'class.dept.php');

$page = '';
$board = null;

if (($id = ($_REQUEST['id'] ?? $_POST['id'] ?? null)) && is_numeric($id)) {
    $board = TaskBoard::lookup($id);
    if (!$board)
        $errors['err'] = 'Доска не найдена #' . $id;
    elseif ($_REQUEST['a'] != 'add')
        $page = 'taskboard.inc.php';
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
            $errors['err'] = 'Отсутствует ID доски';

        $data = array(
            'board_name' => $_POST['board_name'],
            'board_type' => $_POST['board_type'],
            'dept_id' => $_POST['dept_id'] ? $_POST['dept_id'] : 0,
            'description' => $_POST['description'],
            'color' => $_POST['color'],
            'created_by' => $thisuser->getId()
        );

        if (!$errors) {
            if ($_POST['a'] == 'add') {
                $boardId = TaskBoard::create($data, $errors);
                if ($boardId) {
                    $_SESSION['flash_msg'] = 'Доска успешно создана';
                    header('Location: taskboards.php?id=' . $boardId);
                    exit;
                }
            } elseif ($_POST['a'] == 'update') {
                if (TaskBoard::update($_POST['id'], $data, $errors)) {
                    $msg = 'Доска успешно обновлена';
                    $board = TaskBoard::lookup($_POST['id']);
                }
            }
        }

        if ($errors && !$errors['err'])
            $errors['err'] = 'Исправьте ошибки и попробуйте снова';

        break;

    case 'addlist':
        $listId = TaskBoard::addList($_POST['board_id'], $_POST['list_name'], $errors);
        if ($listId) {
            $msg = 'Список добавлен';
            $board = TaskBoard::lookup($_POST['board_id']);
        }
        break;

    case 'updatelist':
        if (TaskBoard::updateList($_POST['list_id'], $_POST['list_name'])) {
            $msg = 'Список обновлен';
            $board = TaskBoard::lookup($_POST['board_id']);
        }
        break;

    case 'deletelist':
        if (TaskBoard::deleteList($_POST['list_id'])) {
            $msg = 'Список удален';
            $board = TaskBoard::lookup($_POST['board_id']);
        }
        break;

    case 'process':
        if (!$_POST['boards'] || !is_array($_POST['boards']))
            $errors['err'] = 'Выберите хотя бы одну доску';
        else {
            $msg = '';
            $selected = count($_POST['boards']);
            if (isset($_POST['archive'])) {
                $count = 0;
                foreach ($_POST['boards'] as $bid) {
                    if (TaskBoard::archive(intval($bid))) $count++;
                }
                $msg = "$count из $selected досок архивировано";
            } elseif (isset($_POST['delete'])) {
                $count = 0;
                foreach ($_POST['boards'] as $bid) {
                    if (TaskBoard::delete(intval($bid))) $count++;
                }
                $msg = "$count из $selected досок удалено";
            }

            if (!$msg)
                $errors['err'] = 'Ошибка выполнения. Попробуйте снова.';
        }
        break;

    default:
        $errors['err'] = 'Неизвестное действие';
    endswitch;
    } // end CSRF else
endif;

if (!$msg && isset($_SESSION['flash_msg'])) {
    $msg = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}

if (!$page && $_REQUEST['a'] == 'add' && !isset($boardId))
    $page = 'taskboard.inc.php';

$inc = $page ? $page : 'taskboards.inc.php';

$nav->setTabActive('tasks');
$nav->addSubMenu(array('desc' => 'Все задачи', 'href' => 'tasks.php', 'iconclass' => 'allTasks'));
$nav->addSubMenu(array('desc' => 'Доски', 'href' => 'taskboards.php', 'iconclass' => 'taskBoards'));

require_once(STAFFINC_DIR . 'header.inc.php');
require_once(STAFFINC_DIR . $inc);
require_once(STAFFINC_DIR . 'footer.inc.php');
?>
