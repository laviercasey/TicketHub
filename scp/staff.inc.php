<?php
if(basename($_SERVER['SCRIPT_NAME'])==basename(__FILE__)) die('Kwaheri rafiki!');
if(!file_exists('../main.inc.php')) die('Критическая ошибка. Обратитесь в техподдержку');
define('ROOT_PATH','../');
require_once('../main.inc.php');

if(!defined('INCLUDE_DIR')) die('Критическая ошибка');

define('STAFFINC_DIR',INCLUDE_DIR.'staff/');
define('SCP_DIR',str_replace('//','/',dirname(__FILE__).'/'));

define('OSTSCPINC',TRUE);
define('OSTSTAFFINC',TRUE);

define('KB_PREMADE_TABLE',TABLE_PREFIX.'kb_premade');
define('KB_DOCUMENTS_TABLE',TABLE_PREFIX.'kb_documents');

define('TASK_BOARDS_TABLE',TABLE_PREFIX.'task_boards');
define('TASK_LISTS_TABLE',TABLE_PREFIX.'task_lists');
define('TASKS_TABLE',TABLE_PREFIX.'tasks');
define('TASK_ASSIGNEES_TABLE',TABLE_PREFIX.'task_assignees');
define('TASK_TAGS_TABLE',TABLE_PREFIX.'task_tags');
define('TASK_TAG_ASSOC_TABLE',TABLE_PREFIX.'task_tag_associations');
define('TASK_CUSTOM_FIELDS_TABLE',TABLE_PREFIX.'task_custom_fields');
define('TASK_CUSTOM_VALUES_TABLE',TABLE_PREFIX.'task_custom_values');
define('TASK_ATTACHMENTS_TABLE',TABLE_PREFIX.'task_attachments');
define('TASK_COMMENTS_TABLE',TABLE_PREFIX.'task_comments');
define('TASK_TIME_LOGS_TABLE',TABLE_PREFIX.'task_time_logs');
define('TASK_ACTIVITY_LOG_TABLE',TABLE_PREFIX.'task_activity_log');
define('TASK_AUTOMATION_TABLE',TABLE_PREFIX.'task_automation_rules');
define('TASK_RECURRING_TABLE',TABLE_PREFIX.'task_recurring');
define('TASK_TEMPLATES_TABLE',TABLE_PREFIX.'task_templates');
define('TASK_SAVED_FILTERS_TABLE',TABLE_PREFIX.'task_saved_filters');
define('TASK_BOARD_PERMS_TABLE',TABLE_PREFIX.'task_board_permissions');

define('LOCATIONS_TABLE',TABLE_PREFIX.'locations');
define('INVENTORY_CATEGORIES_TABLE',TABLE_PREFIX.'inventory_categories');
define('INVENTORY_BRANDS_TABLE',TABLE_PREFIX.'inventory_brands');
define('INVENTORY_MODELS_TABLE',TABLE_PREFIX.'inventory_models');
define('INVENTORY_ITEMS_TABLE',TABLE_PREFIX.'inventory_items');
define('INVENTORY_HISTORY_TABLE',TABLE_PREFIX.'inventory_history');


require_once(INCLUDE_DIR.'class.staff.php');
require_once(INCLUDE_DIR.'class.nav.php');


function staffLoginPage($msg) {
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
              || (isset($_REQUEST['api']) && isset($_REQUEST['f']));
    if ($isAjax) {
        header('HTTP/1.1 401 Unauthorized');
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $_SESSION['_staff']['auth']['dest']=THISPAGE;
    $_SESSION['_staff']['auth']['msg']=$msg;
    require(SCP_DIR.'login.php');
    exit;
}

$thisuser = new StaffSession($_SESSION['_staff']['userID'] ?? 0);
if(!is_object($thisuser) || !$thisuser->getId() || !$thisuser->isValid()){
    $msg=(!$thisuser || !$thisuser->isValid())?'Необходима авторизация':'Session timed out due to inactivity';
    staffLoginPage($msg);
    exit;
}

if(!$thisuser->isadmin()){
    if($cfg->isHelpDeskOffline()){
        staffLoginPage('Система Отключена');
        exit;
    }

    if(!$thisuser->isactive() || !$thisuser->isGroupActive()) {
        staffLoginPage('Доступ запрещён. Обратитесь к администратору');
        exit;
    }
}

$thisuser->refreshSession();
$_SESSION['TZ_OFFSET']=$thisuser->getTZoffset();
$_SESSION['daylight']=$thisuser->observeDaylight();

define('AUTO_REFRESH_RATE',$thisuser->getRefreshRate()*60);

$errors=array();
$msg=$warn=$sysnotice='';
$tabs=array();
$submenu=array();

if(defined('THIS_VERSION') && strcasecmp($cfg->getVersion(),THIS_VERSION)) {
    $errors['err']=$sysnotice=sprintf('Скрипт версии %s различается с версией базы данных %s',THIS_VERSION,$cfg->getVersion());
}elseif($cfg->isHelpDeskOffline()){
    $sysnotice='<strong>Система отключена</strong> - Интерфейс клиента отключен и ТОЛЬКО администратор имеет доступ в панель управления.';
    $sysnotice.=' <a href="admin.php?t=pref">Включить</a>.';
}

$nav = new StaffNav(strcasecmp(basename($_SERVER['SCRIPT_NAME']),'admin.php')?'staff':'admin');
if($thisuser->forcePasswdChange()){
    require('profile.php');
    exit;
}


?>
