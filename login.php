<?php
require_once('main.inc.php');
if(!defined('INCLUDE_DIR')) die('Критическая ошибка');
define('CLIENTINC_DIR',INCLUDE_DIR.'client/');
define('OSTCLIENTINC',TRUE);

require_once(INCLUDE_DIR.'class.client.php');
require_once(INCLUDE_DIR.'class.ticket.php');
$loginmsg='Необходима авторизация';
if($_POST && (!empty($_POST['lemail']) && !empty($_POST['lticket']))):
    if(!Misc::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors['err']='Ошибка безопасности. Попробуйте ещё раз.';
    }
    $loginmsg='Необходима авторизация';
    $email=trim($_POST['lemail']);
    $ticketID=trim($_POST['lticket']);

    $loginmsg='Неверно указан е-маил или ID';
    if($_SESSION['_client']['laststrike']) {
        if((time()-$_SESSION['_client']['laststrike'])<$cfg->getClientLoginTimeout()) {
            $loginmsg='Чрезмерные неудачные попытки входа';
            $errors['err']='Вы исчерпали максимальное кол-во попыток входа. Попробуйте создать <a href="open.php">новую заявку</a>';
        }else{
            $_SESSION['_client']['laststrike']=null;
            $_SESSION['_client']['strikes']=0;
        }
    }
    if(!$errors && is_numeric($ticketID) && Validator::is_email($email) && ($tid=Ticket::getIdByExtId($ticketID))) {
        $ticket= new Ticket($tid);
        if($ticket->getId() && strcasecmp($ticket->getEMail(),$email)==0){

            $user = new ClientSession($email,$ticket->getId());
            $_SESSION['_client']=array();
            $_SESSION['_client']['userID']   =$ticket->getEmail();
            $_SESSION['_client']['key']      =$ticket->getExtId();
            $_SESSION['_client']['token']    =$user->getSessionToken();
            $_SESSION['TZ_OFFSET']=$cfg->getTZoffset();
            $_SESSION['daylight']=$cfg->observeDaylightSaving();
            $msg=sprintf("%s/%s logged in [%s]",$ticket->getEmail(),$ticket->getExtId(),$_SERVER['REMOTE_ADDR']);
            Sys::log(LOG_DEBUG,'User login',$msg);
            session_regenerate_id(true);
            Misc::rotateCSRFToken();
            session_write_close();
            @header("Location: tickets.php");
            require_once('tickets.php');
            exit;
        }
    }
    $_SESSION['_client']['strikes']+=1;
    if(!$errors && $_SESSION['_client']['strikes']>$cfg->getClientMaxLogins()) {
        $loginmsg='Доступ Запрещен';
        $errors['err']='Забыли данные для входа? Обратитесь в ИТ-отдел.';
        $_SESSION['_client']['laststrike']=time();
        $redacted_email = substr($_POST['lemail'], 0, 3) . str_repeat('*', max(0, strlen($_POST['lemail']) - 3));
        $alert='Частая попытка входа клиента?'."\n".
                'Email: '.$redacted_email."\n".'Ticket#: ***'."\n".
                'IP: '.$_SERVER['REMOTE_ADDR']."\n".'Время:'.date('M j, Y, g:i a T')."\n\n".
                'Попыток #'.$_SESSION['_client']['strikes'];
        Sys::log(LOG_ALERT,'Частая попытка входа (клиент)',$alert,($cfg->alertONLoginError()));
    }elseif($_SESSION['_client']['strikes']%2==0){
        $redacted_email = substr($_POST['lemail'], 0, 3) . str_repeat('*', max(0, strlen($_POST['lemail']) - 3));
        $alert='Email: '.$redacted_email."\n".'Запрос #: ***'."\n".'IP: '.$_SERVER['REMOTE_ADDR'].
               "\n".'Время: '.date('M j, Y, g:i a T')."\n\n".'Попыток #'.$_SESSION['_client']['strikes'];
        Sys::log(LOG_WARNING,'Неудачная попытка входа (клиент)',$alert);
    }
endif;
require(CLIENTINC_DIR.'header.inc.php');
require(CLIENTINC_DIR.'login.inc.php');
require(CLIENTINC_DIR.'footer.inc.php');
?>
