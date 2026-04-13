<?php
require('client.inc.php');
define('SOURCE','Web');
$inc='open.inc.php';
$errors=array();
if ($_POST && !Misc::validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $errors['err'] = 'Ошибка проверки формы. Пожалуйста, попробуйте снова.';
} elseif ($_POST) {
    $_POST['deptId'] = $_POST['emailId'] = 0;
    if ($cfg->enableCaptcha() && (!$thisclient || !$thisclient->isValid())) {
        if (!$_POST['captcha'])
            $errors['captcha'] = 'Введите текст показаный на изображении';
        elseif (!hash_equals($_SESSION['captcha'] ?? '', strtoupper($_POST['captcha'])))
            $errors['captcha'] = 'Неверно - попробуйте еще раз!';
    }

    if (($ticket = Ticket::create($_POST, $errors, SOURCE))) {
        $msg = 'Запрос успешно создан';
        if ($thisclient && $thisclient->isValid())
            @header('Location: tickets.php?id=' . $ticket->getExtId());
        $inc = 'thankyou.inc.php';
    } else {
        $errors['err'] = !empty($errors['err']) ? $errors['err'] : 'Невозможно создать запрос. Пожалуйста исправьте ошибки и попробуйте еще раз!';
    }
}
Misc::rotateCSRFToken();

require(CLIENTINC_DIR.'header.inc.php');
require(CLIENTINC_DIR.$inc);
require(CLIENTINC_DIR.'footer.inc.php');
?>
