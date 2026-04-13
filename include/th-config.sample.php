<?php
if(!strcasecmp(basename($_SERVER['SCRIPT_NAME']),basename(__FILE__)) || !defined('ROOT_PATH')) die('kwaheri rafiki!');

define('THINSTALLED',FALSE);
if(THINSTALLED!=TRUE){
    if(!file_exists(ROOT_PATH.'setup/install.php')) die('Error: Contact system admin.');
    header('Location: '.ROOT_PATH.'setup/install.php');
    exit;
}

define('SECRET_SALT', getenv('SECRET_SALT') ?: '');
define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: '');

define('DBTYPE','mysql');
define('DBHOST', getenv('DB_HOST') ?: 'localhost');
define('DBNAME', getenv('DB_NAME') ?: 'tickethub');
define('DBUSER', getenv('DB_USER') ?: '');
define('DBPASS', getenv('DB_PASS') ?: '');

define('TABLE_PREFIX','th_');

?>
