<?php
require('client.inc.php');
require_once(INCLUDE_DIR.'class.document.php');

if(!defined('KB_DOCUMENTS_TABLE'))
    define('KB_DOCUMENTS_TABLE', TABLE_PREFIX.'kb_documents');

if(!$_GET['id'] || !is_numeric($_GET['id'])) die('Доступ запрещён');

$doc = Document::lookup(intval($_GET['id']));

if(!$doc || !$doc->getId() || !$doc->isEnabled() || !in_array($doc->getAudience(), array('client','all'))) {
    die('Доступ запрещён');
}

if(!$doc->isFile()) die('Не является файловым документом');

$file_path = $doc->getFilePath();
if(!file_exists($file_path)) die('Файл не найден');

$real_path = realpath($file_path);
$upload_dir = realpath(rtrim($cfg->getUploadDir(), '/\\') . '/docs');
if (!$real_path || !$upload_dir || strpos($real_path, $upload_dir) !== 0) {
    die('Доступ запрещён');
}

$filename = $doc->getFileName();
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

$ctypes = array(
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'ppt' => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'txt' => 'text/plain',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif'
);

$ctype = isset($ctypes[$ext]) ? $ctypes[$ext] : 'application/octet-stream';

header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: public");
header("Content-Type: $ctype");

$safeFilename = str_replace(["\r", "\n", '"'], '', basename($filename));
if(isset($_GET['inline']) && in_array($ext, array('pdf','jpg','jpeg','png','gif','txt'))) {
    header('Content-Disposition: inline; filename="' . $safeFilename . '"');
} else {
    header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
}

header("Content-Transfer-Encoding: binary");
header("Content-Length: " . filesize($file_path));
readfile($file_path);
exit();
?>
