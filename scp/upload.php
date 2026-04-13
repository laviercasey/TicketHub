<?php
require('staff.inc.php');
require_once(INCLUDE_DIR . 'class.fileupload.php');

header('Content-Type: application/json; charset=utf-8');

if (!$thisuser || !$thisuser->isValid()) {
    http_response_code(401);
    echo json_encode(['error' => 'Доступ запрещён']);
    exit;
}

if (!Misc::validateCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Ошибка CSRF. Обновите страницу и попробуйте снова.']);
    exit;
}

if (empty($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
    http_response_code(400);
    echo json_encode(['error' => 'Файл не выбран']);
    exit;
}

$type  = trim($_POST['type'] ?? '');
$refId = (int)($_POST['ref_id'] ?? 0);

match ($type) {
    'task'     => handleTask($refId),
    'document' => handleDocument($refId),
    default    => respond(400, 'Неверный тип загрузки'),
};


function handleTask(int $taskId): never {
    global $thisuser;
    require_once(INCLUDE_DIR . 'class.taskattachment.php');
    require_once(INCLUDE_DIR . 'class.taskactivity.php');

    if (!$taskId) respond(400, 'Не указан ID задачи');

    $errors = [];
    $attId  = TaskAttachment::upload($taskId, $_FILES['file'], $thisuser->getId(), $errors);

    if ($attId) {
        echo json_encode(['success' => true, 'attachment_id' => $attId]);
    } else {
        $errMsg = $errors['file'] ?? $errors['err'] ?? 'Ошибка загрузки файла';
        error_log("Upload error [task=$taskId]: $errMsg | file=" . ($_FILES['file']['name'] ?? 'unknown') . " size=" . ($_FILES['file']['size'] ?? 0) . " error=" . ($_FILES['file']['error'] ?? -1));
        respond(422, $errMsg);
    }
    exit;
}

function handleDocument(int $docId): never {
    respond(501, 'Загрузка документов — через форму создания документа');
}

function respond(int $code, string $message): never {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}
