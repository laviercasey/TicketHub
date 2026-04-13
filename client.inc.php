<?php
if(!strcasecmp(basename($_SERVER['SCRIPT_NAME']),basename(__FILE__))) die('kwaheri rafiki!');

if(!file_exists('main.inc.php')) die('Критическая ошибка.');

require_once('main.inc.php');

if(!defined('INCLUDE_DIR')) die('Критическая ошибка');

define('CLIENTINC_DIR',INCLUDE_DIR.'client/');
define('OSTCLIENTINC',TRUE);

if(!is_object($cfg) || !$cfg->getId() || $cfg->isHelpDeskOffline()) {
    include('./offline.php');
    exit;
}

if(defined('THIS_VERSION') && strcasecmp($cfg->getVersion(),THIS_VERSION)) {
    die('Система отключена для обновления.');
    exit;
}

require_once(INCLUDE_DIR.'class.client.php');
require_once(INCLUDE_DIR.'class.ticket.php');
require_once(INCLUDE_DIR.'class.dept.php');

$errors=array();
$msg='';
$thisclient=null;
if($_SESSION['_client']['userID'] && $_SESSION['_client']['key'])
    $thisclient = new ClientSession($_SESSION['_client']['userID'],$_SESSION['_client']['key']);

if($thisclient && $thisclient->getId() && $thisclient->isValid()){
     $thisclient->refreshSession();
}

?>
