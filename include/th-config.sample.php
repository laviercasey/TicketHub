<?php
if(!strcasecmp(basename($_SERVER['SCRIPT_NAME']),basename(__FILE__)) || !defined('ROOT_PATH')) die('kwaheri rafiki!');

define('SECRET_SALT', getenv('SECRET_SALT') ?: '');
define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: '');

define('DBTYPE','mysql');
define('DBHOST', getenv('DB_HOST') ?: 'localhost');
define('DBNAME', getenv('DB_NAME') ?: 'tickethub');
define('DBUSER', getenv('DB_USER') ?: '');
define('DBPASS', getenv('DB_PASS') ?: '');

define('TABLE_PREFIX','th_');
define('SCHEMA_VERSION', '1.0');
define('PRODUCT_VERSION', '0.1.0');

?>
