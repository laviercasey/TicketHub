<?php
require_once('staff.inc.php');
$msg='';
if($_POST && $_POST['id']!=$thisuser->getId()) {
 $errors['err']='Internal Error. Action Denied';
}

if(!$errors && $_POST) {
    if(!Misc::validateCSRFToken($_POST['csrf_token'])) {
        $errors['err']='Invalid form submission. Please try again.';
    }

    switch(strtolower($_REQUEST['t'])):
    case 'pref':
        if(!is_numeric($_POST['auto_refresh_rate']))
            $errors['err']='Invalid auto refresh value.';

        if(!$errors) {

            $sql='UPDATE '.STAFF_TABLE.' SET updated=NOW() '.
                ',daylight_saving='.db_input(isset($_POST['daylight_saving'])?1:0).
                ',max_page_size='.db_input($_POST['max_page_size']).
                ',auto_refresh_rate='.db_input($_POST['auto_refresh_rate']).
                ',timezone_offset='.db_input($_POST['timezone_offset']).
                ' WHERE staff_id='.db_input($thisuser->getId());

            if(db_query($sql) && db_affected_rows()){
                $thisuser->reload();
                $_SESSION['TZ_OFFSET']=$thisuser->getTZoffset();
                $_SESSION['daylight']=$thisuser->observeDaylight();
                $msg='Preference Updated Successfully';
            }else{
                $errors['err']='Preference update error.';
            }
        }
        break;
    case 'passwd':
        if(!$_POST['password'])
            $errors['password']='Current password required';
        if(!$_POST['npassword'])
            $errors['npassword']='New password required';
        elseif(strlen($_POST['npassword'])<6)
             $errors['npassword']='Must be atleast 6 characters';
        if(!$_POST['vpassword'])
            $errors['vpassword']='Confirm new password';
        if(!$errors) {
            if(!$thisuser->check_passwd($_POST['password'])){
                $errors['password']='Valid password required';
            }elseif(strcmp($_POST['npassword'],$_POST['vpassword'])){
                $errors['npassword']=$errors['vpassword']='New password(s) don\'t match';
            }elseif(!strcasecmp($_POST['password'],$_POST['npassword'])){
                $errors['npassword']='New password is same as old password';
            }
        }
        if(!$errors) {
            $sql='UPDATE '.STAFF_TABLE.' SET updated=NOW() '.
                ',change_passwd=0, passwd='.db_input(password_hash($_POST['npassword'], PASSWORD_DEFAULT)).
                ' WHERE staff_id='.db_input($thisuser->getId());
            if(db_query($sql) && db_affected_rows()){
                $msg='Пароль успешно изменен';
            }else{
                $errors['err']='Unable to complete password change. Internal error.';
            }
        }
        break;
    case 'info':
        if(!$_POST['firstname']) {
            $errors['firstname']='First name required';
        }
        if(!$_POST['lastname']) {
            $errors['lastname']='Last name required';
        }
        if(!$_POST['email'] || !Validator::is_email($_POST['email'])) {
            $errors['email']='Valid email required';
        }
        if($_POST['phone'] && !Validator::is_phone($_POST['phone'])) {
            $errors['phone']='Enter a valid number';
        }
        if($_POST['mobile'] && !Validator::is_phone($_POST['mobile'])) {
            $errors['mobile']='Enter a valid number';
        }

        if($_POST['phone_ext'] && !is_numeric($_POST['phone_ext'])) {
            $errors['phone_ext']='Invalid ext.';
        }

        if(!$errors) {

            $sql='UPDATE '.STAFF_TABLE.' SET updated=NOW() '.
                ',firstname='.db_input(Format::striptags($_POST['firstname'])).
                ',lastname='.db_input(Format::striptags($_POST['lastname'])).
                ',email='.db_input($_POST['email']).
                ',phone="'.db_input($_POST['phone'],false).'"'.
                ',phone_ext='.db_input($_POST['phone_ext']).
                ',mobile="'.db_input($_POST['mobile'],false).'"'.
                ',signature='.db_input(Format::striptags($_POST['signature'])).
                ' WHERE staff_id='.db_input($thisuser->getId());
            if(db_query($sql) && db_affected_rows()){
                $msg='Profile Updated Successfully';
            }else{
                $errors['err']='Error(s) occured. Profile NOT updated';
            }
        }else{
            $errors['err']='Error(s) below occured. Try again';
        }
        break;
    default:
        $errors['err']='Uknown action';
    endswitch;
    if(!$errors) {
        $thisuser->reload();
        $_SESSION['TZ_OFFSET']=$thisuser->getTZoffset();
        $_SESSION['daylight']=$thisuser->observeDaylight();
    }
}

$nav->setTabActive('profile');
$nav->addSubMenu(array('desc'=>'Мой профиль','href'=>'profile.php','iconclass'=>'user'));
$nav->addSubMenu(array('desc'=>'Настройки','href'=>'profile.php?t=pref','iconclass'=>'userPref'));
$nav->addSubMenu(array('desc'=>'Изменить Пароль','href'=>'profile.php?t=passwd','iconclass'=>'userPasswd'));
if($thisuser->onVacation()){
        $warn.='Добро пожаловать! You are listed as \'on vacation\' Please let admin or your manager know that you are back.';
}

$rep=($errors && $_POST)?Format::input($_POST):Format::htmlchars($thisuser->getData());

$inc='myprofile.inc.php';
switch(strtolower($_REQUEST['t'])) {
    case 'pref':
        $inc='mypref.inc.php';
        break;
    case 'passwd':
        $inc='changepasswd.inc.php';
        break;
    case 'info':
    default:
        $inc='myprofile.inc.php';
}
if($thisuser->forcePasswdChange()){
    $errors['err']='Вы должны изменить ваш пароль для продолжения.';
    $inc='changepasswd.inc.php';
}

require_once(STAFFINC_DIR.'header.inc.php');
?>
<?php if (@$errors['err']) { ?>
<div class="alert-danger mb-4"><i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0"></i><span><?= Format::htmlchars($errors['err']) ?></span></div>
<?php } elseif ($msg) { ?>
<div class="alert-success mb-4"><i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i><span><?= Format::htmlchars($msg) ?></span></div>
<?php } elseif ($warn) { ?>
<div class="alert-warning mb-4"><i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i><span><?= Format::htmlchars($warn) ?></span></div>
<?php } ?>
<?php require(STAFFINC_DIR . $inc); ?>
<?
require_once(STAFFINC_DIR.'footer.inc.php');
?>
