<?php
require_once('main.inc.php');
require(INCLUDE_DIR.'class.captcha.php');

$captcha = new Captcha(5, 28);
$captcha->getImage();
?>
