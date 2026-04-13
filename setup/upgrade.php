<?php
if(!file_exists('../main.inc.php')) die('Fatal error..get tech support');
require_once('../main.inc.php');
require_once('setup.inc.php');
require_once(INCLUDE_DIR.'class.staff.php');

$thisuser = new StaffSession($_SESSION['_staff']['userID']);
if(!is_object($thisuser) || !$thisuser->getId() || !$thisuser->isValid() || !$thisuser->isadmin()){
    $_SESSION['_staff']['auth']['dest']=THISPAGE;
    $_SESSION['_staff']['auth']['msg']='Admin access level required.';
    session_write_close();
    session_regenerate_id();
    header('Location: ../scp/login.php');
    exit;
}

$errors=array();
$fp=null;
define('VERSION','1.0');
define('PREFIX',TABLE_PREFIX);

$info='<strong>Нужна помощь?</strong> <a href="https://github.com/LaverCasey/TicketHub" target="_blank">TicketHub Support</a>';
$inc='upgrade.inc.php';
if(!strcasecmp($cfg->getVersion(),VERSION)) {
    $errors['err']='Обновление не требуется! Система уже обновлена до версии '.VERSION;
    $inc='upgradedone.inc.php';
}elseif($_SESSION['abort']){
    die('Обновление прервано! Восстановите предыдущую версию и попробуйте снова (требуется выход из системы).');
}elseif(version_compare(PHP_VERSION, '8.0.0', '<')){
    $errors['err']='Требуется PHP 8.0 или новее. Текущая версия: '.PHP_VERSION;
    $inc='php.inc.php';
}elseif($_POST && !$errors){
    db_query('UPDATE '.CONFIG_TABLE.' SET thversion='.db_real_escape(VERSION, true));

    $log = sprintf("TicketHub обновлён до версии %s пользователем %s", VERSION, $thisuser->getName());
    $sql = 'INSERT INTO '.PREFIX.'syslog SET created=NOW(),updated=NOW() '.
         ',title="TicketHub upgraded!",log_type="Debug" '.
         ',log='.db_input($log).
         ',ip_address='.db_input($_SERVER['REMOTE_ADDR']);
    db_query($sql);

    $inc='upgradedone.inc.php';
    $msg='TicketHub обновлён до версии '.VERSION;
}
$title=sprintf('TicketHub upgrade wizard v %s','1.0');
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<title>Обновление TicketHub</title>
<link rel="stylesheet" href="style.css" media="screen">
</head>
<body>
<div id="container">
    <div id="header">
        <a id="logo" href="#" title="TicketHub"><img src="images/logo.svg" alt="TicketHub Upgrade Wizard"></a>
        <p id="info"><?=$info?></p>
    </div>
    <div id="nav">
        <ul id="sub_nav">
            <li><?=$title?></li>
        </ul>
    </div>
    <div class="clear"></div>
    <div id="content" width="100%" height="100%">
       <div>
            <?php if($errors['err']) {?>
                <p align="center" id="errormessage"><?=$errors['err']?></p>
            <?php }elseif($msg) {?>
                <p align="center" id="infomessage"><?=$msg?></p>
            <?php }elseif($warn) {?>
                <p align="center" id="warnmessage"><?=$warn?></p>
            <?php }?>
        </div>
        <div style="padding:0 3px 5px 3px;">
        <?php
            if(file_exists("./inc/$inc"))
                require("./inc/$inc");
            else
                echo '<span class="error">Invalid path - get technical support</span>';
        ?>
        </div>
    </div>
    <div id="footer">Copyright &copy; <?=date('Y')?>&nbsp;TicketHub.Ru. &nbsp;All Rights Reserved.</div>
</div>
</body>
</html>
