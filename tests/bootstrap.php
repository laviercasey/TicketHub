<?php

define('ROOT_DIR', dirname(__DIR__) . '/');
define('INCLUDE_DIR', ROOT_DIR . 'include/');

require_once ROOT_DIR . 'vendor/autoload.php';
define('TABLE_PREFIX', 'th_');

define('TICKET_TABLE', TABLE_PREFIX . 'ticket');
define('TICKET_MESSAGE_TABLE', TABLE_PREFIX . 'ticket_message');
define('TICKET_RESPONSE_TABLE', TABLE_PREFIX . 'ticket_response');
define('TICKET_NOTE_TABLE', TABLE_PREFIX . 'ticket_note');
define('TICKET_ATTACHMENT_TABLE', TABLE_PREFIX . 'ticket_attachment');
define('TICKET_PRIORITY_TABLE', TABLE_PREFIX . 'ticket_priority');
define('TICKET_LOCK_TABLE', TABLE_PREFIX . 'ticket_lock');
define('DEPT_TABLE', TABLE_PREFIX . 'department');
define('STAFF_TABLE', TABLE_PREFIX . 'staff');
define('GROUP_TABLE', TABLE_PREFIX . 'group');
define('TOPIC_TABLE', TABLE_PREFIX . 'help_topic');
define('CONFIG_TABLE', TABLE_PREFIX . 'config');
define('EMAIL_TABLE', TABLE_PREFIX . 'email');
define('EMAIL_TEMPLATE_TABLE', TABLE_PREFIX . 'email_template');
define('BANLIST_TABLE', TABLE_PREFIX . 'email_banlist');
define('SYSLOG_TABLE', TABLE_PREFIX . 'syslog');

define('TASKS_TABLE', TABLE_PREFIX . 'tasks');
define('TASK_BOARDS_TABLE', TABLE_PREFIX . 'task_boards');
define('TASK_LISTS_TABLE', TABLE_PREFIX . 'task_lists');
define('TASK_ASSIGNEES_TABLE', TABLE_PREFIX . 'task_assignees');
define('TASK_COMMENTS_TABLE', TABLE_PREFIX . 'task_comments');
define('TASK_TAG_ASSOC_TABLE', TABLE_PREFIX . 'task_tag_assoc');
define('TASK_CUSTOM_VALUES_TABLE', TABLE_PREFIX . 'task_custom_values');
define('TASK_TIME_LOGS_TABLE', TABLE_PREFIX . 'task_time_logs');

define('API_TOKEN_TABLE', TABLE_PREFIX . 'api_tokens');
define('API_AUDIT_LOG_TABLE', TABLE_PREFIX . 'api_audit_log');
define('API_IP_BLACKLIST_TABLE', TABLE_PREFIX . 'api_ip_blacklist');

define('EXT_TICKET_ID_LEN', 6);
define('THIS_VERSION', '1.0');
define('THISPAGE', '/index.php');
define('DEBUG_MODE', false);

if (!defined('SECRET_SALT')) {
    define('SECRET_SALT', 'test-secret-salt-for-phpunit');
}

define('TASK_ACTIVITY_TABLE', TABLE_PREFIX . 'task_activity');
define('TASK_ACTIVITY_LOG_TABLE', TABLE_PREFIX . 'task_activity_log');
define('TASK_ATTACHMENTS_TABLE', TABLE_PREFIX . 'task_attachments');
define('TASK_TAGS_TABLE', TABLE_PREFIX . 'task_tags');
define('TASK_CUSTOM_FIELDS_TABLE', TABLE_PREFIX . 'task_custom_fields');
define('TASK_CHECKLIST_TABLE', TABLE_PREFIX . 'task_checklist');
define('TASK_AUTOMATION_TABLE', TABLE_PREFIX . 'task_automation');
define('TASK_RECURRING_TABLE', TABLE_PREFIX . 'task_recurring');
define('TASK_TEMPLATES_TABLE', TABLE_PREFIX . 'task_templates');
define('TASK_BOARD_PERMS_TABLE', TABLE_PREFIX . 'task_board_permissions');
define('TASK_SAVED_FILTERS_TABLE', TABLE_PREFIX . 'task_saved_filters');

define('INVENTORY_ITEMS_TABLE', TABLE_PREFIX . 'inventory_items');
define('INVENTORY_CATEGORIES_TABLE', TABLE_PREFIX . 'inventory_categories');
define('INVENTORY_BRANDS_TABLE', TABLE_PREFIX . 'inventory_brands');
define('INVENTORY_MODELS_TABLE', TABLE_PREFIX . 'inventory_models');
define('INVENTORY_HISTORY_TABLE', TABLE_PREFIX . 'inventory_history');
define('LOCATIONS_TABLE', TABLE_PREFIX . 'locations');

define('KB_DOCUMENTS_TABLE', TABLE_PREFIX . 'kb_documents');
define('PRIORITY_USERS_TABLE', TABLE_PREFIX . 'priority_users');

define('API_LOG_TABLE', TABLE_PREFIX . 'api_log');
define('API_RATE_LIMIT_TABLE', TABLE_PREFIX . 'api_rate_limit');

if (!defined('SESSION_SECRET')) {
    define('SESSION_SECRET', 'test-session-secret-for-phpunit');
}

if (!defined('ADMIN_EMAIL')) {
    define('ADMIN_EMAIL', 'admin@test.local');
}

if (!function_exists('mysqli_query')) {
    function mysqli_query($link, $query, $result_mode = 0) {
        return db_query($query);
    }
}

if (!function_exists('mysqli_num_rows')) {
    function mysqli_num_rows($result) {
        return db_num_rows($result);
    }
}

if (!function_exists('mysqli_real_escape_string')) {
    function mysqli_real_escape_string($link, $string) {
        return addslashes($string ?? '');
    }
}

require_once __DIR__ . '/Helpers/DatabaseMock.php';

require_once INCLUDE_DIR . 'class.format.php';
require_once INCLUDE_DIR . 'class.validator.php';
require_once INCLUDE_DIR . 'class.misc.php';
require_once INCLUDE_DIR . 'class.pagenate.php';
require_once INCLUDE_DIR . 'class.apiresponse.php';
require_once INCLUDE_DIR . 'class.apisecurity.php';
require_once INCLUDE_DIR . 'class.banlist.php';
require_once INCLUDE_DIR . 'class.lock.php';
