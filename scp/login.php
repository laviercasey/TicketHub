<?php
require_once('../main.inc.php');
if(!defined('INCLUDE_DIR')) die('Fatal Error. Kwaheri!');

require_once(INCLUDE_DIR.'class.staff.php');

$msg=$_SESSION['_staff']['auth']['msg'] ?? '';
$msg=$msg?$msg:'Authentication Required';
$errors=array();
if($_POST && (!empty($_POST['username']) && !empty($_POST['passwd']))){
    if(!Misc::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors['err']='Security error. Please try again.';
    }
    $msg='Invalid login';
    if($_SESSION['_staff']['laststrike'] ?? null) {
        if((time()-$_SESSION['_staff']['laststrike'])<$cfg->getStaffLoginTimeout()) {
            $msg='Excessive failed login attempts';
            $errors['err']='You\'ve reached maximum failed login attempts allowed.';
        }else{
            $_SESSION['_staff']['laststrike']=null;
            $_SESSION['_staff']['strikes']=0;
        }
    }
    if(!$errors && ($user=new StaffSession($_POST['username'])) && $user->getId() && $user->check_passwd($_POST['passwd'])){
        db_query('UPDATE '.STAFF_TABLE.' SET lastlogin=NOW() WHERE staff_id='.db_input($user->getId()));
        $dest=$_SESSION['_staff']['auth']['dest'] ?? '';

        $_SESSION['_staff']=array();
        $_SESSION['_staff']['userID']=$_POST['username'];
        $user->refreshSession();
        $_SESSION['TZ_OFFSET']=$user->getTZoffset();
        $_SESSION['daylight']=$user->observeDaylight();
        Sys::log(LOG_DEBUG,'Staff login',sprintf("%s logged in [%s]",$user->getUserName(),$_SERVER['REMOTE_ADDR']));
        $dest=($dest && (!strstr($dest,'login.php') && !strstr($dest,'dispatch.php') && !preg_match('#^(https?://|//|javascript:)#i', $dest)))?$dest:'index.php';
        session_regenerate_id(true);
        Misc::rotateCSRFToken();
        session_write_close();
        @header("Location: $dest");
        require_once('index.php');
        exit;
    }
    $_SESSION['_staff']['strikes']+=1;
    if(!$errors && $_SESSION['_staff']['strikes']>$cfg->getStaffMaxLogins()) {
        $msg='Доступ запрещён';
        $errors['err']='Забыли данные для входа? Обратитесь в ИТ-отдел.';
        $_SESSION['_staff']['laststrike']=time();
        $redacted_user = substr($_POST['username'], 0, 3) . str_repeat('*', max(0, strlen($_POST['username']) - 3));
        $alert='Excessive login attempts by a staff member?'."\n".
               'Username: '.$redacted_user."\n".'IP: '.$_SERVER['REMOTE_ADDR']."\n".'TIME: '.date('M j, Y, g:i a T')."\n\n".
               'Attempts #'.$_SESSION['_staff']['strikes']."\n".'Timeout: '.($cfg->getStaffLoginTimeout()/60)." minutes \n\n";
        Sys::log(LOG_ALERT,'Excessive login attempts (staff)',$alert,($cfg->alertONLoginError()));
    }elseif($_SESSION['_staff']['strikes']%2==0){
        $redacted_user = substr($_POST['username'], 0, 3) . str_repeat('*', max(0, strlen($_POST['username']) - 3));
        $alert='Username: '.$redacted_user."\n".'IP: '.$_SERVER['REMOTE_ADDR'].
               "\n".'TIME: '.date('M j, Y, g:i a T')."\n\n".'Attempts #'.$_SESSION['_staff']['strikes'];
        Sys::log(LOG_WARNING,'Failed login attempt (staff)',$alert);
    }
}
if(!defined("OSTSCPINC")) define("OSTSCPINC",TRUE);
$login_err=($_POST)?true:false;
include_once(INCLUDE_DIR.'staff/login.tpl.php');
?>
