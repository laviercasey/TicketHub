<?php
require('secure.inc.php');
if(!is_object($thisclient) || !$thisclient->isValid()) die('Доступ запрещён');

require_once(INCLUDE_DIR.'class.ticket.php');
$ticket=null;
$inc='tickets.inc.php';
if(($id=$_REQUEST['id']?$_REQUEST['id']:$_POST['ticket_id']) && is_numeric($id)) {
    $ticket= new Ticket(Ticket::getIdByExtId((int)$id));
    if(!$ticket or !$ticket->getEmail()) {
        $ticket=null;
        $errors['err']='Доступ запрещён. Возможно, неверный ID заявки';
    }elseif(strcasecmp($thisclient->getEmail(),$ticket->getEmail())){
        $errors['err']='Нарушение безопасности. Повторные нарушения приведут к блокировке вашего аккаунта.';
        $ticket=null;
    }else{

        $inc='viewticket.inc.php';
    }
}

if ($_POST && !Misc::validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $errors['err'] = 'Ошибка проверки формы. Пожалуйста, попробуйте снова.';
} elseif ($_POST && is_object($ticket) && $ticket->getId()) {
        $errors = array();
        switch (strtolower($_POST['a'])) {
            case 'postmessage':
                if (strcasecmp($thisclient->getEmail(), $ticket->getEmail())) {
                    $errors['err'] = 'Доступ запрещён. Возможно, неверный ID заявки';
                    $inc = 'tickets.inc.php';
                }

                if (!$_POST['message'])
                    $errors['message'] = 'Сообщение не должно быть пустым!';
                if ($_FILES['attachment']['name']) {
                    if (!$cfg->allowOnlineAttachments())
                        $errors['attachment'] = 'File [ ' . $_FILES['attachment']['name'] . ' ] rejected';
                    elseif (!$cfg->canUploadFileType($_FILES['attachment']['name']))
                        $errors['attachment'] = 'Неверный тип файла [ ' . $_FILES['attachment']['name'] . ' ]';
                    elseif ($_FILES['attachment']['size'] > $cfg->getMaxFileSize())
                        $errors['attachment'] = 'Файл слишком большой. Разрешенный размер: ' . $cfg->getMaxFileSize() . ' байт';
                }

                if (!$errors) {

                    if (($msgid = $ticket->postMessage($_POST['message'], 'Web'))) {
                        if ($_FILES['attachment']['name'] && $cfg->canUploadFiles() && $cfg->allowOnlineAttachments())
                            $ticket->uploadAttachment($_FILES['attachment'], $msgid, 'M');

                        $msg = 'Сообщение успешно отправлено';
                    } else {
                        $errors['err'] = 'Невозможно отправить сообщение. Попробуйте еще раз';
                    }
                } else {
                    $errors['err'] = $errors['err'] ? $errors['err'] : 'Error(s) occured. Please try again';
                }
                break;
            default:
                $errors['err'] = 'Неизвестное действие';
        }
    $ticket->reload();
}
Misc::rotateCSRFToken();
include(CLIENTINC_DIR.'header.inc.php');
include(CLIENTINC_DIR.$inc);
include(CLIENTINC_DIR.'footer.inc.php');
?>
