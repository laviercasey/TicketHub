<?php
require('client.inc.php');
session_regenerate_id(true);
$_SESSION['_client']=array();
session_unset();
session_destroy();
header('Location: index.php');
exit;
?>
