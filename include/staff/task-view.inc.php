<?php
if(!defined('OSTSCPINC') || !is_object($thisuser)) die('Доступ запрещён');

if (!$task) {
    die('Задача не найдена');
}

$info = $task->getInfo();
$title = 'Задача #' . $task->getId() . ': ' . Format::htmlchars($task->getTitle());

require_once(INCLUDE_DIR.'class.tasktag.php');
require_once(INCLUDE_DIR.'class.taskcustomfield.php');
require_once(INCLUDE_DIR.'class.tasktimelog.php');
require_once(INCLUDE_DIR.'class.taskcomment.php');
require_once(INCLUDE_DIR.'class.taskattachment.php');
require_once(INCLUDE_DIR.'class.taskactivity.php');
require_once(INCLUDE_DIR.'class.taskrecurring.php');

$taskTags = TaskTag::getByTask($task->getId());
$customValues = TaskCustomField::getValuesByTask($task->getId());
$timeLogs = TaskTimeLog::getByTaskId($task->getId());
$timeTotal = TaskTimeLog::getTotalByTask($task->getId());
$subtasks = $task->getSubtasks();
$comments = TaskComment::getByTaskId($task->getId());
$attachments = TaskAttachment::getByTaskId($task->getId());
$activities = TaskActivity::getByTaskId($task->getId(), 30);
$recurringInfo = TaskRecurring::getByTaskId($task->getId());

$assignees = $task->getAssignees('assignee');
$watchers = $task->getAssignees('watcher');

$staffList = array();
$ssql = 'SELECT staff_id, CONCAT(firstname," ",lastname) as name FROM ' . TABLE_PREFIX . 'staff WHERE isactive=1 ORDER BY firstname';
if (($sres = db_query($ssql)) && db_num_rows($sres)) {
    while ($srow = db_fetch_array($sres)) {
        $staffList[] = $srow;
    }
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

<div class="flex items-center justify-between mb-3">
    <div class="flex items-center gap-2">
        <a href="tasks.php" class="btn-secondary btn-sm"><i data-lucide="arrow-left" class="w-4 h-4"></i> К списку</a>
        <?php if ($task->getBoardId()) { ?>
        <a href="tasks.php?board_id=<?php echo $task->getBoardId(); ?>" class="btn-secondary btn-sm"><i data-lucide="columns" class="w-4 h-4"></i> <?php echo Format::htmlchars($task->getBoardName()); ?></a>
        <?php } ?>
    </div>
    <a href="tasks.php?id=<?php echo $task->getId(); ?>" class="btn-primary btn-sm"><i data-lucide="edit" class="w-4 h-4"></i> Полное редактирование</a>
</div>

<?php
$priorityColors = array('low' => '#6b7280', 'normal' => '#6366f1', 'high' => '#f59e0b', 'urgent' => '#ef4444');
$priorityIcons = array('low' => 'arrow-down', 'normal' => 'minus', 'high' => 'arrow-up', 'urgent' => 'alert-triangle');
$statusColors = array('open' => '#6b7280', 'in_progress' => '#6366f1', 'blocked' => '#ef4444', 'completed' => '#22c55e', 'cancelled' => '#9ca3af');
$statusIcons = array('open' => 'circle', 'in_progress' => 'loader', 'blocked' => 'ban', 'completed' => 'check-circle', 'cancelled' => 'x-circle');
?>

<!-- Task Header -->
<div class="card mb-6 overflow-hidden">
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4 text-white">
        <h3 class="text-xl font-heading font-bold m-0 flex items-center gap-2">
            <i data-lucide="check-square" class="w-5 h-5"></i> <?php echo Format::htmlchars($info['title']); ?>
            <span class="badge-secondary text-xs ml-2">#<?php echo $task->getId(); ?></span>
        </h3>
    </div>
    <div class="p-0">
        <!-- Quick Status Bar -->
        <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <strong class="text-gray-500 text-[11px] uppercase tracking-wide">Статус</strong>
                    <div class="mt-1">
                        <span class="inline-flex items-center gap-1 text-white text-xs font-medium px-2.5 py-1 rounded-full" style="background:<?php echo $statusColors[$info['status']]; ?>;">
                            <i data-lucide="<?php echo $statusIcons[$info['status']]; ?>" class="w-3 h-3"></i> <?php echo Format::htmlchars($statusLabels[$info['status']]); ?>
                        </span>
                    </div>
                </div>
                <div>
                    <strong class="text-gray-500 text-[11px] uppercase tracking-wide">Приоритет</strong>
                    <div class="mt-1">
                        <span class="inline-flex items-center gap-1 text-white text-xs font-medium px-2.5 py-1 rounded-full" style="background:<?php echo $priorityColors[$info['priority']]; ?>;">
                            <i data-lucide="<?php echo $priorityIcons[$info['priority']]; ?>" class="w-3 h-3"></i> <?php echo Format::htmlchars($priorityLabels[$info['priority']]); ?>
                        </span>
                    </div>
                </div>
                <div>
                    <strong class="text-gray-500 text-[11px] uppercase tracking-wide">Тип задачи</strong>
                    <div class="mt-1 text-sm text-gray-700 flex items-center gap-1">
                        <i data-lucide="tag" class="w-4 h-4 text-gray-400"></i> <?php echo Format::htmlchars($typeLabels[$info['task_type']]); ?>
                    </div>
                </div>
                <div>
                    <strong class="text-gray-500 text-[11px] uppercase tracking-wide">Доска</strong>
                    <div class="mt-1 text-sm text-gray-700 flex items-center gap-1">
                        <i data-lucide="columns" class="w-4 h-4 text-gray-400"></i> <?php echo Format::htmlchars($task->getBoardName()); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description -->
        <?php if ($info['description']) { ?>
        <div class="px-6 py-5 border-b border-gray-100">
            <h4 class="text-xs uppercase text-gray-500 tracking-wide font-semibold mb-2 flex items-center gap-1"><i data-lucide="align-left" class="w-4 h-4"></i> Описание</h4>
            <div class="whitespace-pre-wrap leading-relaxed text-gray-700 text-sm"><?php echo Format::htmlchars($info['description']); ?></div>
        </div>
        <?php } ?>

        <!-- Details Grid -->
        <div class="px-6 py-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left Column -->
                <div class="space-y-4">
                    <?php if ($info['list_name']) { ?>
                    <div class="pb-4 border-b border-gray-100">
                        <div class="text-gray-400 text-[11px] uppercase tracking-wide mb-1 flex items-center gap-1">
                            <i data-lucide="list" class="w-3 h-3"></i> Список
                        </div>
                        <div class="text-sm text-gray-700"><?php echo Format::htmlchars($info['list_name']); ?></div>
                    </div>
                    <?php } ?>

                    <?php if (count($assignees) > 0) { ?>
                    <div class="pb-4 border-b border-gray-100">
                        <div class="text-gray-400 text-[11px] uppercase tracking-wide mb-1 flex items-center gap-1">
                            <i data-lucide="users" class="w-3 h-3"></i> Исполнители
                        </div>
                        <div class="text-sm text-gray-700">
                            <?php
                            $names = array();
                            foreach ($assignees as $a) {
                                $names[] = Format::htmlchars($a['name']);
                            }
                            echo implode(', ', $names);
                            ?>
                        </div>
                    </div>
                    <?php } ?>

                    <?php if ($info['time_estimate']) { ?>
                    <div class="pb-4 border-b border-gray-100">
                        <div class="text-gray-400 text-[11px] uppercase tracking-wide mb-1 flex items-center gap-1">
                            <i data-lucide="clock" class="w-3 h-3"></i> Оценка времени
                        </div>
                        <div class="text-sm text-gray-700">
                            <?php echo TaskTimeLog::formatMinutes($info['time_estimate']); ?>
                            <?php if ($timeTotal > 0) { ?>
                            <span class="text-gray-400"> / затрачено: <strong class="<?php echo ($timeTotal > $info['time_estimate']) ? 'text-red-500' : 'text-green-500'; ?>"><?php echo TaskTimeLog::formatMinutes($timeTotal); ?></strong></span>
                            <?php } ?>
                        </div>
                    </div>
                    <?php } ?>
                </div>

                <!-- Right Column -->
                <div class="space-y-4">
                    <?php if ($info['start_date']) { ?>
                    <div class="pb-4 border-b border-gray-100">
                        <div class="text-gray-400 text-[11px] uppercase tracking-wide mb-1 flex items-center gap-1">
                            <i data-lucide="calendar" class="w-3 h-3"></i> Дата начала
                        </div>
                        <div class="text-sm text-gray-700"><?php echo date('d.m.Y', strtotime($info['start_date'])); ?></div>
                    </div>
                    <?php } ?>

                    <?php if ($info['end_date']) { ?>
                    <div class="pb-4 border-b border-gray-100">
                        <div class="text-gray-400 text-[11px] uppercase tracking-wide mb-1 flex items-center gap-1">
                            <i data-lucide="calendar-check" class="w-3 h-3"></i> Дата завершения
                        </div>
                        <div class="text-sm text-gray-700"><?php echo date('d.m.Y', strtotime($info['end_date'])); ?></div>
                    </div>
                    <?php } ?>

                    <?php if ($info['deadline']) { ?>
                    <div class="pb-4 border-b border-gray-100">
                        <div class="text-gray-400 text-[11px] uppercase tracking-wide mb-1 flex items-center gap-1">
                            <i data-lucide="flag" class="w-3 h-3"></i> Дедлайн
                        </div>
                        <div class="text-sm">
                            <span class="<?php echo $task->isOverdue() ? 'text-red-500 font-semibold' : 'text-gray-700'; ?>">
                                <?php echo date('d.m.Y', strtotime($info['deadline'])); ?>
                            </span>
                            <?php if ($task->isOverdue()) { ?>
                            <span class="badge-danger ml-2">Просрочено</span>
                            <?php } ?>
                        </div>
                    </div>
                    <?php } ?>

                    <div class="pb-4 border-b border-gray-100">
                        <div class="text-gray-400 text-[11px] uppercase tracking-wide mb-1 flex items-center gap-1">
                            <i data-lucide="user" class="w-3 h-3"></i> Создатель
                        </div>
                        <div class="text-sm text-gray-700">
                            <?php echo Format::htmlchars($task->getCreatorName()); ?>
                            <span class="text-gray-400 text-xs ml-1"><?php echo date('d.m.Y H:i', strtotime($task->getCreated())); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (count($taskTags) > 0) { ?>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="text-gray-400 text-[11px] uppercase tracking-wide mb-2 flex items-center gap-1">
                    <i data-lucide="tags" class="w-3 h-3"></i> Теги
                </div>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($taskTags as $tag) { ?>
                    <span class="inline-block text-white text-xs font-medium px-3 py-1 rounded-md" style="background:<?php echo Format::htmlchars($tag['tag_color']); ?>;">
                        <?php echo Format::htmlchars($tag['tag_name']); ?>
                    </span>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>

            <?php if (count($customValues) > 0) { ?>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="text-gray-400 text-[11px] uppercase tracking-wide mb-2 flex items-center gap-1">
                    <i data-lucide="sliders" class="w-3 h-3"></i> Дополнительные поля
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <?php foreach ($customValues as $cv) { ?>
                    <div>
                        <div class="text-gray-400 text-[11px] mb-1"><?php echo Format::htmlchars($cv['field_name']); ?></div>
                        <div class="text-sm text-gray-700"><?php echo (isset($cv['field_value']) && $cv['field_value'] !== '') ? Format::htmlchars(TaskCustomField::formatValue($cv['field_type'], $cv['field_value'], isset($cv['field_options']) ? $cv['field_options'] : '')) : '—'; ?></div>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left column: Additional content -->
    <div class="lg:col-span-2 space-y-6">

        <?php if (count($subtasks) > 0) {
            $completedSubtasks = 0;
            foreach ($subtasks as $st) {
                if ($st['status'] == 'completed') $completedSubtasks++;
            }
            $subtaskTotal = count($subtasks);
        ?>
        <div class="card">
            <div class="card-header">
                <strong class="flex items-center gap-2"><i data-lucide="list" class="w-4 h-4"></i> Подзадачи
                <span class="badge-secondary"><?php echo $completedSubtasks . '/' . $subtaskTotal; ?></span>
                </strong>
            </div>
            <div class="card-body p-0">
                <div class="px-4 pt-3">
                    <div class="w-full bg-gray-200 rounded-full h-1.5 mb-2">
                        <div class="bg-green-500 h-1.5 rounded-full" style="width:<?php echo $subtaskTotal > 0 ? round($completedSubtasks / $subtaskTotal * 100) : 0; ?>%;"></div>
                    </div>
                </div>
                <div class="divide-y divide-gray-100">
                <?php foreach ($subtasks as $st) {
                    $stBadge = $st['status'] == 'completed' ? 'badge-success' : ($st['status'] == 'in_progress' ? 'badge-primary' : 'badge-secondary');
                ?>
                    <div class="subtask-row flex items-center px-4 py-2 hover:bg-gray-50">
                        <form action="tasks.php" method="POST" class="w-8 cursor-pointer">
                            <?php echo Misc::csrfField(); ?>
                            <input type="hidden" name="a" value="toggle_subtask">
                            <input type="hidden" name="subtask_id" value="<?php echo $st['task_id']; ?>">
                            <input type="hidden" name="task_id" value="<?php echo $task->getId(); ?>">
                            <button type="submit" class="p-0 bg-transparent border-0">
                            <?php if ($st['status'] == 'completed') { ?>
                            <i data-lucide="check-square" class="w-4 h-4 text-green-500"></i>
                            <?php } else { ?>
                            <i data-lucide="square" class="w-4 h-4 text-gray-400"></i>
                            <?php } ?>
                            </button>
                        </form>
                        <div class="flex-1 <?php echo $st['status'] == 'completed' ? 'line-through text-gray-400' : ''; ?>">
                            <a href="tasks.php?id=<?php echo $st['task_id']; ?>&view=1" class="text-indigo-600 hover:text-indigo-800"><?php echo Format::htmlchars($st['title']); ?></a>
                        </div>
                        <div class="w-20"><span class="<?php echo $stBadge; ?>"><?php echo Format::htmlchars($statusLabels[$st['status']]); ?></span></div>
                    </div>
                <?php } ?>
                </div>
            </div>
        </div>
        <?php } ?>

        <!-- Attachments -->
        <div class="card">
            <div class="card-header">
                <strong class="flex items-center gap-2"><i data-lucide="paperclip" class="w-4 h-4"></i> Вложения (<?php echo count($attachments); ?>)</strong>
            </div>
            <div class="card-body">
                <?php if (count($attachments) > 0) { ?>
                <div class="divide-y divide-gray-100 mb-3" id="attachments-table">
                    <?php foreach ($attachments as $att) {
                        $attObj = new TaskAttachment($att['attachment_id']);
                    ?>
                    <div class="flex items-center py-2 gap-3" data-id="<?php echo $att['attachment_id']; ?>">
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
                <p class="text-gray-500 mb-3" id="no-attachments-msg">Вложений нет</p>
                <?php } ?>

                <form id="upload-form" class="border-t border-gray-100 pt-3">
                    <label class="btn-secondary btn-sm cursor-pointer inline-flex items-center gap-2">
                        <i data-lucide="upload" class="w-4 h-4"></i> Загрузить файл
                        <input type="file" id="file-input" style="display:none;">
                    </label>
                    <small class="text-gray-500 ml-2">PDF, DOC, XLS, JPG, PNG, ZIP и др. (макс. 10 МБ)</small>
                    <div id="upload-progress" style="display:none;" class="mt-2">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-indigo-500 h-2 rounded-full animate-pulse" style="width:100%"></div>
                        </div>
                        <small class="text-gray-500">Загрузка...</small>
                    </div>
                </form>
            </div>
        </div>

        <!-- Comments (interactive) -->
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
                        <form action="tasks.php" method="POST" class="ml-auto inline" onsubmit="return confirm('Удалить комментарий?');">
                            <?php echo Misc::csrfField(); ?>
                            <input type="hidden" name="a" value="delete_comment">
                            <input type="hidden" name="comment_id" value="<?php echo $c['comment_id']; ?>">
                            <input type="hidden" name="task_id" value="<?php echo $task->getId(); ?>">
                            <input type="hidden" name="return_view" value="1">
                            <button type="submit" class="text-red-500" title="Удалить"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                        </form>
                        <?php } ?>
                    </div>
                    <div class="comment-body text-sm text-gray-700"><?php echo nl2br(Format::htmlchars($c['comment_text'])); ?></div>
                </div>
                <?php } ?>
                <?php } else { ?>
                <p class="text-gray-500 no-comments-msg">Комментариев пока нет</p>
                <?php } ?>
            </div>
            <div class="card-footer">
                <form action="tasks.php" method="POST" class="space-y-2">
                    <?php echo Misc::csrfField(); ?>
                    <input type="hidden" name="a" value="add_comment">
                    <input type="hidden" name="task_id" value="<?php echo $task->getId(); ?>">
                    <input type="hidden" name="return_view" value="1">
                    <div class="form-group">
                        <textarea name="comment_text" class="textarea" rows="2" placeholder="Написать комментарий..."></textarea>
                    </div>
                    <button type="submit" class="btn-primary btn-sm"><i data-lucide="send" class="w-4 h-4"></i> Отправить</button>
                </form>
            </div>
        </div>

        <!-- Activity Log -->
        <?php if (count($activities) > 0) { ?>
        <div class="card">
            <div class="card-header"><strong class="flex items-center gap-2"><i data-lucide="history" class="w-4 h-4"></i> История</strong></div>
            <div class="card-body p-0">
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
            </div>
        </div>
        <?php } ?>

    </div>

    <!-- Right column: Sidebar -->
    <div class="space-y-6">

        <!-- Quick Actions -->
        <div class="card border-indigo-200">
            <div class="card-header bg-indigo-50"><strong class="flex items-center gap-2 text-indigo-700"><i data-lucide="zap" class="w-4 h-4"></i> Быстрые действия</strong></div>
            <div class="card-body space-y-3">
                <form action="tasks.php" method="POST" class="form-group">
                    <?php echo Misc::csrfField(); ?>
                    <input type="hidden" name="a" value="update_status">
                    <input type="hidden" name="task_id" value="<?php echo $task->getId(); ?>">
                    <label class="label text-[11px] uppercase text-gray-500 flex items-center gap-1">
                        <i data-lucide="arrow-left-right" class="w-3 h-3"></i> Изменить статус
                    </label>
                    <select name="status" class="select text-sm" onchange="this.form.submit()">
                        <?php foreach ($statusLabels as $sk => $sv) { ?>
                        <option value="<?php echo $sk; ?>" <?php echo ($info['status']==$sk)?'selected':''; ?>><?php echo Format::htmlchars($sv); ?></option>
                        <?php } ?>
                    </select>
                </form>

                <form action="tasks.php" method="POST" class="form-group">
                    <?php echo Misc::csrfField(); ?>
                    <input type="hidden" name="a" value="update_priority">
                    <input type="hidden" name="task_id" value="<?php echo $task->getId(); ?>">
                    <label class="label text-[11px] uppercase text-gray-500 flex items-center gap-1">
                        <i data-lucide="flag" class="w-3 h-3"></i> Изменить приоритет
                    </label>
                    <select name="priority" class="select text-sm" onchange="this.form.submit()">
                        <?php foreach ($priorityLabels as $pk => $pv) { ?>
                        <option value="<?php echo $pk; ?>" <?php echo ($info['priority']==$pk)?'selected':''; ?>><?php echo Format::htmlchars($pv); ?></option>
                        <?php } ?>
                    </select>
                </form>

                <div class="border-t border-gray-200 pt-3">
                    <?php if ($task->isArchived()) { ?>
                    <form action="tasks.php" method="POST" onsubmit="return confirm('Разархивировать задачу?');">
                        <?php echo Misc::csrfField(); ?>
                        <input type="hidden" name="a" value="unarchive_task">
                        <input type="hidden" name="task_id" value="<?php echo $task->getId(); ?>">
                        <button type="submit" class="btn-success btn-sm w-full">
                            <i data-lucide="folder-open" class="w-4 h-4"></i> Разархивировать
                        </button>
                    </form>
                    <?php } else { ?>
                    <form action="tasks.php" method="POST" onsubmit="return confirm('Архивировать задачу?');">
                        <?php echo Misc::csrfField(); ?>
                        <input type="hidden" name="a" value="archive_task">
                        <input type="hidden" name="task_id" value="<?php echo $task->getId(); ?>">
                        <button type="submit" class="btn-warning btn-sm w-full">
                            <i data-lucide="archive" class="w-4 h-4"></i> Архивировать
                        </button>
                    </form>
                    <?php } ?>
                </div>
            </div>
        </div>

        <!-- Assignees -->
        <div class="card">
            <div class="card-header"><strong class="flex items-center gap-2"><i data-lucide="users" class="w-4 h-4"></i> Исполнители</strong></div>
            <div class="card-body">
                <div id="assignees-list">
                    <?php if (count($assignees) > 0) { ?>
                    <ul class="space-y-1">
                        <?php foreach ($assignees as $a) { ?>
                        <li class="flex items-center justify-between py-1">
                            <span class="text-sm flex items-center gap-1"><i data-lucide="user" class="w-4 h-4 text-gray-400"></i> <?php echo Format::htmlchars($a['name']); ?></span>
                            <form action="tasks.php" method="POST" class="inline" onsubmit="return confirm('Удалить исполнителя?');">
                                <?php echo Misc::csrfField(); ?>
                                <input type="hidden" name="a" value="remove_assignee">
                                <input type="hidden" name="task_id" value="<?php echo $task->getId(); ?>">
                                <input type="hidden" name="staff_id" value="<?php echo $a['staff_id']; ?>">
                                <button type="submit" class="text-red-500" title="Удалить"><i data-lucide="x" class="w-4 h-4"></i></button>
                            </form>
                        </li>
                        <?php } ?>
                    </ul>
                    <?php } else { ?>
                    <span class="text-gray-500 text-sm">Не назначено</span>
                    <?php } ?>
                </div>
                <div class="border-t border-gray-100 pt-2 mt-2">
                    <form action="tasks.php" method="POST" class="flex gap-2">
                        <?php echo Misc::csrfField(); ?>
                        <input type="hidden" name="a" value="add_assignee">
                        <input type="hidden" name="task_id" value="<?php echo $task->getId(); ?>">
                        <select name="staff_id" class="select text-sm flex-1">
                            <option value="">Выберите сотрудника</option>
                            <?php foreach ($staffList as $st) { ?>
                            <option value="<?php echo $st['staff_id']; ?>"><?php echo Format::htmlchars($st['name']); ?></option>
                            <?php } ?>
                        </select>
                        <button type="submit" class="btn-secondary btn-sm"><i data-lucide="plus" class="w-4 h-4"></i></button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Time Tracking -->
        <div class="card">
            <div class="card-header">
                <strong class="flex items-center gap-2"><i data-lucide="clock" class="w-4 h-4"></i> Учёт времени</strong>
            </div>
            <div class="card-body space-y-3">
                <div class="time-summary">
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

                <!-- Add time form (POST) -->
                <form action="tasks.php" method="POST" class="border-t border-gray-100 pt-3 space-y-2">
                    <?php echo Misc::csrfField(); ?>
                    <input type="hidden" name="a" value="add_timelog">
                    <input type="hidden" name="task_id" value="<?php echo $task->getId(); ?>">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs text-gray-500 mb-0.5 block">Часы</label>
                            <input type="number" name="tl_hours" class="input text-sm" placeholder="0" min="0" value="0">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 mb-0.5 block">Минуты</label>
                            <input type="number" name="tl_minutes" class="input text-sm" placeholder="0" min="0" max="59" value="0">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-0.5 block">Дата</label>
                        <input type="text" name="tl_date" class="input text-sm datepicker w-full" value="<?php echo date('m/d/Y'); ?>" autocomplete="off">
                    </div>
                    <input type="text" name="tl_notes" class="input text-sm" placeholder="Описание (необязательно)">
                    <button type="submit" class="btn-secondary btn-sm w-full"><i data-lucide="plus" class="w-4 h-4"></i> Записать время</button>
                </form>

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
                                <form action="tasks.php" method="POST" class="inline">
                                    <?php echo Misc::csrfField(); ?>
                                    <input type="hidden" name="a" value="delete_timelog">
                                    <input type="hidden" name="timelog_id" value="<?php echo $tl['log_id']; ?>">
                                    <input type="hidden" name="task_id" value="<?php echo $task->getId(); ?>">
                                    <button type="submit" class="text-red-500 hover:text-red-700" title="Удалить" onclick="return confirm('Удалить запись времени?');"><i data-lucide="x" class="w-3 h-3"></i></button>
                                </form>
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

        <?php if ($task->getTicketId()) {
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

        <!-- Archive button -->
        <div class="card">
            <div class="card-body">
                <?php if ($info['is_archived']) { ?>
                <form action="tasks.php" method="POST" onsubmit="return confirm('Разархивировать задачу?');">
                    <?php echo Misc::csrfField(); ?>
                    <input type="hidden" name="a" value="unarchive_task">
                    <input type="hidden" name="task_id" value="<?php echo $task->getId(); ?>">
                    <button type="submit" class="btn-warning btn-sm w-full">
                        <i data-lucide="inbox" class="w-4 h-4"></i> Разархивировать
                    </button>
                </form>
                <?php } else { ?>
                <form action="tasks.php" method="POST" onsubmit="return confirm('Архивировать задачу?');">
                    <?php echo Misc::csrfField(); ?>
                    <input type="hidden" name="a" value="archive_task">
                    <input type="hidden" name="task_id" value="<?php echo $task->getId(); ?>">
                    <button type="submit" class="btn-secondary btn-sm w-full">
                        <i data-lucide="archive" class="w-4 h-4"></i> Архивировать
                    </button>
                </form>
                <?php } ?>
            </div>
        </div>

    </div>
</div>

<style>
.status-completed {
    text-decoration: line-through;
    color: #9ca3af;
}
</style>

<script type="text/javascript">
$(document).ready(function(){
    var taskId = <?php echo $task ? $task->getId() : 0; ?>;

    document.getElementById('file-input').addEventListener('change', function() {
        if (!this.files || this.files.length === 0) return;

        const progressEl = document.getElementById('upload-progress');
        progressEl.style.display = 'block';

        const formData = new FormData();
        formData.append('type', 'task');
        formData.append('ref_id', taskId);
        formData.append('csrf_token', '<?php echo Misc::generateCSRFToken(); ?>');
        formData.append('file', this.files[0]);

        fetch('upload.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(function(r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function(data) {
            if (data.success) {
                location.reload();
            } else {
                alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
                progressEl.style.display = 'none';
            }
        })
        .catch(function() {
            alert('Ошибка загрузки файла');
            progressEl.style.display = 'none';
        });
    });

    $(document).on('click', '.delete-attachment', function(e){
        e.preventDefault();
        if (!confirm('Удалить вложение?')) return;

        var id = $(this).data('id');
        var $row = $(this).closest('[data-id]');

        fetch('tasks.php', {
            method: 'POST',
            headers: {'Accept': 'application/json'},
            body: new URLSearchParams({
                a: 'delete_attachment',
                attachment_id: id,
                task_id: taskId,
                csrf_token: '<?php echo Misc::generateCSRFToken(); ?>'
            })
        })
        .then(function(response) {
            if (!response.ok) throw new Error('Ошибка сети');
            return response.json();
        })
        .then(function(data) {
            if (data && data.success) {
                $row.fadeOut(300, function(){ $(this).remove(); });
            }
        })
        .catch(function(error) {
            alert('Ошибка удаления');
        });
    });
});
</script>
