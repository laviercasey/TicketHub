<?php
    if(!strcasecmp(basename($_SERVER['SCRIPT_NAME']),basename(__FILE__))) die('kwaheri rafiki!');

    ini_set('allow_url_fopen', 0);
    ini_set('allow_url_include', 0);

    ini_set('session.use_trans_sid', 0);
    ini_set('session.cache_limiter', 'nocache');

    error_reporting(E_ALL ^ E_NOTICE);
    ini_set('display_errors',0);
    ini_set('display_startup_errors',0);

    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 1 : 0);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', 86400);
    ini_set('session.cookie_lifetime', 0);
    session_start();

    if(!defined('ROOT_PATH')) define('ROOT_PATH','./');
    define('ROOT_DIR',str_replace('\\\\', '/', realpath(dirname(__FILE__))).'/');
    define('INCLUDE_DIR',ROOT_DIR.'include/');
    define('SETUP_DIR',INCLUDE_DIR.'setup/');

    require_once ROOT_DIR.'vendor/autoload.php';

    define('THIS_VERSION','1.0');

    $envFile = ROOT_DIR . '.env';
    if(file_exists($envFile)) {
        $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach($envLines as $envLine) {
            $envLine = trim($envLine);
            if($envLine === '' || $envLine[0] === '#') continue;
            if(strpos($envLine, '=') === false) continue;
            list($envKey, $envVal) = explode('=', $envLine, 2);
            $envKey = trim($envKey);
            $envVal = trim(trim($envVal), "\"'");
            if(!getenv($envKey)) {
                putenv("$envKey=$envVal");
            }
        }
        unset($envLines, $envLine, $envKey, $envVal, $envFile);
    }

    $configfile='';
    if(file_exists(INCLUDE_DIR.'th-config.php'))
        $configfile=INCLUDE_DIR.'th-config.php';

    if(!$configfile || !file_exists($configfile)) {
        header('Location: '.ROOT_PATH.'setup/');
        exit;
    }

    require($configfile);
    define('CONFIG_FILE',$configfile);

    if(!defined('THINSTALLED') || !THINSTALLED) {
        header('Location: '.ROOT_PATH.'setup/');
        exit;
    }

    if(!defined('PATH_SEPARATOR')){
        if(strpos($_ENV['OS'] ?? '','Win')!==false || !strcasecmp(substr(PHP_OS, 0, 3),'WIN'))
            define('PATH_SEPARATOR', ';' );
        else
            define('PATH_SEPARATOR',':');
    }

    ini_set('include_path', './'.PATH_SEPARATOR.INCLUDE_DIR);

    require(INCLUDE_DIR.'class.usersession.php');
    require(INCLUDE_DIR.'class.pagenate.php');
    require(INCLUDE_DIR.'class.sys.php');
    require(INCLUDE_DIR.'class.misc.php');
    require(INCLUDE_DIR.'class.http.php');
    require(INCLUDE_DIR.'class.format.php');
    require(INCLUDE_DIR.'class.validator.php');
    require(INCLUDE_DIR.'mysql.php');

    define('THISPAGE',Misc::currentURL());
    define('PAGE_LIMIT',20);

    if(!defined('SECRET_SALT') || !SECRET_SALT) {
        die('FATAL: SECRET_SALT is not configured. Set it in your .env file (openssl rand -hex 32).');
    }

    define('DEBUG_MODE', (bool)(getenv('APP_DEBUG') ?: false));
    define('SESSION_SECRET', hash('sha256', SECRET_SALT . 'session-secret-key'));
    define('SESSION_TTL', 86400);

    define('DEFAULT_PRIORITY_ID',1);
    define('EXT_TICKET_ID_LEN',6);

    define('CONFIG_TABLE',TABLE_PREFIX.'config');
    define('SYSLOG_TABLE',TABLE_PREFIX.'syslog');

    define('STAFF_TABLE',TABLE_PREFIX.'staff');
    define('DEPT_TABLE',TABLE_PREFIX.'department');
    define('TOPIC_TABLE',TABLE_PREFIX.'help_topic');
    define('GROUP_TABLE',TABLE_PREFIX.'groups');

    define('TICKET_TABLE',TABLE_PREFIX.'ticket');
	define('TICKET_TABLE_ARCHIVED',TABLE_PREFIX.'ticket_archived');
    define('TICKET_NOTE_TABLE',TABLE_PREFIX.'ticket_note');
    define('TICKET_MESSAGE_TABLE',TABLE_PREFIX.'ticket_message');
    define('TICKET_RESPONSE_TABLE',TABLE_PREFIX.'ticket_response');
    define('TICKET_ATTACHMENT_TABLE',TABLE_PREFIX.'ticket_attachment');
    define('TICKET_PRIORITY_TABLE',TABLE_PREFIX.'ticket_priority');
    define('TICKET_LOCK_TABLE',TABLE_PREFIX.'ticket_lock');

    define('EMAIL_TABLE',TABLE_PREFIX.'email');
    define('EMAIL_TEMPLATE_TABLE',TABLE_PREFIX.'email_template');
    define('BANLIST_TABLE',TABLE_PREFIX.'email_banlist');

    define('API_TOKEN_TABLE',TABLE_PREFIX.'api_tokens');
    define('API_LOG_TABLE',TABLE_PREFIX.'api_logs');
    define('API_RATE_LIMIT_TABLE',TABLE_PREFIX.'api_rate_limits');
    define('API_WEBHOOK_TABLE',TABLE_PREFIX.'api_webhooks');
    define('API_AUDIT_LOG_TABLE',TABLE_PREFIX.'api_audit_log');
    define('API_IP_BLACKLIST_TABLE',TABLE_PREFIX.'api_ip_blacklist');

    if(!defined('TASKS_TABLE'))
        define('TASKS_TABLE',TABLE_PREFIX.'tasks');
    if(!defined('TASK_BOARDS_TABLE'))
        define('TASK_BOARDS_TABLE',TABLE_PREFIX.'task_boards');
    if(!defined('TASK_LISTS_TABLE'))
        define('TASK_LISTS_TABLE',TABLE_PREFIX.'task_lists');
    if(!defined('TASK_BOARD_LISTS_TABLE'))
        define('TASK_BOARD_LISTS_TABLE',TABLE_PREFIX.'task_board_lists');
    if(!defined('TASK_ASSIGNEES_TABLE'))
        define('TASK_ASSIGNEES_TABLE',TABLE_PREFIX.'task_assignees');
    if(!defined('TASK_TAGS_TABLE'))
        define('TASK_TAGS_TABLE',TABLE_PREFIX.'task_tags');
    if(!defined('TASK_TAG_ASSOC_TABLE'))
        define('TASK_TAG_ASSOC_TABLE',TABLE_PREFIX.'task_tag_associations');
    if(!defined('TASK_ACTIVITY_LOG_TABLE'))
        define('TASK_ACTIVITY_LOG_TABLE',TABLE_PREFIX.'task_activity_log');
    if(!defined('TASK_RECURRING_TABLE'))
        define('TASK_RECURRING_TABLE',TABLE_PREFIX.'task_recurring');
    if(!defined('TASK_TIME_LOGS_TABLE'))
        define('TASK_TIME_LOGS_TABLE',TABLE_PREFIX.'task_time_logs');
    if(!defined('TASK_COMMENTS_TABLE'))
        define('TASK_COMMENTS_TABLE',TABLE_PREFIX.'task_comments');
    if(!defined('TASK_ATTACHMENTS_TABLE'))
        define('TASK_ATTACHMENTS_TABLE',TABLE_PREFIX.'task_attachments');
    if(!defined('TASK_CUSTOM_FIELDS_TABLE'))
        define('TASK_CUSTOM_FIELDS_TABLE',TABLE_PREFIX.'task_custom_fields');
    if(!defined('TASK_CUSTOM_VALUES_TABLE'))
        define('TASK_CUSTOM_VALUES_TABLE',TABLE_PREFIX.'task_custom_values');
    if(!defined('TASK_AUTOMATION_TABLE'))
        define('TASK_AUTOMATION_TABLE',TABLE_PREFIX.'task_automation_rules');
    if(!defined('KB_PREMADE_TABLE'))
        define('KB_PREMADE_TABLE',TABLE_PREFIX.'kb_premade');
    if(!defined('KB_DOCUMENTS_TABLE'))
        define('KB_DOCUMENTS_TABLE',TABLE_PREFIX.'kb_documents');
    if(!defined('KB_ATTACHMENTS_TABLE'))
        define('KB_ATTACHMENTS_TABLE',TABLE_PREFIX.'kb_attachments');

    define('TIMEZONE_TABLE',TABLE_PREFIX.'timezone');
    define('PRIORITY_USERS_TABLE',TABLE_PREFIX.'priority_users');

    $ferror=null;
    if (!db_connect(DBHOST,DBUSER,DBPASS) || !db_select_database(DBNAME)) {
        $ferror='Невозможно подключиться к базе данных';
    }elseif(!($cfg=Sys::getConfig())){
        $ferror='Невозможно загрузить конфигурацию из БД. Обратитесь в техподдержку.';
    }

    if($ferror){
        Sys::alertAdmin('TicketHub Fatal Error',$ferror);
        die("<b>Критическая ошибка:</b> Свяжитесь с администратором системы.");
        exit;
    }

    $cfg->init();
    $_SESSION['TZ_OFFSET']=$cfg->getTZoffset();
    $_SESSION['daylight']=$cfg->observeDaylightSaving();
?>
