<?php
$is_cli = (php_sapi_name() == 'cli' || !isset($_SERVER['HTTP_HOST']));

if (!$is_cli) {
    $cron_token = defined('CRON_TOKEN') ? CRON_TOKEN : '';
    if (!$cron_token || !isset($_GET['token']) || $_GET['token'] !== $cron_token) {
        header('HTTP/1.1 403 Forbidden');
        die('Доступ запрещён: Неверный или отсутствующий токен cron');
    }
}

if (!file_exists('../main.inc.php')) {
    if ($is_cli) {
        echo "Критическая ошибка: main.inc.php не найден\n";
    }
    die('Критическая ошибка');
}

define('ROOT_PATH', '../');
require_once('../main.inc.php');

if (!defined('INCLUDE_DIR')) {
    if ($is_cli) {
        echo "Fatal error: INCLUDE_DIR not defined\n";
    }
    die('Критическая ошибка');
}

if (!defined('TASK_RECURRING_TABLE'))
    define('TASK_RECURRING_TABLE', TABLE_PREFIX . 'task_recurring');
if (!defined('TASKS_TABLE'))
    define('TASKS_TABLE', TABLE_PREFIX . 'tasks');
if (!defined('TASK_BOARDS_TABLE'))
    define('TASK_BOARDS_TABLE', TABLE_PREFIX . 'task_boards');
if (!defined('TASK_LISTS_TABLE'))
    define('TASK_LISTS_TABLE', TABLE_PREFIX . 'task_lists');
if (!defined('TASK_ASSIGNEES_TABLE'))
    define('TASK_ASSIGNEES_TABLE', TABLE_PREFIX . 'task_assignees');
if (!defined('TASK_COMMENTS_TABLE'))
    define('TASK_COMMENTS_TABLE', TABLE_PREFIX . 'task_comments');
if (!defined('TASK_ATTACHMENTS_TABLE'))
    define('TASK_ATTACHMENTS_TABLE', TABLE_PREFIX . 'task_attachments');
if (!defined('TASK_ACTIVITY_LOG_TABLE'))
    define('TASK_ACTIVITY_LOG_TABLE', TABLE_PREFIX . 'task_activity_log');
if (!defined('TASK_TAG_ASSOC_TABLE'))
    define('TASK_TAG_ASSOC_TABLE', TABLE_PREFIX . 'task_tag_associations');
if (!defined('TASK_CUSTOM_VALUES_TABLE'))
    define('TASK_CUSTOM_VALUES_TABLE', TABLE_PREFIX . 'task_custom_values');
if (!defined('TASK_TIME_LOGS_TABLE'))
    define('TASK_TIME_LOGS_TABLE', TABLE_PREFIX . 'task_time_logs');
if (!defined('TASK_TEMPLATES_TABLE'))
    define('TASK_TEMPLATES_TABLE', TABLE_PREFIX . 'task_templates');

require_once(INCLUDE_DIR . 'class.task.php');
require_once(INCLUDE_DIR . 'class.taskrecurring.php');

$count = TaskRecurring::processRecurring();

$message = date('Y-m-d H:i:s') . ' - Создано задач: ' . $count;

if ($is_cli) {
    echo $message . "\n";
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
}
?>
