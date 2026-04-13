<?php
require_once('client.inc.php');
if($cfg && !$cfg->isHelpDeskOffline()) { 
    @header('Location: index.php'); //Redirect if the system is online.
    include('index.php');
    exit;
}
?>
<html>
<head>
<title>Система Технической Поддержки</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body bgcolor="#FFFFFF" text="#000000" leftmargin="0" rightmargin="0" topmargin="0">
<table width="60%" cellpadding="5" cellspacing="0" border="0">
	<tr<td>
        <p>
         <h3>Система Технической Поддержки - Отключена</h3>
         
         Спасибо вам за обращение к нам.<br>
         Система Технической Поддержки в настоящий момент отключена, пожалуйста попробуйте вернуться через некоторое время.
        </p>
    </td></tr>
</table>
</body>
</html>