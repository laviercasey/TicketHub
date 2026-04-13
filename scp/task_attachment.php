<?php
require('staff.inc.php');
require_once(INCLUDE_DIR.'class.taskattachment.php');
require_once(INCLUDE_DIR.'class.fileupload.php');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    Http::response(400, 'Не указан ID файла');
    exit;
}

$attachment = TaskAttachment::lookup($id);
if (!$attachment || !$attachment->getId()) {
    Http::response(404, 'Файл не найден');
    exit;
}

$filepath = $attachment->getFilePath();
$mime     = $attachment->getFileMime() ?: 'application/octet-stream';

FileUpload::serve($filepath, $attachment->getFileName(), $mime, isset($_GET['inline']));
?>
