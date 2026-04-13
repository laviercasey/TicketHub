<?php
if(!defined('OSTSCPINC') || !is_object($thisuser)) die('Доступ запрещён');

$filter_board = isset($_REQUEST['board_id']) ? intval($_REQUEST['board_id']) : 0;

require_once(INCLUDE_DIR . 'class.taskpermission.php');
$boards = array();
$bsql = 'SELECT board_id, board_name, color FROM ' . TASK_BOARDS_TABLE . ' WHERE is_archived=0 ORDER BY board_name';
if (($bres = db_query($bsql)) && db_num_rows($bres)) {
    while ($brow = db_fetch_array($bres)) {
        if (TaskPermission::canView($brow['board_id'], $thisuser->getId())) {
            $boards[] = $brow;
        }
    }
}

if (!$filter_board && count($boards) > 0) {
    $filter_board = $boards[0]['board_id'];
}

$currentBoard = null;
if ($filter_board) {
    $currentBoard = TaskBoard::lookup($filter_board);
}

$canEditBoard = $filter_board ? TaskPermission::canEdit($filter_board, $thisuser->getId()) : false;

$lists = array();
if ($currentBoard) {
    $lists = $currentBoard->getLists();
}

$tasksByList = array();
$tasksByList[0] = array();

foreach ($lists as $l) {
    $tasksByList[$l['list_id']] = array();
}

if ($filter_board) {
    $tsql = 'SELECT t.*,'
          . ' CONCAT(s.firstname," ",s.lastname) as creator_name,'
          . ' GROUP_CONCAT(DISTINCT CONCAT(sa.firstname," ",sa.lastname) SEPARATOR ", ") as assignee_names,'
          . ' COUNT(DISTINCT st.task_id) as subtask_count,'
          . ' SUM(IFNULL(tl.time_spent, 0)) as time_total'
          . ' FROM ' . TASKS_TABLE . ' t'
          . ' LEFT JOIN ' . TABLE_PREFIX . 'staff s ON s.staff_id=t.created_by'
          . ' LEFT JOIN ' . TASK_ASSIGNEES_TABLE . ' a ON a.task_id=t.task_id AND a.role=\'assignee\''
          . ' LEFT JOIN ' . TABLE_PREFIX . 'staff sa ON sa.staff_id=a.staff_id'
          . ' LEFT JOIN ' . TASKS_TABLE . ' st ON st.parent_task_id=t.task_id AND st.is_archived=0'
          . ' LEFT JOIN ' . TASK_TIME_LOGS_TABLE . ' tl ON tl.task_id=t.task_id'
          . ' WHERE t.board_id=' . db_input($filter_board)
          . ' AND t.parent_task_id IS NULL'
          . ' AND t.is_archived=0'
          . ' GROUP BY t.task_id'
          . ' ORDER BY t.position ASC, t.task_id ASC';

    $taskIds = array();

    if (($tres = db_query($tsql)) && db_num_rows($tres)) {
        while ($trow = db_fetch_array($tres)) {
            $taskIds[] = intval($trow['task_id']);
            $lid = intval($trow['list_id']);
            if (!isset($tasksByList[$lid])) {
                $tasksByList[$lid] = array();
            }

            if (!$trow['assignee_names']) {
                $trow['assignee_names'] = '';
            }

            $trow['subtask_count'] = intval($trow['subtask_count']);
            $trow['time_total'] = intval($trow['time_total']);
            $trow['tags'] = array();

            $tasksByList[$lid][] = $trow;
        }
    }

    if (count($taskIds) > 0) {
        $tagSql = 'SELECT ta.task_id, tg.tag_id, tg.tag_name, tg.tag_color'
                . ' FROM ' . TASK_TAG_ASSOC_TABLE . ' ta'
                . ' JOIN ' . TASK_TAGS_TABLE . ' tg ON tg.tag_id=ta.tag_id'
                . ' WHERE ta.task_id IN (' . implode(',', $taskIds) . ')'
                . ' ORDER BY tg.tag_name ASC';

        if (($tagRes = db_query($tagSql)) && db_num_rows($tagRes)) {
            while ($tagRow = db_fetch_array($tagRes)) {
                $tid = intval($tagRow['task_id']);

                foreach ($tasksByList as $lid => $tasks) {
                    foreach ($tasks as $idx => $task) {
                        if ($task['task_id'] == $tid) {
                            $tasksByList[$lid][$idx]['tags'][] = array(
                                'tag_id' => $tagRow['tag_id'],
                                'tag_name' => $tagRow['tag_name'],
                                'tag_color' => $tagRow['tag_color']
                            );
                        }
                    }
                }
            }
        }
    }
}

if (count($tasksByList[0]) > 0) {
    $statusListMap = array();
    foreach ($lists as $l) {
        if (!empty($l['status']) && !isset($statusListMap[$l['status']])) {
            $statusListMap[$l['status']] = $l['list_id'];
        }
    }
    $noListRemaining = array();
    foreach ($tasksByList[0] as $noTask) {
        $noTaskStatus = $noTask['status'];
        if (isset($statusListMap[$noTaskStatus])) {
            $tasksByList[$statusListMap[$noTaskStatus]][] = $noTask;
        } else {
            $noListRemaining[] = $noTask;
        }
    }
    $tasksByList[0] = $noListRemaining;
}

require_once(INCLUDE_DIR . 'class.tasktag.php');
require_once(INCLUDE_DIR . 'class.tasktimelog.php');

$priorityLabels = Task::getPriorityLabels();
$statusLabels = Task::getStatusLabels();
$priorityClasses = array('low' => 'badge-secondary', 'normal' => 'badge-info', 'high' => 'badge-warning', 'urgent' => 'badge-danger');
$statusClasses = array('open' => 'badge-secondary', 'in_progress' => 'badge-primary', 'review' => 'badge-warning', 'blocked' => 'badge-danger', 'completed' => 'badge-success', 'cancelled' => 'badge-secondary');
?>

<?php if($msg) { ?>
<div id="infomessage" class="alert-success"><?php echo Format::htmlchars($msg); ?></div>
<?php } elseif(!empty($errors['err'])) { ?>
<div id="errormessage" class="alert-danger"><?php echo Format::htmlchars($errors['err']); ?></div>
<?php } ?>

<div class="flex items-center justify-between mb-4">
    <h2 class="text-lg font-heading font-semibold text-gray-900 flex items-center gap-2">
        <i data-lucide="check-square" class="w-5 h-5"></i>
        <?php if ($currentBoard) { ?>
        <?php $boardInfo = $currentBoard->getInfo(); ?>
        <span class="inline-block w-3.5 h-3.5 rounded-full" style="background:<?php echo Format::htmlchars($boardInfo['color']); ?>;"></span>
        <?php echo Format::htmlchars($currentBoard->getName()); ?>
        <?php } else { ?>
        Канбан-доска
        <?php } ?>
    </h2>
    <div class="flex items-center gap-2">
        <div class="inline-flex rounded-lg border border-gray-200 overflow-hidden">
            <a href="tasks.php?display=dashboard" class="btn-secondary btn-sm" title="Панель"><i data-lucide="layout-dashboard" class="w-4 h-4"></i></a>
            <a href="tasks.php<?php echo $filter_board ? '?board_id='.$filter_board : ''; ?>" class="btn-secondary btn-sm" title="Список"><i data-lucide="list" class="w-4 h-4"></i></a>
            <a href="tasks.php?display=kanban<?php echo $filter_board ? '&board_id='.$filter_board : ''; ?>" class="btn-primary btn-sm" title="Канбан"><i data-lucide="columns" class="w-4 h-4"></i></a>
            <a href="tasks.php?display=calendar<?php echo $filter_board ? '&board_id='.$filter_board : ''; ?>" class="btn-secondary btn-sm" title="Календарь"><i data-lucide="calendar" class="w-4 h-4"></i></a>
        </div>
        <?php if ($filter_board && $canEditBoard) { ?>
        <a href="tasks.php?a=add&board_id=<?php echo $filter_board; ?>" class="btn-primary">
            <i data-lucide="plus" class="w-4 h-4"></i> Новая задача
        </a>
        <?php } ?>
    </div>
</div>

<div class="kanban-board-selector mb-4">
    <form method="get" action="tasks.php" class="flex items-center gap-2">
        <input type="hidden" name="display" value="kanban">
        <select name="board_id" class="select text-sm w-auto" onchange="this.form.submit();">
            <option value="">Выберите доску</option>
            <?php foreach ($boards as $b) { ?>
            <option value="<?php echo $b['board_id']; ?>"
                <?php echo $filter_board==$b['board_id']?'selected':''; ?>>
                <?php echo Format::htmlchars($b['board_name']); ?>
            </option>
            <?php } ?>
        </select>
        <?php if ($currentBoard) { ?>
        <a href="taskboards.php?id=<?php echo $filter_board; ?>" class="btn-secondary btn-sm"><i data-lucide="settings" class="w-4 h-4"></i> Настройки доски</a>
        <?php } ?>
    </form>
</div>

<?php if (!$filter_board || !$currentBoard) { ?>
<div class="empty-state py-16">
    <div class="empty-state-icon"><i data-lucide="columns" class="w-12 h-12"></i></div>
    <div class="empty-state-title">Выберите доску для отображения канбан-доски.</div>
    <?php if (count($boards) == 0) { ?>
    <p class="mt-4"><a href="taskboards.php?a=add" class="btn-primary">Создать первую доску</a></p>
    <?php } ?>
</div>

<?php } elseif (count($lists) == 0) { ?>
<div class="empty-state py-16">
    <div class="empty-state-icon"><i data-lucide="list" class="w-12 h-12"></i></div>
    <div class="empty-state-title">В этой доске нет списков.</div>
    <div class="empty-state-text"><a href="taskboards.php?id=<?php echo $filter_board; ?>" class="text-indigo-600 hover:underline">Добавьте списки</a> для канбан-доски.</div>
</div>

<?php } else { ?>
<div class="kanban-board">
    <?php foreach ($lists as $list) {
        $listTasks = isset($tasksByList[$list['list_id']]) ? $tasksByList[$list['list_id']] : array();
        $taskCount = count($listTasks);
    ?>
    <div class="kanban-column">
        <div class="kanban-column-header">
            <span class="font-semibold text-sm"><?php echo Format::htmlchars($list['list_name']); ?></span>
            <span class="badge-secondary text-xs"><?php echo $taskCount; ?></span>
        </div>
        <div class="kanban-column-body" data-list-id="<?php echo $list['list_id']; ?>">
            <?php foreach ($listTasks as $t) {
                $isOverdue = false;
                if ($t['deadline'] && !in_array($t['status'], array('completed','cancelled'))) {
                    $isOverdue = strtotime($t['deadline']) < time();
                }
                $pClass = isset($priorityClasses[$t['priority']]) ? $priorityClasses[$t['priority']] : 'badge-secondary';
                $sClass = isset($statusClasses[$t['status']]) ? $statusClasses[$t['status']] : 'badge-secondary';
            ?>
            <div class="kanban-card card priority-<?php echo Format::htmlchars($t['priority']); ?> <?php echo ($t['status']=='completed')?'opacity-60':''; ?>" data-task-id="<?php echo $t['task_id']; ?>">
                <div class="card-body p-3 space-y-2">
                    <div class="kanban-card-title font-medium text-sm">
                        <a href="tasks.php?id=<?php echo $t['task_id']; ?>&view=1" class="text-gray-800 hover:text-indigo-600"><?php echo Format::htmlchars($t['title']); ?></a>
                    </div>
                    <?php if ($t['description']) {
                        $desc = strip_tags($t['description']);
                        $desc = mb_substr($desc, 0, 80);
                    ?>
                    <div class="kanban-card-description text-xs text-gray-500"><?php echo Format::htmlchars($desc); ?>...</div>
                    <?php } ?>
                    <div class="kanban-card-meta flex flex-wrap gap-1">
                        <span class="<?php echo $pClass; ?> text-[10px]"><?php echo Format::htmlchars($priorityLabels[$t['priority']]); ?></span>
                        <?php if ($t['status'] != 'open') { ?>
                        <span class="<?php echo $sClass; ?> text-[10px]"><?php echo Format::htmlchars($statusLabels[$t['status']]); ?></span>
                        <?php } ?>
                    </div>
                    <?php if ($t['assignee_names']) { ?>
                    <div class="kanban-card-assignee text-xs text-gray-500 flex items-center gap-1"><i data-lucide="user" class="w-3 h-3"></i> <?php echo Format::htmlchars($t['assignee_names']); ?></div>
                    <?php } ?>
                    <?php if (isset($t['tags']) && count($t['tags']) > 0) { ?>
                    <div class="kanban-card-tags flex flex-wrap gap-1">
                        <?php foreach ($t['tags'] as $kt) { ?>
                        <span class="task-tag-badge inline-block text-[10px] text-white rounded-full px-2 py-0.5" style="background:<?php echo Format::htmlchars($kt['tag_color']); ?>;"><?php echo Format::htmlchars($kt['tag_name']); ?></span>
                        <?php } ?>
                    </div>
                    <?php } ?>
                    <?php if ($t['deadline']) { ?>
                    <div class="kanban-card-deadline text-xs flex items-center gap-1 <?php echo $isOverdue ? 'text-red-500 font-medium' : 'text-gray-500'; ?>">
                        <i data-lucide="clock" class="w-3 h-3"></i> <?php echo date('d.m.Y', strtotime($t['deadline'])); ?>
                        <?php if ($isOverdue) { ?><span class="text-red-500">(просрочена)</span><?php } ?>
                    </div>
                    <?php } ?>
                    <?php if ($t['subtask_count'] > 0) { ?>
                    <div class="kanban-card-subtasks text-xs text-gray-500 flex items-center gap-1"><i data-lucide="list" class="w-3 h-3"></i> <?php echo $t['subtask_count']; ?> подзадач</div>
                    <?php } ?>
                    <?php if ($t['time_total'] > 0 || $t['time_estimate'] > 0) { ?>
                    <div class="kanban-card-time text-xs text-gray-500 flex items-center gap-1"><i data-lucide="clock" class="w-3 h-3"></i> <?php echo TaskTimeLog::formatMinutes($t['time_total']); ?><?php echo $t['time_estimate'] ? ' / ' . TaskTimeLog::formatMinutes($t['time_estimate']) : ''; ?></div>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>
        </div>
        <?php if ($canEditBoard) { ?>
        <div class="kanban-quick-add p-2 border-t border-gray-100">
            <input type="text" placeholder="+ Добавить задачу..." class="input text-sm w-full"
                   data-list-id="<?php echo $list['list_id']; ?>"
                   data-board-id="<?php echo $filter_board; ?>">
        </div>
        <?php } ?>
    </div>
    <?php } ?>

    <?php
    $noListTasks = isset($tasksByList[0]) ? $tasksByList[0] : array();
    if (count($noListTasks) > 0) { ?>
    <div class="kanban-column">
        <div class="kanban-column-header bg-yellow-50">
            <span class="font-semibold text-sm">Без списка</span>
            <span class="badge-warning text-xs"><?php echo count($noListTasks); ?></span>
        </div>
        <div class="kanban-column-body" data-list-id="0">
            <?php foreach ($noListTasks as $t) {
                $pClass = isset($priorityClasses[$t['priority']]) ? $priorityClasses[$t['priority']] : 'badge-secondary';
            ?>
            <div class="kanban-card card priority-<?php echo Format::htmlchars($t['priority']); ?>" data-task-id="<?php echo $t['task_id']; ?>">
                <div class="card-body p-3 space-y-2">
                    <div class="kanban-card-title font-medium text-sm">
                        <a href="tasks.php?id=<?php echo $t['task_id']; ?>&view=1" class="text-gray-800 hover:text-indigo-600"><?php echo Format::htmlchars($t['title']); ?></a>
                    </div>
                    <div class="kanban-card-meta flex flex-wrap gap-1">
                        <span class="<?php echo $pClass; ?> text-[10px]"><?php echo Format::htmlchars($priorityLabels[$t['priority']]); ?></span>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
    <?php } ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
$(document).ready(function(){
    var csrfToken = '<?php echo Misc::generateCSRFToken(); ?>';

    var canEdit = <?php echo $canEditBoard ? 'true' : 'false'; ?>;
    document.querySelectorAll('.kanban-column-body').forEach(function(el){
        new Sortable(el, {
            group: 'kanban',
            animation: 150,
            ghostClass: 'opacity-30',
            dragClass: 'shadow-lg',
            handle: '.kanban-card',
            disabled: !canEdit,
            onEnd: function(evt){
                if (!canEdit) return;
                var taskId = evt.item.getAttribute('data-task-id');
                var newListId = evt.to.getAttribute('data-list-id');
                var newIndex = evt.newIndex;

                fetch('tasks.php', {
                    method: 'POST',
                    headers: {'Accept': 'application/json'},
                    body: new URLSearchParams({
                        a: 'move_task',
                        task_id: taskId,
                        list_id: newListId,
                        position: newIndex,
                        csrf_token: csrfToken
                    })
                });
            }
        });
    });

    $('.kanban-quick-add input').keypress(function(e){
        if (e.which !== 13) return;
        var $input = $(this);
        var title = $.trim($input.val());
        if (!title) return;

        var listId = $input.data('list-id');
        var boardId = $input.data('board-id');
        $input.prop('disabled', true);

        fetch('tasks.php', {
            method: 'POST',
            headers: {'Accept': 'application/json'},
            body: new URLSearchParams({
                a: 'kanban_quick_add',
                title: title,
                board_id: boardId,
                list_id: listId,
                csrf_token: csrfToken
            })
        })
        .then(function(r){ return r.json(); })
        .then(function(data){
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Ошибка');
                $input.prop('disabled', false);
            }
        })
        .catch(function(){
            alert('Ошибка сети');
            $input.prop('disabled', false);
        });
    });
});
</script>
<?php } ?>
