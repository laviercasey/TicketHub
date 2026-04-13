<?php
@chdir(realpath(dirname(__FILE__)).'/');
require('api.inc.php');
require_once(INCLUDE_DIR.'class.cron.php');
Cron::run();
$caller = (php_sapi_name() === 'cli') ? 'CLI' : (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown');
Sys::log(LOG_DEBUG,'Cron Job','Cron job executed ['.$caller.']');
?>
