<?php
require('staff.inc.php');
Sys::log(LOG_DEBUG,'Staff logout',sprintf("%s logged out [%s]",$thisuser->getUserName(),$_SERVER['REMOTE_ADDR'])); //Debug.
$_SESSION['_staff']=array();
session_unset();
session_destroy();
@header('Location: login.php');
require('login.php');
?>
