<?php
if(!defined('OSTSCPINC') || !is_object($thisuser)) die('Доступ запрещён');

$view = isset($_REQUEST['view']) ? $_REQUEST['view'] : '';
$filter_board = isset($_REQUEST['board_id']) ? intval($_REQUEST['board_id']) : 0;
$filter_status = isset($_REQUEST['status']) ? $_REQUEST['status'] : '';
$filter_priority = isset($_REQUEST['priority']) ? $_REQUEST['priority'] : '';
$filter_assignee = isset($_REQUEST['assignee']) ? intval($_REQUEST['assignee']) : 0;
$search = isset($_REQUEST['q']) ? trim($_REQUEST['q']) : '';
$sort = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : 'created';
$order = (isset($_REQUEST['order']) && strtoupper($_REQUEST['order']) == 'ASC') ? 'ASC' : 'DESC';
$negorder = ($order == 'ASC') ? 'DESC' : 'ASC';
$page = isset($_REQUEST['p']) ? max(1, intval($_REQUEST['p'])) : 1;
$pagelimit = 25;

$qselect = 'SELECT t.*, b.board_name, l.list_name, CONCAT(s.firstname," ",s.lastname) as creator_name,'
         . ' GROUP_CONCAT(DISTINCT CONCAT(sa.firstname," ",sa.lastname) SEPARATOR ", ") as assignee_names,'
         . ' SUM(IFNULL(tl.time_spent, 0)) as time_total';
$qfrom = ' FROM ' . TASKS_TABLE . ' t'
       . ' LEFT JOIN ' . TASK_BOARDS_TABLE . ' b ON b.board_id=t.board_id'
       . ' LEFT JOIN ' . TASK_LISTS_TABLE . ' l ON l.list_id=t.list_id'
       . ' LEFT JOIN ' . TABLE_PREFIX . 'staff s ON s.staff_id=t.created_by'
       . ' LEFT JOIN ' . TASK_ASSIGNEES_TABLE . ' agg ON agg.task_id=t.task_id AND agg.role=\'assignee\''
       . ' LEFT JOIN ' . TABLE_PREFIX . 'staff sa ON sa.staff_id=agg.staff_id'
       . ' LEFT JOIN ' . TASK_TIME_LOGS_TABLE . ' tl ON tl.task_id=t.task_id';
$qwhere = ' WHERE t.parent_task_id IS NULL AND t.is_archived=0';

if ($view == 'mytasks') {
    $qfrom .= ' JOIN ' . TASK_ASSIGNEES_TABLE . ' a ON a.task_id=t.task_id';
    $qwhere .= ' AND a.staff_id=' . db_input($thisuser->getId()) . " AND a.role='assignee'";
    if (!$filter_status) {
        $qwhere .= " AND t.status NOT IN ('completed','cancelled')";
    }
} elseif ($view == 'overdue') {
    $qwhere .= " AND t.deadline IS NOT NULL AND t.deadline < NOW() AND t.status NOT IN ('completed','cancelled')";
} elseif ($view == 'unassigned') {
    $qfrom .= ' LEFT JOIN ' . TASK_ASSIGNEES_TABLE . ' a2 ON a2.task_id=t.task_id AND a2.role=\'assignee\'';
    $qwhere .= ' AND a2.assignment_id IS NULL';
    $qwhere .= " AND t.status NOT IN ('completed','cancelled')";
}

if ($filter_board) {
    $qwhere .= ' AND t.board_id=' . db_input($filter_board);
}
if ($filter_status) {
    $qwhere .= ' AND t.status=' . db_input($filter_status);
}
if ($filter_priority) {
    $qwhere .= ' AND t.priority=' . db_input($filter_priority);
}
if ($filter_assignee) {
    if ($view != 'mytasks') {
        $qfrom .= ' JOIN ' . TASK_ASSIGNEES_TABLE . ' af ON af.task_id=t.task_id';
        $qwhere .= ' AND af.staff_id=' . db_input($filter_assignee) . " AND af.role='assignee'";
    }
}
if ($search) {
    $qwhere .= ' AND MATCH(t.title, t.description) AGAINST(' . db_input($search) . ')';
}

require_once(INCLUDE_DIR . 'class.tasktag.php');
$filter_tags = isset($_REQUEST['tags']) ? $_REQUEST['tags'] : array();
if (!is_array($filter_tags)) $filter_tags = array($filter_tags);
$filter_tags = array_map('intval', $filter_tags);
$filter_tags = array_filter($filter_tags);

if (count($filter_tags) > 0) {
    $qfrom .= ' JOIN ' . TASK_TAG_ASSOC_TABLE . ' ftag ON ftag.task_id=t.task_id';
    $qwhere .= ' AND ftag.tag_id IN(' . implode(',', $filter_tags) . ')';
}

$sortCols = array(
    'title' => 't.title',
    'status' => 't.status',
    'priority' => "FIELD(t.priority,'urgent','high','normal','low')",
    'deadline' => 't.deadline',
    'created' => 't.created',
    'board' => 'b.board_name'
);
$orderBy = isset($sortCols[$sort]) ? $sortCols[$sort] : 't.created';

$total = 0;
$csql = 'SELECT COUNT(DISTINCT t.task_id) ' . $qfrom . $qwhere;
if (($res = db_query($csql)) && db_num_rows($res))
    list($total) = db_fetch_row($res);

require_once(INCLUDE_DIR . 'class.pagenate.php');
$pageNav = new Pagenate($total, $page, $pagelimit);
$qstr = '&view=' . urlencode($view) . '&board_id=' . $filter_board . '&status=' . urlencode($filter_status)
      . '&priority=' . urlencode($filter_priority) . '&assignee=' . $filter_assignee . '&q=' . urlencode($search);
if (count($filter_tags) > 0) {
    foreach ($filter_tags as $_ftid) {
        $qstr .= '&tags[]=' . intval($_ftid);
    }
}
$pageNav->setURL('tasks.php', $qstr);

$query = $qselect . $qfrom . $qwhere . ' GROUP BY t.task_id ORDER BY ' . $orderBy . ' ' . $order
       . ' LIMIT ' . $pageNav->getStart() . ',' . $pageNav->getLimit();
$result = db_query($query);

$taskIds = array();
$taskRows = array();
if ($result && db_num_rows($result)) {
    while ($row = db_fetch_array($result)) {
        $taskIds[] = intval($row['task_id']);
        $row['tags'] = array();
        $taskRows[] = $row;
    }
}

$taskTagsMap = array();
if (count($taskIds) > 0) {
    $tagSql = 'SELECT ta.task_id, tg.tag_id, tg.tag_name, tg.tag_color'
            . ' FROM ' . TASK_TAG_ASSOC_TABLE . ' ta'
            . ' JOIN ' . TASK_TAGS_TABLE . ' tg ON tg.tag_id=ta.tag_id'
            . ' WHERE ta.task_id IN (' . implode(',', $taskIds) . ')'
            . ' ORDER BY tg.tag_name ASC';
    if (($tagRes = db_query($tagSql)) && db_num_rows($tagRes)) {
        while ($tagRow = db_fetch_array($tagRes)) {
            $tid = intval($tagRow['task_id']);
            if (!isset($taskTagsMap[$tid])) {
                $taskTagsMap[$tid] = array();
            }
            $taskTagsMap[$tid][] = $tagRow;
        }
    }
}

require_once(INCLUDE_DIR . 'class.taskpermission.php');
$boards = array();
$bsql = 'SELECT board_id, board_name FROM ' . TASK_BOARDS_TABLE . ' WHERE is_archived=0 ORDER BY board_name';
if (($bres = db_query($bsql)) && db_num_rows($bres)) {
    while ($brow = db_fetch_array($bres)) {
        if (TaskPermission::canView($brow['board_id'], $thisuser->getId())) {
            $boards[] = $brow;
        }
    }
}

$canEditBoard = $filter_board ? TaskPermission::canEdit($filter_board, $thisuser->getId()) : false;

$staffList = array();
$ssql = 'SELECT staff_id, CONCAT(firstname," ",lastname) as name FROM ' . TABLE_PREFIX . 'staff WHERE isactive=1 ORDER BY firstname';
if (($sres = db_query($ssql)) && db_num_rows($sres)) {
    while ($srow = db_fetch_array($sres)) {
        $staffList[] = $srow;
    }
}

require_once(INCLUDE_DIR . 'class.tasktimelog.php');
require_once(INCLUDE_DIR . 'class.taskfilter.php');

$savedFilters = TaskFilter::getByStaff($thisuser->getId());

$boardTags = array();
if ($filter_board) {
    $boardTags = TaskTag::getByBoard($filter_board);
}
if (!$filter_board) {
    $tgsql = 'SELECT tg.*, b.board_name FROM ' . TASK_TAGS_TABLE . ' tg'
           . ' LEFT JOIN ' . TASK_BOARDS_TABLE . ' b ON b.board_id=tg.board_id'
           . ' ORDER BY b.board_name, tg.tag_name';
    if (($tgres = db_query($tgsql)) && db_num_rows($tgres)) {
        while ($tgrow = db_fetch_array($tgres)) {
            $boardTags[] = $tgrow;
        }
    }
}

$priorityLabels = Task::getPriorityLabels();
$statusLabels = Task::getStatusLabels();

$viewTitles = array(
    'mytasks' => 'Мои задачи',
    'overdue' => 'Просроченные задачи',
    'unassigned' => 'Задачи без исполнителя'
);
$viewTitle = isset($viewTitles[$view]) ? $viewTitles[$view] : 'Все задачи';
?>

<?php if($msg) { ?>
<div id="infomessage" class="alert-success"><?php echo Format::htmlchars($msg); ?></div>
<?php } elseif(!empty($errors['err'])) { ?>
<div id="errormessage" class="alert-danger"><?php echo Format::htmlchars($errors['err']); ?></div>
<?php } ?>

<div class="flex items-center justify-between mb-4">
    <h2 class="text-lg font-heading font-semibold text-gray-900 flex items-center gap-2">
        <i data-lucide="check-square" class="w-5 h-5"></i> <?php echo $viewTitle; ?>
    </h2>
    <div class="flex items-center gap-2">
        <div class="inline-flex rounded-lg border border-gray-200 overflow-hidden">
            <a href="tasks.php?display=dashboard" class="btn-secondary btn-sm" title="Панель"><i data-lucide="layout-dashboard" class="w-4 h-4"></i></a>
            <a href="tasks.php?view=<?php echo urlencode($view); ?><?php echo $filter_board ? '&board_id='.$filter_board : ''; ?>" class="btn-primary btn-sm" title="Список"><i data-lucide="list" class="w-4 h-4"></i></a>
            <a href="tasks.php?display=kanban<?php echo $filter_board ? '&board_id='.$filter_board : ''; ?>" class="btn-secondary btn-sm" title="Канбан"><i data-lucide="columns" class="w-4 h-4"></i></a>
            <a href="tasks.php?display=calendar<?php echo $filter_board ? '&board_id='.$filter_board : ''; ?>" class="btn-secondary btn-sm" title="Календарь"><i data-lucide="calendar" class="w-4 h-4"></i></a>
        </div>
        <a href="task_export.php?view=<?php echo urlencode($view); ?>&board_id=<?php echo $filter_board; ?>&status=<?php echo urlencode($filter_status); ?>&priority=<?php echo urlencode($filter_priority); ?>&assignee=<?php echo $filter_assignee; ?>&q=<?php echo urlencode($search); ?>" class="btn-secondary btn-sm">
            <i data-lucide="download" class="w-4 h-4"></i> Экспорт CSV
        </a>
        <?php if ($canEditBoard) { ?>
        <a href="tasks.php?a=add<?php echo $filter_board ? '&board_id='.$filter_board : ''; ?>" class="btn-primary">
            <i data-lucide="plus" class="w-4 h-4"></i> Новая задача
        </a>
        <?php } ?>
    </div>
</div>

<div class="flex gap-2 mb-3">
    <a href="tasks.php?view=mytasks" class="btn-sm <?php echo $view=='mytasks'?'btn-primary':'btn-secondary'; ?>">Мои задачи</a>
    <a href="tasks.php" class="btn-sm <?php echo !$view?'btn-primary':'btn-secondary'; ?>">Все</a>
    <a href="tasks.php?view=overdue" class="btn-sm <?php echo $view=='overdue'?'btn-danger':'btn-secondary'; ?>">Просроченные</a>
    <a href="tasks.php?view=unassigned" class="btn-sm <?php echo $view=='unassigned'?'btn-warning':'btn-secondary'; ?>">Без исполнителя</a>
</div>

<div class="card mb-3">
    <div class="card-body p-2">
        <form method="get" action="tasks.php" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="view" value="<?php echo Format::htmlchars($view); ?>">
            <select name="board_id" class="select text-sm w-auto">
                <option value="">Доска</option>
                <?php foreach ($boards as $b) { ?>
                <option value="<?php echo $b['board_id']; ?>" <?php echo $filter_board==$b['board_id']?'selected':''; ?>><?php echo Format::htmlchars($b['board_name']); ?></option>
                <?php } ?>
            </select>
            <select name="status" class="select text-sm w-auto">
                <option value="">Статус</option>
                <?php foreach ($statusLabels as $k=>$v) { ?>
                <option value="<?php echo $k; ?>" <?php echo $filter_status==$k?'selected':''; ?>><?php echo Format::htmlchars($v); ?></option>
                <?php } ?>
            </select>
            <select name="priority" class="select text-sm w-auto">
                <option value="">Приоритет</option>
                <?php foreach ($priorityLabels as $k=>$v) { ?>
                <option value="<?php echo $k; ?>" <?php echo $filter_priority==$k?'selected':''; ?>><?php echo Format::htmlchars($v); ?></option>
                <?php } ?>
            </select>
            <select name="assignee" class="select text-sm w-auto">
                <option value="">Исполнитель</option>
                <?php foreach ($staffList as $st) { ?>
                <option value="<?php echo $st['staff_id']; ?>" <?php echo $filter_assignee==$st['staff_id']?'selected':''; ?>><?php echo Format::htmlchars($st['name']); ?></option>
                <?php } ?>
            </select>
            <?php if (count($boardTags) > 0) { ?>
            <div class="relative inline-block" id="tagFilterWrap">
                <button type="button" class="select text-sm w-auto inline-flex items-center gap-1 whitespace-nowrap" id="tagFilterBtn">
                    <i data-lucide="tag" class="w-3.5 h-3.5 flex-shrink-0"></i>
                    <span>Теги</span><?php if (count($filter_tags)) { ?><span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1 text-xs font-semibold bg-blue-100 text-blue-700 rounded-full"><?php echo count($filter_tags); ?></span><?php } ?>
                    <svg class="w-3 h-3 flex-shrink-0 transition-transform duration-200" id="tagFilterChevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="tagFilterDropdown" class="hidden absolute left-0 top-full mt-1 bg-white border border-gray-200 rounded-xl shadow-xl z-50 w-[240px] overflow-hidden" style="max-width:calc(100vw - 32px);">
                    <div class="px-3 py-2 border-b border-gray-100 flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Теги</span>
                        <?php if (count($filter_tags)) { ?>
                        <button type="button" class="text-xs text-blue-600 hover:text-blue-800" onclick="document.querySelectorAll('#tagFilterDropdown input[type=checkbox]').forEach(function(c){c.checked=false})">Сбросить</button>
                        <?php } ?>
                    </div>
                    <div class="overflow-y-auto" style="max-height:132px;">
                        <?php foreach ($boardTags as $tg) {
                            $checked = in_array(intval($tg['tag_id']), $filter_tags) ? 'checked' : '';
                            $tagLabel = $tg['tag_name'];
                            if (isset($tg['board_name']) && !$filter_board) {
                                $tagLabel .= ' (' . $tg['board_name'] . ')';
                            }
                        ?>
                        <label class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer select-none active:bg-gray-100 transition-colors">
                            <input type="checkbox" name="tags[]" value="<?php echo intval($tg['tag_id']); ?>" <?php echo $checked; ?> class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 flex-shrink-0">
                            <span class="truncate"><?php echo Format::htmlchars($tagLabel); ?></span>
                        </label>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <script>
            (function(){
                var btn = document.getElementById('tagFilterBtn');
                var dd = document.getElementById('tagFilterDropdown');
                var chev = document.getElementById('tagFilterChevron');
                var wrap = document.getElementById('tagFilterWrap');
                function toggle() {
                    var open = !dd.classList.contains('hidden');
                    if (open) { dd.classList.add('hidden'); chev.style.transform=''; }
                    else { dd.classList.remove('hidden'); chev.style.transform='rotate(180deg)'; }
                }
                btn.addEventListener('click', function(e){ e.preventDefault(); toggle(); });
                document.addEventListener('click', function(e){
                    if (!wrap.contains(e.target)) { dd.classList.add('hidden'); chev.style.transform=''; }
                });
                dd.addEventListener('touchmove', function(e){ e.stopPropagation(); }, {passive:true});
            })();
            </script>
            <?php } ?>
            <input type="text" name="q" value="<?php echo Format::htmlchars($search); ?>" placeholder="Поиск..." class="input text-sm w-[150px]">
            <button type="submit" class="btn-primary btn-sm"><i data-lucide="search" class="w-4 h-4"></i></button>
            <?php if ($filter_board || $filter_status || $filter_priority || $filter_assignee || $search || count($filter_tags)) { ?>
            <a href="tasks.php?view=<?php echo urlencode($view); ?>" class="btn-secondary btn-sm"><i data-lucide="x" class="w-4 h-4"></i> Сброс</a>
            <?php } ?>
        </form>
    </div>
</div>

<div class="flex items-center gap-2 mb-3">
    <div class="relative inline-block">
        <button type="button" class="btn-secondary btn-sm" onclick="var m=document.getElementById('savedFiltersMenu');m.style.display=m.style.display==='block'?'none':'block';">
            <i data-lucide="bookmark" class="w-4 h-4"></i> Сохранённые фильтры <svg class="w-3 h-3 inline-block ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <ul class="absolute left-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-50 min-w-[220px] py-1" id="savedFiltersMenu" style="display:none;">
            <?php if (count($savedFilters) > 0) {
                foreach ($savedFilters as $sf) { ?>
            <li class="list-none">
                <a href="#" class="flex items-center justify-between px-3 py-2 text-sm text-gray-700 hover:bg-gray-50" onclick="TaskFilters.loadSavedFilter(<?php echo intval($sf['filter_id']); ?>); return false;">
                    <span>
                        <?php if ($sf['is_default']) { ?><i data-lucide="star" class="w-4 h-4 text-yellow-500 inline-block"></i> <?php } ?>
                        <?php echo Format::htmlchars($sf['filter_name']); ?>
                    </span>
                    <span class="flex items-center gap-1 ml-2">
                        <i data-lucide="<?php echo $sf['is_default'] ? 'star' : 'star'; ?>" class="w-3 h-3 text-gray-400 hover:text-yellow-500 cursor-pointer" data-id="<?php echo intval($sf['filter_id']); ?>" title="<?php echo $sf['is_default'] ? 'Убрать из по умолчанию' : 'Сделать по умолчанию'; ?>" onclick="event.stopPropagation(); event.preventDefault(); TaskFilters.toggleDefault(<?php echo intval($sf['filter_id']); ?>, <?php echo $sf['is_default'] ? '1' : '0'; ?>);"></i>
                        <i data-lucide="trash-2" class="w-3 h-3 text-gray-400 hover:text-red-500 cursor-pointer" data-id="<?php echo intval($sf['filter_id']); ?>" title="Удалить" onclick="event.stopPropagation(); event.preventDefault(); TaskFilters.deleteFilter(<?php echo intval($sf['filter_id']); ?>);"></i>
                    </span>
                </a>
            </li>
            <?php }
            } else { ?>
            <li class="list-none"><span class="block px-3 py-2 text-sm text-gray-400">Нет сохранённых фильтров</span></li>
            <?php } ?>
        </ul>
    </div>

    <?php if ($filter_board || $filter_status || $filter_priority || $filter_assignee || $search || count($filter_tags)) { ?>
    <button type="button" class="btn-success btn-sm" id="btnSaveFilter" onclick="TaskFilters.showSaveDialog();">
        <i data-lucide="save" class="w-4 h-4"></i> Сохранить фильтр
    </button>
    <?php } ?>

    <span id="saveFilterForm" style="display:none;" class="flex items-center gap-2 ml-2">
        <input type="text" id="saveFilterName" class="input text-sm w-[200px]" placeholder="Название фильтра...">
        <button type="button" class="btn-primary btn-sm" onclick="TaskFilters.saveCurrentFilter();"><i data-lucide="check" class="w-4 h-4"></i></button>
        <button type="button" class="btn-secondary btn-sm" onclick="TaskFilters.hideSaveDialog();"><i data-lucide="x" class="w-4 h-4"></i></button>
    </span>
</div>
<script>
document.addEventListener('click', function(e) {
    var menu = document.getElementById('savedFiltersMenu');
    if (menu && menu.style.display === 'block' && !menu.parentElement.contains(e.target)) {
        menu.style.display = 'none';
    }
});
</script>

<div class="mb-2 text-sm text-gray-500">
    <?php echo $pageNav->showing(); ?>
</div>

<form action="tasks.php" method="POST" id="tasks-form">
<?php echo Misc::csrfField(); ?>
<input type="hidden" name="a" value="process">

<div class="table-wrapper">
<table class="table-modern">
    <thead>
        <tr>
            <th class="table-th w-5"><input type="checkbox" id="selectAll" class="checkbox"></th>
            <th class="table-th"><a href="tasks.php?sort=title&order=<?php echo $negorder.$qstr; ?>" class="flex items-center gap-1">Название <i data-lucide="arrow-up-down" class="w-3 h-3"></i></a></th>
            <th class="table-th w-[120px]"><a href="tasks.php?sort=board&order=<?php echo $negorder.$qstr; ?>" class="flex items-center gap-1">Доска <i data-lucide="arrow-up-down" class="w-3 h-3"></i></a></th>
            <th class="table-th w-[100px]"><a href="tasks.php?sort=status&order=<?php echo $negorder.$qstr; ?>" class="flex items-center gap-1">Статус <i data-lucide="arrow-up-down" class="w-3 h-3"></i></a></th>
            <th class="table-th w-[90px]"><a href="tasks.php?sort=priority&order=<?php echo $negorder.$qstr; ?>" class="flex items-center gap-1">Приоритет <i data-lucide="arrow-up-down" class="w-3 h-3"></i></a></th>
            <th class="table-th w-[140px]">Исполнитель</th>
            <th class="table-th w-[70px]">Время</th>
            <th class="table-th w-[100px]"><a href="tasks.php?sort=deadline&order=<?php echo $negorder.$qstr; ?>" class="flex items-center gap-1">Дедлайн <i data-lucide="arrow-up-down" class="w-3 h-3"></i></a></th>
            <th class="table-th w-[100px]"><a href="tasks.php?sort=created&order=<?php echo $negorder.$qstr; ?>" class="flex items-center gap-1">Создана <i data-lucide="arrow-up-down" class="w-3 h-3"></i></a></th>
        </tr>
    </thead>
    <tbody>
    <?php
    if (count($taskRows) > 0) {
        foreach ($taskRows as $row) {
            $t = new Task($row['task_id']);
            $isOverdue = false;
            if ($row['deadline'] && !in_array($row['status'], array('completed','cancelled'))) {
                $isOverdue = strtotime($row['deadline']) < time();
            }

            $assigneeNames = $row['assignee_names'] ? $row['assignee_names'] : '';

            $rowTimeTotal = intval($row['time_total']);

            $rowTags = isset($taskTagsMap[$row['task_id']]) ? $taskTagsMap[$row['task_id']] : array();

            $pClass = '';
            switch($row['priority']) {
                case 'urgent': $pClass = 'badge-danger'; break;
                case 'high': $pClass = 'badge-warning'; break;
                case 'normal': $pClass = 'badge-info'; break;
                default: $pClass = 'badge-secondary';
            }
            $sClass = '';
            switch($row['status']) {
                case 'in_progress': $sClass = 'badge-primary'; break;
                case 'blocked': $sClass = 'badge-danger'; break;
                case 'completed': $sClass = 'badge-success'; break;
                default: $sClass = 'badge-secondary';
            }
    ?>
        <tr class="<?php echo $isOverdue ? 'bg-red-50' : ''; ?>">
            <td class="table-td"><input type="checkbox" name="tids[]" value="<?php echo $row['task_id']; ?>" class="checkbox"></td>
            <td class="table-td">
                <a href="tasks.php?id=<?php echo $row['task_id']; ?>&view=1" class="text-indigo-600 hover:text-indigo-800 font-medium" title="<?php echo Format::htmlchars($row['title']); ?>">
                    <?php echo Format::htmlchars($row['title']); ?>
                </a>
                <?php
                if (count($rowTags) > 0) {
                    echo '<br>';
                    foreach ($rowTags as $rt) {
                        echo '<span class="task-tag-badge inline-block text-xs text-white rounded-full px-2 py-0.5 mr-1 mt-1" style="background:' . Format::htmlchars($rt['tag_color']) . ';">' . Format::htmlchars($rt['tag_name']) . '</span> ';
                    }
                }
                if ($row['description']) {
                    $desc = strip_tags($row['description']);
                    $desc = mb_substr($desc, 0, 100);
                    echo '<br><small class="text-gray-500 task-description-preview">' . Format::htmlchars($desc) . '...</small>';
                }
                ?>
            </td>
            <td class="table-td"><small class="text-gray-600"><?php echo Format::htmlchars($row['board_name']); ?></small></td>
            <td class="table-td"><span class="<?php echo $sClass; ?>"><?php echo Format::htmlchars($statusLabels[$row['status']]); ?></span></td>
            <td class="table-td"><span class="<?php echo $pClass; ?>"><?php echo Format::htmlchars($priorityLabels[$row['priority']]); ?></span></td>
            <td class="table-td"><small class="text-gray-600"><?php echo Format::htmlchars($assigneeNames); ?></small></td>
            <td class="table-td">
                <?php
                if ($rowTimeTotal > 0) {
                    echo '<small>' . TaskTimeLog::formatMinutes($rowTimeTotal);
                    if ($row['time_estimate'] > 0) {
                        echo '/' . TaskTimeLog::formatMinutes($row['time_estimate']);
                    }
                    echo '</small>';
                } elseif ($row['time_estimate'] > 0) {
                    echo '<small class="text-gray-500">0/' . TaskTimeLog::formatMinutes($row['time_estimate']) . '</small>';
                }
                ?>
            </td>
            <td class="table-td">
                <?php if ($row['deadline']) {
                    echo '<small' . ($isOverdue ? ' class="text-red-500 font-medium"' : '') . '>';
                    echo Format::htmlchars(date('d.m.Y', strtotime($row['deadline'])));
                    echo '</small>';
                } ?>
            </td>
            <td class="table-td"><small class="text-gray-600"><?php echo Format::htmlchars(date('d.m.Y', strtotime($row['created']))); ?></small></td>
        </tr>
    <?php }
    } else { ?>
        <tr>
            <td colspan="9" class="table-td">
                <div class="empty-state py-8">
                    <div class="empty-state-icon"><i data-lucide="inbox" class="w-8 h-8"></i></div>
                    <div class="empty-state-title">Задачи не найдены</div>
                </div>
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>
</div>

<?php if ($total > 0) { ?>
<div class="flex items-center justify-between mt-4">
    <div>
        <small class="text-gray-500">Выбрать:
            <a href="#" class="text-indigo-600 hover:underline" onclick="return select_all('tasks-form');">Все</a> |
            <a href="#" class="text-indigo-600 hover:underline" onclick="return reset_all('tasks-form');">Ничего</a>
        </small>
    </div>
    <div class="pagination">
        <?php echo $pageNav->getPageLinks(); ?>
    </div>
</div>

<div class="flex flex-wrap items-center gap-2 mt-4">
    <button type="submit" name="complete" class="btn-success btn-sm"><i data-lucide="check" class="w-4 h-4"></i> Завершить</button>
    <button type="submit" name="delete" class="btn-danger btn-sm" onclick="return confirm('Удалить выбранные задачи?');"><i data-lucide="trash-2" class="w-4 h-4"></i> Удалить</button>
    <span class="flex items-center gap-2 ml-4 pl-4 border-l border-gray-200">
        <select name="bulk_status" class="select text-sm w-auto">
            <option value="">Статус</option>
            <?php foreach ($statusLabels as $k=>$v) { ?>
            <option value="<?php echo $k; ?>"><?php echo Format::htmlchars($v); ?></option>
            <?php } ?>
        </select>
        <button type="submit" name="change_status" class="btn-secondary btn-sm">Изменить статус</button>
    </span>
    <span class="flex items-center gap-2 ml-2 pl-4 border-l border-gray-200">
        <select name="bulk_priority" class="select text-sm w-auto">
            <option value="">Приоритет</option>
            <?php foreach ($priorityLabels as $k=>$v) { ?>
            <option value="<?php echo $k; ?>"><?php echo Format::htmlchars($v); ?></option>
            <?php } ?>
        </select>
        <button type="submit" name="change_priority" class="btn-secondary btn-sm">Изменить приоритет</button>
    </span>
</div>
<?php } ?>

</form>

<script type="text/javascript">
var savedFilterConfigs = {};
<?php foreach ($savedFilters as $sf) { ?>
savedFilterConfigs[<?php echo intval($sf['filter_id']); ?>] = <?php echo json_encode($sf['filter_config']); ?>;
<?php } ?>
</script>
<script type="text/javascript" src="js/task-filters.js"></script>
<script type="text/javascript">
$(document).ready(function(){
    $('#selectAll').click(function(){
        $('input[name="tids[]"]').prop('checked', this.checked);
    });
});
</script>
