<?php
if(!defined('OSTSCPINC') || !is_object($thisuser)) die('Доступ запрещён');

require_once(INCLUDE_DIR . 'class.tasktimelog.php');
require_once(INCLUDE_DIR . 'class.taskactivity.php');

$staffId = $thisuser->getId();

$sql = 'SELECT COUNT(*) FROM ' . TASK_ASSIGNEES_TABLE . ' a'
     . ' JOIN ' . TASKS_TABLE . ' t ON t.task_id=a.task_id'
     . ' WHERE a.staff_id=' . db_input($staffId)
     . " AND a.role='assignee'"
     . " AND t.status NOT IN ('completed','cancelled')"
     . ' AND t.parent_task_id IS NULL'
     . ' AND t.is_archived=0';
$myOpenCount = 0;
if (($res = db_query($sql)) && db_num_rows($res))
    list($myOpenCount) = db_fetch_row($res);

$sql = 'SELECT COUNT(*) FROM ' . TASKS_TABLE
     . " WHERE deadline IS NOT NULL AND deadline < NOW()"
     . " AND status NOT IN ('completed','cancelled')"
     . ' AND parent_task_id IS NULL'
     . ' AND is_archived=0';
$overdueCount = 0;
if (($res = db_query($sql)) && db_num_rows($res))
    list($overdueCount) = db_fetch_row($res);

$weekStart = date('Y-m-d', strtotime('monday this week'));
$sql = 'SELECT COUNT(*) FROM ' . TASKS_TABLE
     . " WHERE status='completed'"
     . ' AND completed_date >= ' . db_input($weekStart)
     . ' AND parent_task_id IS NULL'
     . ' AND is_archived=0';
$completedWeek = 0;
if (($res = db_query($sql)) && db_num_rows($res))
    list($completedWeek) = db_fetch_row($res);

$sql = 'SELECT COUNT(*) FROM ' . TASKS_TABLE
     . " WHERE status NOT IN ('completed','cancelled')"
     . ' AND parent_task_id IS NULL'
     . ' AND is_archived=0';
$totalActive = 0;
if (($res = db_query($sql)) && db_num_rows($res))
    list($totalActive) = db_fetch_row($res);

$myTimeWeek = TaskTimeLog::getTotalsByStaffDateRange($staffId, $weekStart, date('Y-m-d 23:59:59'));

$recentSql = 'SELECT al.*, CONCAT(s.firstname," ",s.lastname) as staff_name, t.title as task_title'
           . ' FROM ' . TASK_ACTIVITY_LOG_TABLE . ' al'
           . ' LEFT JOIN ' . TABLE_PREFIX . 'staff s ON s.staff_id=al.staff_id'
           . ' LEFT JOIN ' . TASKS_TABLE . ' t ON t.task_id=al.task_id'
           . ' ORDER BY al.created DESC LIMIT 20';
$recentActivities = array();
if (($res = db_query($recentSql)) && db_num_rows($res)) {
    while ($row = db_fetch_array($res)) {
        $recentActivities[] = $row;
    }
}

$upcomingSql = 'SELECT t.task_id, t.title, t.deadline, t.priority, b.board_name'
             . ' FROM ' . TASK_ASSIGNEES_TABLE . ' a'
             . ' JOIN ' . TASKS_TABLE . ' t ON t.task_id=a.task_id'
             . ' LEFT JOIN ' . TASK_BOARDS_TABLE . ' b ON b.board_id=t.board_id'
             . ' WHERE a.staff_id=' . db_input($staffId)
             . " AND a.role='assignee'"
             . " AND t.status NOT IN ('completed','cancelled')"
             . ' AND t.deadline IS NOT NULL'
             . ' AND t.deadline >= NOW()'
             . ' AND t.deadline <= ' . db_input(date('Y-m-d 23:59:59', strtotime('+7 days')))
             . ' AND t.is_archived=0'
             . ' ORDER BY t.deadline ASC LIMIT 10';
$upcomingTasks = array();
if (($res = db_query($upcomingSql)) && db_num_rows($res)) {
    while ($row = db_fetch_array($res)) {
        $upcomingTasks[] = $row;
    }
}

$statusDist = array();
$sdSql = 'SELECT status, COUNT(*) as cnt FROM ' . TASKS_TABLE
       . ' WHERE parent_task_id IS NULL AND is_archived=0 GROUP BY status';
if (($res = db_query($sdSql)) && db_num_rows($res)) {
    while ($row = db_fetch_array($res)) {
        $statusDist[$row['status']] = $row['cnt'];
    }
}

$priorityLabels = Task::getPriorityLabels();
$statusLabels = Task::getStatusLabels();

$statusColors = array(
    'open' => '#6b7280',
    'in_progress' => '#6366f1',
    'blocked' => '#ef4444',
    'completed' => '#22c55e',
    'cancelled' => '#9ca3af'
);

$statusTotal = 0;
foreach ($statusDist as $cnt) {
    $statusTotal += intval($cnt);
}
?>

<?php if($msg) { ?>
<div id="infomessage" class="alert-success"><?php echo Format::htmlchars($msg); ?></div>
<?php } elseif(!empty($errors['err'])) { ?>
<div id="errormessage" class="alert-danger"><?php echo Format::htmlchars($errors['err']); ?></div>
<?php } ?>

<div class="flex items-center justify-between mb-4">
    <h2 class="text-lg font-heading font-semibold text-gray-900 flex items-center gap-2">
        <i data-lucide="check-square" class="w-5 h-5"></i> Панель управления задачами
    </h2>
    <div class="flex items-center gap-2">
        <div class="inline-flex rounded-lg border border-gray-200 overflow-hidden">
            <a href="tasks.php?display=dashboard" class="btn-primary btn-sm" title="Панель"><i data-lucide="layout-dashboard" class="w-4 h-4"></i></a>
            <a href="tasks.php" class="btn-secondary btn-sm" title="Список"><i data-lucide="list" class="w-4 h-4"></i></a>
            <a href="tasks.php?display=kanban" class="btn-secondary btn-sm" title="Канбан"><i data-lucide="columns" class="w-4 h-4"></i></a>
            <a href="tasks.php?display=calendar" class="btn-secondary btn-sm" title="Календарь"><i data-lucide="calendar" class="w-4 h-4"></i></a>
        </div>
        <a href="tasks.php?a=add" class="btn-primary">
            <i data-lucide="plus" class="w-4 h-4"></i> Новая задача
        </a>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <div class="stat-card-icon bg-indigo-100 text-indigo-600"><i data-lucide="user" class="w-6 h-6"></i></div>
        <div class="stat-card-value"><?php echo intval($myOpenCount); ?></div>
        <div class="stat-card-label">Мои задачи</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon bg-yellow-100 text-yellow-600"><i data-lucide="activity" class="w-6 h-6"></i></div>
        <div class="stat-card-value"><?php echo intval($totalActive); ?></div>
        <div class="stat-card-label">Всего активных</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon bg-green-100 text-green-600"><i data-lucide="check-circle" class="w-6 h-6"></i></div>
        <div class="stat-card-value"><?php echo intval($completedWeek); ?></div>
        <div class="stat-card-label">Завершено за неделю</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon bg-red-100 text-red-600"><i data-lucide="alert-triangle" class="w-6 h-6"></i></div>
        <div class="stat-card-value"><?php echo intval($overdueCount); ?></div>
        <div class="stat-card-label">Просрочено</div>
    </div>
</div>

<div class="card mb-6">
    <div class="card-body py-3 px-4 flex items-center gap-2">
        <i data-lucide="clock" class="w-4 h-4 text-gray-400"></i>
        <strong>Моё время за неделю:</strong>
        <span><?php echo TaskTimeLog::formatMinutes($myTimeWeek); ?></span>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
    <div class="lg:col-span-7">
        <div class="card">
            <div class="card-header">
                <h3 class="text-sm font-semibold flex items-center gap-2"><i data-lucide="calendar" class="w-4 h-4"></i> Ближайшие дедлайны</h3>
            </div>
            <?php if (count($upcomingTasks) > 0) { ?>
            <div class="table-wrapper">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th class="table-th">Задача</th>
                        <th class="table-th w-[120px]">Доска</th>
                        <th class="table-th w-[100px]">Дедлайн</th>
                        <th class="table-th w-[90px]">Приоритет</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($upcomingTasks as $ut) {
                    $pClass = '';
                    switch($ut['priority']) {
                        case 'urgent': $pClass = 'badge-danger'; break;
                        case 'high': $pClass = 'badge-warning'; break;
                        case 'normal': $pClass = 'badge-info'; break;
                        default: $pClass = 'badge-secondary';
                    }
                    $daysLeft = floor((strtotime($ut['deadline']) - time()) / 86400);
                    $deadlineClass = '';
                    if ($daysLeft <= 1) $deadlineClass = 'text-red-500 font-medium';
                    elseif ($daysLeft <= 3) $deadlineClass = 'text-yellow-600';
                ?>
                    <tr>
                        <td class="table-td"><a href="tasks.php?id=<?php echo intval($ut['task_id']); ?>" class="text-indigo-600 hover:text-indigo-800"><?php echo Format::htmlchars($ut['title']); ?></a></td>
                        <td class="table-td"><small class="text-gray-600"><?php echo Format::htmlchars($ut['board_name']); ?></small></td>
                        <td class="table-td"><small class="<?php echo $deadlineClass; ?>"><?php echo date('d.m.Y', strtotime($ut['deadline'])); ?></small></td>
                        <td class="table-td"><span class="<?php echo $pClass; ?>"><?php echo Format::htmlchars($priorityLabels[$ut['priority']]); ?></span></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
            </div>
            <?php } else { ?>
            <div class="card-body">
                <div class="empty-state py-6">
                    <div class="empty-state-icon"><i data-lucide="check-circle" class="w-8 h-8"></i></div>
                    <div class="empty-state-text">Нет задач с дедлайном в ближайшие 7 дней</div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>

    <div class="lg:col-span-5">
        <div class="card">
            <div class="card-header">
                <h3 class="text-sm font-semibold flex items-center gap-2"><i data-lucide="history" class="w-4 h-4"></i> Последняя активность</h3>
            </div>
            <?php if (count($recentActivities) > 0) { ?>
            <div class="card-body p-0 max-h-[400px] overflow-y-auto">
                <div class="divide-y divide-gray-100">
                <?php foreach ($recentActivities as $act) { ?>
                    <div class="px-4 py-2 text-sm">
                        <div>
                            <strong><?php echo Format::htmlchars($act['staff_name']); ?></strong>
                            <span class="text-gray-500">&mdash; <?php echo Format::htmlchars($act['description']); ?></span>
                        </div>
                        <div class="mt-0.5 flex items-center justify-between">
                            <?php if ($act['task_id']) { ?>
                            <a href="tasks.php?id=<?php echo intval($act['task_id']); ?>" class="text-indigo-600 hover:text-indigo-800">
                                <small><?php echo Format::htmlchars($act['task_title']); ?></small>
                            </a>
                            <?php } else { ?><span></span><?php } ?>
                            <small class="text-gray-500"><?php echo Format::htmlchars(date('d.m.Y H:i', strtotime($act['created']))); ?></small>
                        </div>
                    </div>
                <?php } ?>
                </div>
            </div>
            <?php } else { ?>
            <div class="card-body">
                <div class="empty-state py-6">
                    <div class="empty-state-icon"><i data-lucide="inbox" class="w-8 h-8"></i></div>
                    <div class="empty-state-text">Нет активности</div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="text-sm font-semibold flex items-center gap-2"><i data-lucide="bar-chart-2" class="w-4 h-4"></i> Распределение по статусам</h3>
    </div>
    <div class="card-body">
        <?php if ($statusTotal > 0) { ?>
        <div class="space-y-3">
        <?php foreach ($statusLabels as $sKey => $sLabel) {
            $sCount = isset($statusDist[$sKey]) ? intval($statusDist[$sKey]) : 0;
            $sPercent = round(($sCount / $statusTotal) * 100);
            $sColor = isset($statusColors[$sKey]) ? $statusColors[$sKey] : '#6b7280';
        ?>
        <div>
            <div class="flex items-center justify-between mb-1">
                <span class="text-sm"><?php echo Format::htmlchars($sLabel); ?></span>
                <span class="text-sm text-gray-500"><?php echo $sCount; ?> (<?php echo $sPercent; ?>%)</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="h-2 rounded-full" style="width:<?php echo $sPercent; ?>%;background-color:<?php echo $sColor; ?>;"></div>
            </div>
        </div>
        <?php } ?>
        </div>
        <?php } else { ?>
        <div class="empty-state py-6">
            <div class="empty-state-icon"><i data-lucide="check-square" class="w-8 h-8"></i></div>
            <div class="empty-state-text">Нет задач для отображения</div>
        </div>
        <?php } ?>
    </div>
</div>
