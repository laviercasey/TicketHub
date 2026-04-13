<?php
require('staff.inc.php');

ini_set('display_errors','0');
ini_set('display_startup_errors','0');


if(!defined('INCLUDE_DIR'))	Http::json(500, ['error' => 'Ошибка конфигурации']);

if(!$thisuser || !$thisuser->isValid()) {
	Http::json(401, ['error' => 'Доступ запрещён']);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $csrf_token = $_REQUEST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!Misc::validateCSRFToken($csrf_token)) {
        Http::json(403, ['error' => 'CSRF validation failed']);
        exit;
    }
}

if(!$_REQUEST['api'] || !$_REQUEST['f']){
    Http::json(416, ['error' => 'Invalid params']);
    exit;
}
define('OSTAJAXINC',TRUE);
$file='handler.'.Format::file_name(strtolower($_REQUEST['api'])).'.php';
if(!file_exists(INCLUDE_DIR.$file)){
    Http::json(405, ['error' => 'invalid method']);
    exit;
}

$class=ucfirst(strtolower($_REQUEST['api'])).'AjaxAPI';
$func=$_REQUEST['f'];

if(is_callable($func)){
Http::json(500, ['error' => 'Forbidden']);
exit;
}
require(INCLUDE_DIR.$file);

if(!method_exists($class,$func)){
 Http::json(416, ['error' => 'invalid method/call']);
 exit;
}

ob_start();
$obj = new $class();
$response=call_user_func(array($obj,$func),$_REQUEST);
ob_end_clean();

Http::response(200,$response,'application/json');
exit;
?>
