<?php
require('staff.inc.php');
if(!$thisuser || !$thisuser->isStaff() || !$_GET['id'] || !$_GET['ref']) die('Доступ запрещён');
$sql='SELECT attach_id,ref_id,ticket.ticket_id,dept_id,file_name,file_key,staff_id,ticket.created FROM '.TICKET_ATTACHMENT_TABLE.
    ' LEFT JOIN '.TICKET_TABLE.' ticket USING(ticket_id) '.
    ' WHERE attach_id='.db_input($_GET['id']);

if(!($resp=db_query($sql)) || !db_num_rows($resp)) die('Неверный файл');
list($id,$refid,$tid,$deptID,$filename,$key,$staffId,$createDate)=db_fetch_row($resp);

$hash=hash('sha256', $tid.'_'.$refid.'_'.session_id());
if(!$_GET['ref'] || !hash_equals($hash,$_GET['ref'])) die('Доступ запрещён');
if($staffId!=$thisuser->getId() && !$thisuser->canAccessDept($deptID)) die("У вас нет доступа к этой заявке");

$uploadDir=rtrim($cfg->getUploadDir(),'/');
$month=date('my',strtotime($createDate));
$file=$uploadDir."/$month/$key".'_'.$filename;
if(!file_exists($file))
    $file=$uploadDir."/tickets/$key".'_'.$filename;
if(!file_exists($file))
    $file=$uploadDir."/$key".'_'.$filename;

if(!file_exists($file)) die('Файл не найден');

$realFile = realpath($file);
$realUploadDir = realpath(rtrim($cfg->getUploadDir(),'/'));
if(!$realFile || strpos($realFile, rtrim($realUploadDir, '/') . '/') !== 0) {
    die('Доступ запрещён');
}

$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
switch ($extension) {
  case 'pdf':  $ctype = 'application/pdf'; break;
  case 'exe':  $ctype = 'application/octet-stream'; break;
  case 'zip':  $ctype = 'application/zip'; break;
  case 'doc':  $ctype = 'application/msword'; break;
  case 'docx': $ctype = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'; break;
  case 'xls':  $ctype = 'application/vnd.ms-excel'; break;
  case 'xlsx': $ctype = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'; break;
  case 'ppt':  $ctype = 'application/vnd.ms-powerpoint'; break;
  case 'pptx': $ctype = 'application/vnd.openxmlformats-officedocument.presentationml.presentation'; break;
  case 'gif':  $ctype = 'image/gif'; break;
  case 'png':  $ctype = 'image/png'; break;
  case 'jpg':
  case 'jpeg': $ctype = 'image/jpeg'; break;
  case 'webp': $ctype = 'image/webp'; break;
  default:     $ctype = 'application/octet-stream'; break;
}
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: public");
header("Content-Type: $ctype");
$safe_filename = preg_replace('/[^\w\-\.\s]/', '_', basename($filename));
$user_agent = strtolower ($_SERVER["HTTP_USER_AGENT"] ?? '');
if ((is_integer(strpos($user_agent,"msie"))) && (is_integer(strpos($user_agent,"win"))))
{
  header('Content-Disposition: filename="' . $safe_filename . '"');
} else {
  header('Content-Disposition: attachment; filename="' . $safe_filename . '"');
}
header("Content-Transfer-Encoding: binary");
header("Content-Length: ".filesize($file));
readfile($file);
exit();
?>

