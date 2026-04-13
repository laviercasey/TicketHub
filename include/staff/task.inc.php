<?php
if(!defined('OSTSCPINC') || !is_object($thisuser)) die('Доступ запрещён');

$info = $task ? $task->getInfo() : array();
$action = $task ? 'update' : 'add';
$title = $task ? 'Задача #' . $task->getId() . ': ' . Format::htmlchars($task->getTitle()) : 'Новая задача';

$boards = array();
$bsql = 'SELECT board_id, board_name FROM ' . TASK_BOARDS_TABLE . ' WHERE is_archived=0 ORDER BY board_name';
if (($bres = db_query($bsql)) && db_num_rows($bres)) {
    while ($brow = db_fetch_array($bres)) {
        $boards[] = $brow;
    }
}

$lists = array();
$cur_board_id = $task ? $task->getBoardId() : (isset($_REQUEST['board_id']) ? intval($_REQUEST['board_id']) : 0);
if ($cur_board_id) {
    $lsql = 'SELECT list_id, list_name FROM ' . TASK_LISTS_TABLE
          . ' WHERE board_id=' . db_input($cur_board_id) . ' AND is_archived=0 ORDER BY list_order';
    if (($lres = db_query($lsql)) && db_num_rows($lres)) {
        while ($lrow = db_fetch_array($lres)) {
            $lists[] = $lrow;
        }
    }
}

$staffList = array();
$ssql = 'SELECT staff_id, CONCAT(firstname," ",lastname) as name, dept_id FROM ' . TABLE_PREFIX . 'staff WHERE isactive=1 ORDER BY firstname';
if (($sres = db_query($ssql)) && db_num_rows($sres)) {
    while ($srow = db_fetch_array($sres)) {
        $staffList[] = $srow;
    }
}

$assignees = array();
if ($task) {
    $assignees = $task->getAssignees('assignee');
}

$subtasks = array();
if ($task) {
    $subtasks = $task->getSubtasks();
}

$comments = array();
$attachments = array();
$activities = array();
if ($task) {
    require_once(INCLUDE_DIR.'class.taskcomment.php');
    require_once(INCLUDE_DIR.'class.taskattachment.php');
    require_once(INCLUDE_DIR.'class.taskactivity.php');
    $comments = TaskComment::getByTaskId($task->getId());
    $attachments = TaskAttachment::getByTaskId($task->getId());
    $activities = TaskActivity::getByTaskId($task->getId(), 30);
}

require_once(INCLUDE_DIR.'class.tasktag.php');
require_once(INCLUDE_DIR.'class.taskcustomfield.php');

require_once(INCLUDE_DIR.'class.tasktimelog.php');
$timeLogs = array();
$timeTotal = 0;
if ($task) {
    $timeLogs = TaskTimeLog::getByTaskId($task->getId());
    $timeTotal = TaskTimeLog::getTotalByTask($task->getId());
}

require_once(INCLUDE_DIR.'class.taskrecurring.php');
$recurringInfo = null;
if ($task) {
    $recurringInfo = TaskRecurring::getByTaskId($task->getId());
}
$frequencyLabels = TaskRecurring::getFrequencyLabels();
$dayLabels = TaskRecurring::getDayLabels();

$taskTags = array();
$boardTags = array();
$customFields = array();
$customValues = array();
if ($cur_board_id) {
    $boardTags = TaskTag::getByBoard($cur_board_id);
    $customFields = TaskCustomField::getByBoard($cur_board_id);
}
if ($task) {
    $taskTags = TaskTag::getByTask($task->getId());
    $customValues = TaskCustomField::getValuesByTask($task->getId());
}
$taskTagIds = array();
foreach ($taskTags as $tt) {
    $taskTagIds[] = $tt['tag_id'];
}

$priorityLabels = Task::getPriorityLabels();
$statusLabels = Task::getStatusLabels();
$typeLabels = Task::getTaskTypeLabels();
?>

<?php if($msg) { ?>
<div id="infomessage" class="alert-success"><?php echo Format::htmlchars($msg); ?></div>
<?php } elseif(!empty($errors['err'])) { ?>
<div id="errormessage" class="alert-danger"><?php echo Format::htmlchars($errors['err']); ?></div>
<?php } ?>

<div class="flex items-center gap-2 mb-3">
    <a href="tasks.php" class="btn-secondary btn-sm"><i data-lucide="arrow-left" class="w-4 h-4"></i> К списку</a>
    <?php if ($task && $task->getBoardId()) { ?>
    <a href="tasks.php?board_id=<?php echo $task->getBoardId(); ?>" class="btn-secondary btn-sm"><i data-lucide="columns" class="w-4 h-4"></i> <?php echo Format::htmlchars($task->getBoardName()); ?></a>
    <?php } ?>
</div>

<h2 class="text-2xl font-heading font-bold mb-4"><?php echo $title; ?></h2>

<form action="tasks.php" method="POST" enctype="multipart/form-data">
    <?=Misc::csrfField()?>
    <input type="hidden" name="a" value="<?php echo $action; ?>">
    <?php if ($task) { ?>
    <input type="hidden" name="id" value="<?php echo $task->getId(); ?>">
    <?php } ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="card">
                <div class="card-header"><strong>Основная информация</strong></div>
                <div class="card-body space-y-4">

                    <div class="form-group">
                        <label class="label">Название <span class="text-red-500">*</span></label>
                        <input type="text" name="title" class="input"
                               value="<?php echo Format::htmlchars($info['title'] ? $info['title'] : $_POST['title']); ?>"
                               placeholder="Введите название задачи" required>
                        <?php if ($errors['title']) { ?>
                        <span class="form-error"><?php echo $errors['title']; ?></span>
                        <?php } ?>
                    </div>

                    <div class="form-group">
                        <label class="label">Описание</label>
                        <textarea name="description" class="textarea" rows="6"
                                  placeholder="Описание задачи..."><?php echo Format::htmlchars($info['description'] ? $info['description'] : $_POST['description']); ?></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="label">Доска <span class="text-red-500">*</span></label>
                            <select name="board_id" id="board_id" class="select">
                                <option value="">Выберите доску</option>
                                <?php foreach ($boards as $b) { ?>
                                <option value="<?php echo $b['board_id']; ?>"
                                    <?php echo ($cur_board_id==$b['board_id'])?'selected':''; ?>>
                                    <?php echo Format::htmlchars($b['board_name']); ?>
                                </option>
                                <?php } ?>
                            </select>
                            <?php if ($errors['board_id']) { ?>
                            <span class="form-error"><?php echo $errors['board_id']; ?></span>
                            <?php } ?>
                        </div>
                        <div class="form-group">
                            <label class="label">Список</label>
                            <select name="list_id" id="list_id" class="select">
                                <option value="">Без списка</option>
                                <?php foreach ($lists as $l) { ?>
                                <option value="<?php echo $l['list_id']; ?>"
                                    <?php echo ($info['list_id']==$l['list_id'])?'selected':''; ?>>
                                    <?php echo Format::htmlchars($l['list_name']); ?>
                                </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="form-group">
                            <label class="label">Тип задачи</label>
                            <select name="task_type" class="select">
                                <?php foreach ($typeLabels as $k=>$v) { ?>
                                <option value="<?php echo $k; ?>"
                                    <?php echo ($info['task_type']==$k || (!$info['task_type'] && $k=='action'))?'selected':''; ?>>
                                    <?php echo Format::htmlchars($v); ?>
                                </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="label">Приоритет</label>
                            <select name="priority" class="select">
                                <?php foreach ($priorityLabels as $k=>$v) { ?>
                                <option value="<?php echo $k; ?>"
                                    <?php echo ($info['priority']==$k || (!$info['priority'] && $k=='normal'))?'selected':''; ?>>
                                    <?php echo Format::htmlchars($v); ?>
                                </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="label">Статус</label>
                            <select name="status" class="select">
                                <?php foreach ($statusLabels as $k=>$v) { ?>
                                <option value="<?php echo $k; ?>"
                                    <?php echo ($info['status']==$k || (!$info['status'] && $k=='open'))?'selected':''; ?>>
                                    <?php echo Format::htmlchars($v); ?>
                                </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($task) {
                $completedSubtasks = 0;
                foreach ($subtasks as $st) {
                    if ($st['status'] == 'completed') $completedSubtasks++;
                }
                $subtaskTotal = count($subtasks);
            ?>
            <div class="card">
                <div class="card-header flex items-center gap-2">
                    <strong class="flex items-center gap-2"><i data-lucide="list" class="w-4 h-4"></i> Подзадачи
                    <?php if ($subtaskTotal > 0) { ?>
                    <span class="badge-secondary"><?php echo $completedSubtasks . '/' . $subtaskTotal; ?></span>
                    <?php } ?>
                    </strong>
                </div>
                <div class="card-body p-0">
                    <?php if ($subtaskTotal > 0) { ?>
                    <div class="px-4 pt-3">
                        <div class="w-full bg-gray-200 rounded-full h-1.5 mb-2">
                            <div class="bg-green-500 h-1.5 rounded-full" style="width:<?php echo $subtaskTotal > 0 ? round($completedSubtasks / $subtaskTotal * 100) : 0; ?>%;"></div>
                        </div>
                    </div>
                    <?php } ?>
                    <div id="subtask-list">
                    <?php if ($subtaskTotal > 0) { ?>
                    <div class="divide-y divide-gray-100">
                    <?php foreach ($subtasks as $st) {
                        $stBadge = $st['status'] == 'completed' ? 'badge-success' : ($st['status'] == 'in_progress' ? 'badge-primary' : 'badge-secondary');
                    ?>
                        <div class="subtask-row flex items-center px-4 py-2 hover:bg-gray-50" data-id="<?php echo $st['task_id']; ?>">
                            <button type="submit" form="toggle-subtask-<?php echo $st['task_id']; ?>" class="w-8 p-0 bg-transparent border-0 cursor-pointer">
                                <?php if ($st['status'] == 'completed') { ?>
                                <i data-lucide="check-square" class="w-4 h-4 text-green-500"></i>
                                <?php } else { ?>
                                <i data-lucide="square" class="w-4 h-4 text-gray-400"></i>
                                <?php } ?>
                            </button>
                            <div class="flex-1 <?php echo $st['status'] == 'completed' ? 'line-through text-gray-400' : ''; ?>">
                                <a href="tasks.php?id=<?php echo $st['task_id']; ?>" class="text-indigo-600 hover:text-indigo-800"><?php echo Format::htmlchars($st['title']); ?></a>
                            </div>
                            <div class="w-20"><span class="<?php echo $stBadge; ?>"><?php echo Format::htmlchars($statusLabels[$st['status']]); ?></span></div>
                        </div>
                    <?php } ?>
                    </div>
                    <?php } else { ?>
                    <p class="text-gray-500 no-subtasks-msg px-4 py-3 m-0">Подзадач нет</p>
                    <?php } ?>
                    </div>
                    <div class="px-4 py-3 border-t border-gray-100">
                        <div class="flex gap-2">
                            <input type="text" id="quick-subtask-title" class="input text-sm flex-1" placeholder="Новая подзадача..." form="subtask-form">
                            <button type="submit" form="subtask-form" class="btn-secondary btn-sm"><i data-lucide="plus" class="w-4 h-4"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>

            <?php if ($task) { ?>
            <div class="card">
                <div class="card-header">
                    <strong class="flex items-center gap-2"><i data-lucide="paperclip" class="w-4 h-4"></i> Вложения (<?php echo count($attachments); ?>)</strong>
                </div>
                <div class="card-body space-y-3">
                    <?php if (count($attachments) > 0) { ?>
                    <div class="divide-y divide-gray-100 mb-3">
                        <?php foreach ($attachments as $att) {
                            $attObj = new TaskAttachment($att['attachment_id']);
                        ?>
                        <div class="flex items-center py-2 gap-3">
                            <div class="w-8 text-gray-400"><i data-lucide="file" class="w-4 h-4"></i></div>
                            <div class="flex-1">
                                <a href="task_attachment.php?id=<?php echo $att['attachment_id']; ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800">
                                    <?php echo Format::htmlchars($att['file_name']); ?>
                                </a>
                                <br><small class="text-gray-500"><?php echo $attObj->getFileSizeFormatted(); ?> | <?php echo Format::htmlchars($att['uploader_name']); ?> | <?php echo date('d.m.Y H:i', strtotime($att['uploaded_date'])); ?></small>
                            </div>
                            <div class="flex items-center gap-1">
                                <?php if ($attObj->isImage()) { ?>
                                <a href="task_attachment.php?id=<?php echo $att['attachment_id']; ?>&inline=1" target="_blank" class="btn-secondary btn-sm" title="Просмотр"><i data-lucide="eye" class="w-4 h-4"></i></a>
                                <?php } ?>
                                <a href="#" class="btn-danger btn-sm delete-attachment" data-id="<?php echo $att['attachment_id']; ?>" title="Удалить"><i data-lucide="trash-2" class="w-4 h-4"></i></a>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                    <?php } else { ?>
                    <p class="text-gray-500 mb-3">Вложений нет</p>
                    <?php } ?>

                    <div class="border-t border-gray-100 pt-3">
                        <label class="btn-secondary btn-sm cursor-pointer inline-flex items-center gap-2" id="attachment-label">
                            <i data-lucide="upload" class="w-4 h-4"></i> Загрузить файл
                            <input type="file" name="task_attachment" style="display:none;" id="attachment-input">
                        </label>
                        <span id="attachment-upload-status" class="text-sm ml-2 text-gray-500"></span>
                        <small class="text-gray-500 block mt-1">PDF, DOC, XLS, JPG, PNG, ZIP и др. (макс. <?php echo $cfg ? round($cfg->getMaxFileSize()/1048576, 0) : 10; ?> МБ)</small>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <strong class="flex items-center gap-2"><i data-lucide="message-square" class="w-4 h-4"></i> Комментарии (<?php echo count($comments); ?>)</strong>
                </div>
                <div class="card-body" id="comments-container">
                    <?php if (count($comments) > 0) { ?>
                    <?php foreach ($comments as $c) { ?>
                    <div class="task-comment mb-4 pb-3 border-b border-gray-100" id="comment-<?php echo $c['comment_id']; ?>">
                        <div class="comment-header flex items-center gap-2 mb-1">
                            <strong class="text-sm"><?php echo Format::htmlchars($c['staff_name']); ?></strong>
                            <small class="text-gray-500"><?php echo date('d.m.Y H:i', strtotime($c['created'])); ?></small>
                            <?php if ($c['updated']) { ?>
                            <small class="text-gray-500">(ред.)</small>
                            <?php } ?>
                            <?php if ($c['staff_id'] == $thisuser->getId() || $thisuser->isadmin()) { ?>
                            <a href="#" class="text-red-500 ml-auto delete-comment" data-id="<?php echo $c['comment_id']; ?>" title="Удалить"><i data-lucide="trash-2" class="w-4 h-4"></i></a>
                            <?php } ?>
                        </div>
                        <div class="comment-body text-sm text-gray-700"><?php echo nl2br(Format::htmlchars($c['comment_text'])); ?></div>
                    </div>
                    <?php } ?>
                    <?php } else { ?>
                    <p class="text-gray-500 no-comments-msg">Комментариев пока нет</p>
                    <?php } ?>
                </div>
                <div class="card-footer space-y-2">
                    <div class="form-group">
                        <textarea id="comment-text" class="textarea" rows="2" placeholder="Написать комментарий..."></textarea>
                    </div>
                    <button type="button" id="add-comment-btn" class="btn-primary btn-sm"><i data-lucide="send" class="w-4 h-4"></i> Отправить</button>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <strong class="flex items-center gap-2"><i data-lucide="history" class="w-4 h-4"></i> Активность</strong>
                </div>
                <div class="card-body p-0">
                    <?php if (count($activities) > 0) { ?>
                    <div class="max-h-[300px] overflow-y-auto divide-y divide-gray-50">
                        <?php foreach ($activities as $act) {
                            $actObj = new TaskActivity($act['activity_id']);
                        ?>
                        <div class="activity-item px-4 py-2">
                            <i data-lucide="activity" class="w-4 h-4 inline-block text-gray-400"></i>
                            <strong class="text-sm"><?php echo Format::htmlchars($act['staff_name']); ?></strong>
                            <span class="text-sm"><?php echo Format::htmlchars($actObj->getTypeLabel()); ?></span>
                            <?php if ($act['activity_data']) { ?>
                            <span class="text-gray-500 text-sm">&mdash; <?php echo Format::htmlchars(mb_substr($act['activity_data'], 0, 100)); ?></span>
                            <?php } ?>
                            <br><small class="text-gray-500"><?php echo date('d.m.Y H:i', strtotime($act['created'])); ?></small>
                        </div>
                        <?php } ?>
                    </div>
                    <?php } else { ?>
                    <div class="text-center text-gray-500 py-6">Нет записей</div>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><strong>Сроки</strong></div>
                <div class="card-body space-y-4">
                    <div class="form-group">
                        <label class="label">Дата начала</label>
                        <input type="text" name="start_date" class="input datepicker" autocomplete="off"
                               value="<?php echo $info['start_date'] ? date('m/d/Y', strtotime($info['start_date'])) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="label">Дата окончания</label>
                        <input type="text" name="end_date" class="input datepicker" autocomplete="off"
                               value="<?php echo $info['end_date'] ? date('m/d/Y', strtotime($info['end_date'])) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="label">Дедлайн</label>
                        <input type="text" name="deadline" class="input datepicker" autocomplete="off"
                               value="<?php echo $info['deadline'] ? date('m/d/Y', strtotime($info['deadline'])) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="label">Оценка времени (мин)</label>
                        <input type="number" name="time_estimate" class="input" min="0"
                               value="<?php echo intval($info['time_estimate']); ?>"
                               placeholder="0">
                    </div>
                </div>
            </div>

            <?php if ($task) { ?>
            <div class="card">
                <div class="card-header">
                    <strong class="flex items-center gap-2"><i data-lucide="clock" class="w-4 h-4"></i> Учёт времени</strong>
                </div>
                <div class="card-body space-y-3">
                    <div class="time-summary mb-3">
                        <div class="grid grid-cols-2 gap-4 text-center">
                            <div>
                                <div class="text-gray-500 text-xs">Затрачено</div>
                                <strong id="time-total-display" class="text-base"><?php echo TaskTimeLog::formatMinutes($timeTotal); ?></strong>
                            </div>
                            <div>
                                <div class="text-gray-500 text-xs">Оценка</div>
                                <strong class="text-base"><?php echo $info['time_estimate'] ? TaskTimeLog::formatMinutes($info['time_estimate']) : '—'; ?></strong>
                            </div>
                        </div>
                        <?php if ($info['time_estimate'] > 0) {
                            $timePct = min(100, round($timeTotal / $info['time_estimate'] * 100));
                            $timeBarColor = $timePct > 100 ? 'bg-red-500' : ($timePct > 80 ? 'bg-yellow-500' : 'bg-green-500');
                        ?>
                        <div class="w-full bg-gray-200 rounded-full h-1.5 mt-2">
                            <div class="<?php echo $timeBarColor; ?> h-1.5 rounded-full" style="width:<?php echo $timePct; ?>%;"></div>
                        </div>
                        <?php } ?>
                    </div>

                    <div class="border-t border-gray-100 pt-3 space-y-2">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-xs text-gray-500 mb-0.5 block">Часы</label>
                                <input form="add-timelog-form" type="number" name="tl_hours" class="input text-sm" placeholder="0" min="0" value="0">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 mb-0.5 block">Минуты</label>
                                <input form="add-timelog-form" type="number" name="tl_minutes" class="input text-sm" placeholder="0" min="0" max="59" value="0">
                            </div>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 mb-0.5 block">Дата</label>
                            <input form="add-timelog-form" type="text" name="tl_date" class="input text-sm datepicker w-full" value="<?php echo date('m/d/Y'); ?>" autocomplete="off">
                        </div>
                        <input form="add-timelog-form" type="text" name="tl_notes" class="input text-sm" placeholder="Описание (необязательно)">
                        <button type="submit" form="add-timelog-form" class="btn-secondary btn-sm w-full"><i data-lucide="plus" class="w-4 h-4"></i> Записать время</button>
                    </div>

                    <?php if (count($timeLogs) > 0) { ?>
                    <div class="border-t border-gray-100 mt-3 pt-2">
                        <div class="time-logs-list max-h-[200px] overflow-y-auto">
                        <?php foreach ($timeLogs as $tl) { ?>
                            <div class="time-log-entry py-1 border-b border-gray-50 text-xs">
                                <div class="flex items-center justify-between">
                                    <span>
                                        <strong><?php echo TaskTimeLog::formatMinutes($tl['time_spent']); ?></strong>
                                        <span class="text-gray-500">— <?php echo Format::htmlchars($tl['staff_name']); ?></span>
                                    </span>
                                    <button type="submit" form="delete-timelog-<?php echo $tl['log_id']; ?>" class="text-red-500" title="Удалить" onclick="return confirm('Удалить запись времени?');"><i data-lucide="x" class="w-3 h-3"></i></button>
                                </div>
                                <?php if ($tl['notes']) { ?>
                                <div class="text-gray-500"><?php echo Format::htmlchars($tl['notes']); ?></div>
                                <?php } ?>
                                <div class="text-gray-500"><?php echo date('d.m.Y', strtotime($tl['log_date'])); ?></div>
                            </div>
                        <?php } ?>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>

            <div class="card">
                <div class="card-header"><strong>Исполнители</strong></div>
                <div class="card-body space-y-2" id="assignees-container">
                    <?php
                    $aIdx = 0;
                    if (count($assignees) > 0) {
                        foreach ($assignees as $a) { ?>
                    <div class="assignee-row flex items-center gap-2">
                        <select name="assignee_<?php echo $aIdx; ?>" class="select text-sm flex-1">
                            <option value="">Не назначен</option>
                            <?php foreach ($staffList as $st) { ?>
                            <option value="<?php echo $st['staff_id']; ?>" <?php echo ($a['staff_id']==$st['staff_id'])?'selected':''; ?>><?php echo Format::htmlchars($st['name']); ?></option>
                            <?php } ?>
                        </select>
                        <a href="#" class="btn-danger btn-sm remove-assignee"><i data-lucide="x" class="w-4 h-4"></i></a>
                    </div>
                    <?php $aIdx++;
                        }
                    } else { ?>
                    <div class="assignee-row flex items-center gap-2">
                        <select name="assignee_0" class="select text-sm flex-1">
                            <option value="">Не назначен</option>
                            <?php foreach ($staffList as $st) { ?>
                            <option value="<?php echo $st['staff_id']; ?>"><?php echo Format::htmlchars($st['name']); ?></option>
                            <?php } ?>
                        </select>
                        <a href="#" class="btn-danger btn-sm remove-assignee"><i data-lucide="x" class="w-4 h-4"></i></a>
                    </div>
                    <?php } ?>

                    <a href="#" id="add-assignee" class="btn-secondary btn-sm"><i data-lucide="plus" class="w-4 h-4"></i> Добавить</a>
                </div>
            </div>

            <?php if (count($boardTags) > 0) { ?>
            <div class="card">
                <div class="card-header"><strong class="flex items-center gap-2"><i data-lucide="tags" class="w-4 h-4"></i> Теги</strong></div>
                <div class="card-body" id="tags-container">
                    <div class="flex flex-wrap gap-2">
                    <?php foreach ($boardTags as $bt) {
                        $isActive = in_array($bt['tag_id'], $taskTagIds);
                    ?>
                    <label class="task-tag-toggle cursor-pointer inline-block rounded-full px-3 py-1 text-xs font-medium border-2 transition-colors <?php echo $isActive ? 'text-white' : 'text-gray-600 bg-white'; ?>" style="border-color:<?php echo Format::htmlchars($bt['tag_color']); ?>;<?php echo $isActive ? 'background:'.Format::htmlchars($bt['tag_color']).';color:#fff;' : ''; ?>">
                        <input type="checkbox" name="tags[]" value="<?php echo $bt['tag_id']; ?>"
                               <?php echo $isActive ? 'checked' : ''; ?> style="display:none;">
                        <?php echo Format::htmlchars($bt['tag_name']); ?>
                    </label>
                    <?php } ?>
                    </div>
                </div>
            </div>
            <?php } ?>

            <?php if (count($customFields) > 0) { ?>
            <div class="card">
                <div class="card-header"><strong class="flex items-center gap-2"><i data-lucide="list" class="w-4 h-4"></i> Доп. поля</strong></div>
                <div class="card-body space-y-4">
                    <?php foreach ($customFields as $cf) {
                        $cfVal = isset($customValues[$cf['field_id']]) ? $customValues[$cf['field_id']]['field_value'] : '';
                        $cfOpts = $cf['field_options'] ? json_decode($cf['field_options'], true) : array();
                        if (!is_array($cfOpts)) {
                            $cfOpts = @unserialize($cf['field_options'], ['allowed_classes' => false]);
                            if (!is_array($cfOpts)) $cfOpts = array();
                        }
                    ?>
                    <div class="form-group">
                        <label class="label"><?php echo Format::htmlchars($cf['field_name']); ?><?php echo $cf['is_required'] ? ' <span class="text-red-500">*</span>' : ''; ?></label>
                        <?php if ($cf['field_type'] == 'text') { ?>
                        <input type="text" name="cf_<?php echo $cf['field_id']; ?>" class="input text-sm" value="<?php echo Format::htmlchars($cfVal); ?>">
                        <?php } elseif ($cf['field_type'] == 'number') { ?>
                        <input type="number" name="cf_<?php echo $cf['field_id']; ?>" class="input text-sm" value="<?php echo Format::htmlchars($cfVal); ?>">
                        <?php } elseif ($cf['field_type'] == 'date') { ?>
                        <input type="date" name="cf_<?php echo $cf['field_id']; ?>" class="input text-sm" value="<?php echo Format::htmlchars($cfVal); ?>">
                        <?php } elseif ($cf['field_type'] == 'textarea') { ?>
                        <textarea name="cf_<?php echo $cf['field_id']; ?>" class="textarea text-sm" rows="2"><?php echo Format::htmlchars($cfVal); ?></textarea>
                        <?php } elseif ($cf['field_type'] == 'checkbox') { ?>
                        <div><input type="checkbox" name="cf_<?php echo $cf['field_id']; ?>" value="1" <?php echo $cfVal ? 'checked' : ''; ?> class="checkbox"></div>
                        <?php } elseif ($cf['field_type'] == 'dropdown') { ?>
                        <select name="cf_<?php echo $cf['field_id']; ?>" class="select text-sm">
                            <option value="">--</option>
                            <?php foreach ($cfOpts as $opt) { ?>
                            <option value="<?php echo Format::htmlchars($opt); ?>" <?php echo ($cfVal == $opt) ? 'selected' : ''; ?>><?php echo Format::htmlchars($opt); ?></option>
                            <?php } ?>
                        </select>
                        <?php } elseif ($cf['field_type'] == 'user') { ?>
                        <select name="cf_<?php echo $cf['field_id']; ?>" class="select text-sm">
                            <option value="">Сотрудник</option>
                            <?php foreach ($staffList as $st) { ?>
                            <option value="<?php echo $st['staff_id']; ?>" <?php echo ($cfVal == $st['staff_id']) ? 'selected' : ''; ?>><?php echo Format::htmlchars($st['name']); ?></option>
                            <?php } ?>
                        </select>
                        <?php } ?>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>

            <?php if ($task && $task->getTicketId()) {
                $linkedTicketInfo = null;
                $ltSql = 'SELECT ticketID, subject, status FROM ' . TABLE_PREFIX . 'ticket WHERE ticket_id=' . db_input($task->getTicketId());
                $ltRes = db_query($ltSql);
                if ($ltRes && db_num_rows($ltRes)) {
                    $linkedTicketInfo = db_fetch_array($ltRes);
                }
            ?>
            <div class="card">
                <div class="card-header"><strong class="flex items-center gap-2"><i data-lucide="ticket" class="w-4 h-4"></i> Связанный тикет</strong></div>
                <div class="card-body">
                    <a href="tickets.php?id=<?php echo $task->getTicketId(); ?>" class="text-indigo-600 hover:text-indigo-800">
                        <i data-lucide="ticket" class="w-4 h-4 inline-block"></i> #<?php echo $linkedTicketInfo ? Format::htmlchars($linkedTicketInfo['ticketID']) : $task->getTicketId(); ?>
                    </a>
                    <?php if ($linkedTicketInfo) { ?>
                    <br><small class="text-gray-700"><?php echo Format::htmlchars($linkedTicketInfo['subject']); ?></small>
                    <br><small class="text-gray-500">Статус: <?php echo Format::htmlchars($linkedTicketInfo['status']); ?></small>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>

            <?php if ($task) { ?>
            <div class="card">
                <div class="card-header"><strong>Информация</strong></div>
                <div class="card-body text-sm text-gray-600 space-y-1">
                    <div><strong>Создал:</strong> <?php echo Format::htmlchars($task->getCreatorName()); ?></div>
                    <div><strong>Создана:</strong> <?php echo Format::htmlchars($task->getCreated()); ?></div>
                    <?php if ($task->getUpdated()) { ?>
                    <div><strong>Обновлена:</strong> <?php echo Format::htmlchars($task->getUpdated()); ?></div>
                    <?php } ?>
                    <?php if ($task->getCompletedDate()) { ?>
                    <div><strong>Завершена:</strong> <?php echo Format::htmlchars($task->getCompletedDate()); ?></div>
                    <?php } ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><strong class="flex items-center gap-2"><i data-lucide="refresh-cw" class="w-4 h-4"></i> Повторение</strong></div>
                <div class="card-body" id="recurring-panel">
                    <?php if ($recurringInfo) { ?>
                    <div id="recurring-info" class="space-y-2 text-sm">
                        <div>
                            <strong>Частота:</strong> <?php echo Format::htmlchars($frequencyLabels[$recurringInfo['frequency']]); ?>
                            <?php if ($recurringInfo['interval_value'] > 1) { ?>
                            (каждые <?php echo intval($recurringInfo['interval_value']); ?>)
                            <?php } ?>
                        </div>
                        <?php if ($recurringInfo['day_of_week'] && $recurringInfo['frequency'] == 'weekly') {
                            $recDays = explode(',', $recurringInfo['day_of_week']);
                            $recDayNames = array();
                            foreach ($recDays as $rd) {
                                $rd = intval($rd);
                                if (isset($dayLabels[$rd])) $recDayNames[] = $dayLabels[$rd];
                            }
                        ?>
                        <div>
                            <strong>Дни:</strong> <?php echo Format::htmlchars(implode(', ', $recDayNames)); ?>
                        </div>
                        <?php } ?>
                        <div>
                            <strong>Следующее:</strong> <?php echo date('d.m.Y H:i', strtotime($recurringInfo['next_occurrence'])); ?>
                        </div>
                        <div>
                            <strong>Статус:</strong>
                            <?php if ($recurringInfo['is_active']) { ?>
                            <span class="badge-success">Активно</span>
                            <?php } else { ?>
                            <span class="badge-secondary">Отключено</span>
                            <?php } ?>
                        </div>
                        <div class="flex gap-2 pt-2">
                            <?php if ($recurringInfo['is_active']) { ?>
                            <button type="submit" form="toggle-recurring-form" class="btn-warning btn-sm">
                                <i data-lucide="pause" class="w-4 h-4"></i> Отключить
                            </button>
                            <?php } else { ?>
                            <button type="submit" form="enable-recurring-form" class="btn-success btn-sm">
                                <i data-lucide="play" class="w-4 h-4"></i> Включить
                            </button>
                            <?php } ?>
                            <button type="submit" form="remove-recurring-form" class="btn-danger btn-sm" onclick="return confirm('Удалить настройку повторения?');">
                                <i data-lucide="trash-2" class="w-4 h-4"></i> Удалить
                            </button>
                        </div>
                    </div>
                    <?php } else { ?>
                    <div id="recurring-setup">
                        <div id="recurring-form-toggle">
                            <button type="button" class="btn-secondary btn-sm w-full" id="show-recurring-form">
                                <i data-lucide="plus" class="w-4 h-4"></i> Настроить повторение
                            </button>
                        </div>
                        <div id="recurring-form" style="display:none;" class="space-y-3">
                            <div class="form-group">
                                <label class="label">Частота</label>
                                <select form="save-recurring-form" name="rec_frequency" id="rec-frequency" class="select text-sm">
                                    <?php foreach ($frequencyLabels as $fk => $fv) { ?>
                                    <option value="<?php echo $fk; ?>"><?php echo Format::htmlchars($fv); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="label">Интервал</label>
                                <input form="save-recurring-form" type="number" name="rec_interval" id="rec-interval" class="input text-sm" value="1" min="1">
                            </div>
                            <div class="form-group" id="rec-days-group" style="display:none;">
                                <label class="label">Дни недели</label>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($dayLabels as $dk => $dv) { ?>
                                    <label class="inline-flex items-center gap-1 text-sm cursor-pointer">
                                        <input form="save-recurring-form" type="checkbox" name="rec_days[]" class="checkbox rec-day-checkbox" value="<?php echo $dk; ?>"> <?php echo Format::htmlchars($dv); ?>
                                    </label>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" form="save-recurring-form" class="btn-primary btn-sm">
                                    <i data-lucide="save" class="w-4 h-4"></i> Сохранить
                                </button>
                                <button type="button" class="btn-secondary btn-sm" id="cancel-recurring-btn">Отмена</button>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>

    <div class="flex items-center gap-3 mt-6">
        <button type="submit" class="btn-primary"><i data-lucide="save" class="w-4 h-4"></i> <?php echo $task ? 'Сохранить' : 'Создать задачу'; ?></button>
        <a href="tasks.php" class="btn-secondary">Отмена</a>
        <?php if ($task) { ?>
        <a href="tasks.php?a=add&board_id=<?php echo $task->getBoardId(); ?>&parent_task_id=<?php echo $task->getId(); ?>"
           class="btn-secondary"><i data-lucide="plus" class="w-4 h-4"></i> Добавить подзадачу</a>
        <button type="button" class="btn-secondary" id="save-as-template-btn"><i data-lucide="copy" class="w-4 h-4"></i> Сохранить как шаблон</button>
        <?php } ?>
    </div>

    <?php if (isset($_REQUEST['parent_task_id']) && intval($_REQUEST['parent_task_id'])) { ?>
    <input type="hidden" name="parent_task_id" value="<?php echo intval($_REQUEST['parent_task_id']); ?>">
    <?php } ?>
    <?php if (isset($_REQUEST['ticket_id']) && intval($_REQUEST['ticket_id'])) { ?>
    <input type="hidden" name="ticket_id" value="<?php echo intval($_REQUEST['ticket_id']); ?>">
    <?php } ?>
</form>

<?php if ($task) { ?>
<form id="subtask-form" action="tasks.php" method="POST" style="display:none;">
    <?=Misc::csrfField()?>
    <input type="hidden" name="a" value="quick_subtask">
    <input type="hidden" name="task_id" value="<?php echo $task->getId(); ?>">
    <input type="hidden" name="subtask_title" id="subtask-title-hidden">
</form>
<?php } ?>

<?php if ($task && count($subtasks) > 0) {
    foreach ($subtasks as $st) { ?>
<form id="toggle-subtask-<?php echo $st['task_id']; ?>" action="tasks.php" method="POST" style="display:none;">
    <?=Misc::csrfField()?>
    <input type="hidden" name="a" value="toggle_subtask">
    <input type="hidden" name="subtask_id" value="<?php echo $st['task_id']; ?>">
    <input type="hidden" name="task_id" value="<?php echo $task->getId(); ?>">
</form>
<?php } } ?>

<?php if ($task) { ?>
<form id="add-timelog-form" action="tasks.php" method="POST" style="display:none;">
    <?php echo Misc::csrfField(); ?>
    <input type="hidden" name="a" value="add_timelog">
    <input type="hidden" name="task_id" value="<?php echo $task->getId(); ?>">
</form>
<?php if (isset($timeLogs) && count($timeLogs) > 0) {
    foreach ($timeLogs as $tl) { ?>
<form id="delete-timelog-<?php echo $tl['log_id']; ?>" action="tasks.php" method="POST" style="display:none;">
    <?php echo Misc::csrfField(); ?>
    <input type="hidden" name="a" value="delete_timelog">
    <input type="hidden" name="timelog_id" value="<?php echo $tl['log_id']; ?>">
    <input type="hidden" name="task_id" value="<?php echo $task->getId(); ?>">
</form>
<?php } } ?>

<form id="save-recurring-form" action="tasks.php" method="POST" style="display:none;">
    <?php echo Misc::csrfField(); ?>
    <input type="hidden" name="a" value="save_recurring">
    <input type="hidden" name="task_id" value="<?php echo $task->getId(); ?>">
</form>
<?php if (isset($recurringInfo) && $recurringInfo) { ?>
<form id="toggle-recurring-form" action="tasks.php" method="POST" style="display:none;">
    <?php echo Misc::csrfField(); ?>
    <input type="hidden" name="a" value="toggle_recurring">
    <input type="hidden" name="task_id" value="<?php echo $task->getId(); ?>">
    <input type="hidden" name="is_active" value="0">
</form>
<form id="enable-recurring-form" action="tasks.php" method="POST" style="display:none;">
    <?php echo Misc::csrfField(); ?>
    <input type="hidden" name="a" value="toggle_recurring">
    <input type="hidden" name="task_id" value="<?php echo $task->getId(); ?>">
    <input type="hidden" name="is_active" value="1">
</form>
<form id="remove-recurring-form" action="tasks.php" method="POST" style="display:none;">
    <?php echo Misc::csrfField(); ?>
    <input type="hidden" name="a" value="remove_recurring">
    <input type="hidden" name="task_id" value="<?php echo $task->getId(); ?>">
</form>
<?php } ?>
<?php } ?>

<?php if ($task) { ?>
<div id="template-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold mb-4">Сохранить как шаблон</h3>
        <div class="form-group mb-4">
            <label class="label">Название шаблона</label>
            <input type="text" id="template-name-input" class="input" placeholder="Введите название...">
        </div>
        <div class="flex justify-end gap-2">
            <button type="button" id="template-modal-cancel" class="btn-secondary">Отмена</button>
            <button type="button" id="template-modal-save" class="btn-primary">Сохранить</button>
        </div>
    </div>
</div>
<?php } ?>

<script type="text/javascript">
var assigneeIdx = <?php echo max($aIdx, 1); ?>;
var staffOptions = '<?php
    $opts = '<option value="">Не назначен</option>';
    foreach ($staffList as $st) {
        $opts .= '<option value="' . $st['staff_id'] . '">' . Format::htmlchars($st['name']) . '</option>';
    }
    echo addslashes($opts);
?>';

$(document).ready(function(){
    $('#add-assignee').click(function(e){
        e.preventDefault();
        var html = '<div class="assignee-row flex items-center gap-2">'
                 + '<select name="assignee_' + assigneeIdx + '" class="select text-sm flex-1">'
                 + staffOptions + '</select>'
                 + ' <a href="#" class="btn-danger btn-sm remove-assignee"><i data-lucide="x" class="w-4 h-4"></i></a>'
                 + '</div>';
        $(this).before(html);
        if (typeof lucide !== 'undefined') lucide.createIcons();
        assigneeIdx++;
    });

    $(document).on('click', '.remove-assignee', function(e){
        e.preventDefault();
        $(this).closest('.assignee-row').remove();
    });

    $('.task-tag-toggle').click(function(){
        var $label = $(this);
        var $cb = $label.find('input[type="checkbox"]');
        var styleAttr = $label.attr('style') || '';
        var borderMatch = styleAttr.match(/border-color:\s*([^;]+)/);
        var tagColor = borderMatch ? borderMatch[1] : '#3498db';

        if ($cb.is(':checked')) {
            $label.addClass('active').css({'background': tagColor, 'color': '#fff'});
        } else {
            $label.removeClass('active').css({'background': '#fff', 'color': '#555'});
        }
    });

    $('#board_id').change(function(){
        var boardId = $(this).val();
        var $listSelect = $('#list_id');

        if (!boardId) {
            $listSelect.html('<option value="">Без списка</option>');
            return;
        }

        $listSelect.html('<option value="">Сохраните для обновления списков</option>');
    });

    $(document).on('click', '.delete-attachment', function(e){
        e.preventDefault();
        if (!confirm('Удалить вложение?')) return;

        var id = $(this).data('id');
        var $row = $(this).closest('.flex.items-center.py-2');

        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'tasks.php';
        form.style.display = 'none';

        var csrf = document.querySelector('input[name="csrf_token"]');
        if (csrf) {
            var csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = csrf.value;
            form.appendChild(csrfInput);
        }

        var aInput = document.createElement('input');
        aInput.type = 'hidden';
        aInput.name = 'a';
        aInput.value = 'delete_attachment';
        form.appendChild(aInput);

        var attInput = document.createElement('input');
        attInput.type = 'hidden';
        attInput.name = 'attachment_id';
        attInput.value = id;
        form.appendChild(attInput);

        var taskInput = document.createElement('input');
        taskInput.type = 'hidden';
        taskInput.name = 'task_id';
        taskInput.value = '<?php echo $task ? $task->getId() : 0; ?>';
        form.appendChild(taskInput);

        document.body.appendChild(form);
        form.submit();
    });

    document.getElementById('attachment-input').addEventListener('change', function() {
        if (!this.files || this.files.length === 0) return;

        const statusEl = document.getElementById('attachment-upload-status');
        const labelEl = document.getElementById('attachment-label');
        const csrfToken = document.querySelector('input[name="csrf_token"]').value;

        statusEl.textContent = 'Загрузка...';
        statusEl.className = 'text-sm ml-2 text-gray-500';
        labelEl.style.pointerEvents = 'none';
        labelEl.style.opacity = '0.6';

        const formData = new FormData();
        formData.append('type', 'task');
        formData.append('ref_id', '<?php echo $task ? $task->getId() : 0; ?>');
        formData.append('csrf_token', csrfToken);
        formData.append('file', this.files[0]);

        fetch('upload.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(function(response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                statusEl.textContent = 'Загружено!';
                statusEl.className = 'text-sm ml-2 text-green-600';
                setTimeout(function() { location.reload(); }, 500);
            } else {
                statusEl.textContent = 'Ошибка: ' + (data.error || 'Неизвестная ошибка');
                statusEl.className = 'text-sm ml-2 text-red-500';
                labelEl.style.pointerEvents = '';
                labelEl.style.opacity = '';
                document.getElementById('attachment-input').value = '';
            }
        })
        .catch(function(err) {
            statusEl.textContent = 'Ошибка загрузки';
            statusEl.className = 'text-sm ml-2 text-red-500';
            labelEl.style.pointerEvents = '';
            labelEl.style.opacity = '';
            document.getElementById('attachment-input').value = '';
        });
    });

    $('#subtask-form').on('submit', function(){
        var title = $.trim($('#quick-subtask-title').val());
        if (!title) {
            return false;
        }
        $('#subtask-title-hidden').val(title);
        return true;
    });

    $('#quick-subtask-title').keypress(function(e){
        if (e.which == 13) {
            e.preventDefault();
            var title = $.trim($(this).val());
            if (title) {
                $('#subtask-title-hidden').val(title);
                $('#subtask-form').submit();
            }
        }
    });

    $('#show-recurring-form').click(function(){
        $('#recurring-form-toggle').hide();
        $('#recurring-form').show();
    });

    $('#cancel-recurring-btn').click(function(){
        $('#recurring-form').hide();
        $('#recurring-form-toggle').show();
    });

    $('#rec-frequency').change(function(){
        if ($(this).val() == 'weekly') {
            $('#rec-days-group').show();
        } else {
            $('#rec-days-group').hide();
        }
    });

    $('#save-as-template-btn').click(function(){
        $('#template-modal').removeClass('hidden');
        $('#template-name-input').val('<?php echo $task ? addslashes(Format::htmlchars($task->getTitle())) : ''; ?>').focus();
    });
    $('#template-modal-cancel').click(function(){
        $('#template-modal').addClass('hidden');
    });
    $('#template-modal-save').click(function(){
        var name = $.trim($('#template-name-input').val());
        if (!name) { $('#template-name-input').focus(); return; }
        $('#template-modal').addClass('hidden');
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'tasks.php';
        form.style.display = 'none';
        var csrf = document.querySelector('input[name="csrf_token"]');
        if (csrf) {
            var inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'csrf_token'; inp.value = csrf.value;
            form.appendChild(inp);
        }
        var fields = {a:'save_template', task_id:'<?php echo $task ? $task->getId() : 0; ?>', template_name: name};
        for (var k in fields) {
            var inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = k; inp.value = fields[k];
            form.appendChild(inp);
        }
        document.body.appendChild(form);
        form.submit();
    });

    $('#add-comment-btn').click(function(){
        var text = $.trim($('#comment-text').val());
        if (!text) { $('#comment-text').focus(); return; }
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'tasks.php';
        form.style.display = 'none';
        var csrf = document.querySelector('input[name="csrf_token"]');
        if (csrf) {
            var inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'csrf_token'; inp.value = csrf.value;
            form.appendChild(inp);
        }
        var fields = {a:'add_comment', task_id:'<?php echo $task ? $task->getId() : 0; ?>', comment_text: text};
        for (var k in fields) {
            var inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = k; inp.value = fields[k];
            form.appendChild(inp);
        }
        document.body.appendChild(form);
        form.submit();
    });

    $(document).on('click', '.delete-comment', function(e){
        e.preventDefault();
        if (!confirm('Удалить комментарий?')) return;
        var id = $(this).data('id');
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'tasks.php';
        form.style.display = 'none';
        var csrf = document.querySelector('input[name="csrf_token"]');
        if (csrf) {
            var inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'csrf_token'; inp.value = csrf.value;
            form.appendChild(inp);
        }
        var fields = {a:'delete_comment', comment_id: id, task_id:'<?php echo $task ? $task->getId() : 0; ?>'};
        for (var k in fields) {
            var inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = k; inp.value = fields[k];
            form.appendChild(inp);
        }
        document.body.appendChild(form);
        form.submit();
    });
});
</script>
