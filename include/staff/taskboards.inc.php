<?php
if(!defined('OSTSCPINC') || !is_object($thisuser)) die('Доступ запрещён');

require_once(INCLUDE_DIR . 'class.taskpermission.php');

$filter_type = isset($_REQUEST['type']) ? $_REQUEST['type'] : '';
$show_archived = isset($_REQUEST['archived']) ? 1 : 0;

$sql = 'SELECT b.*, CONCAT(s.firstname," ",s.lastname) as creator_name, d.dept_name'
     . ' FROM ' . TASK_BOARDS_TABLE . ' b'
     . ' LEFT JOIN ' . TABLE_PREFIX . 'staff s ON s.staff_id=b.created_by'
     . ' LEFT JOIN ' . TABLE_PREFIX . 'department d ON d.dept_id=b.dept_id'
     . ' WHERE 1=1';

if (!$show_archived) {
    $sql .= ' AND b.is_archived=0';
}
if ($filter_type) {
    $sql .= ' AND b.board_type=' . db_input($filter_type);
}

$sql .= ' ORDER BY b.board_name ASC';
$result = db_query($sql);
$count = $result ? db_num_rows($result) : 0;
?>

<?php if($msg) { ?>
<div id="infomessage" class="alert-success"><?php echo Format::htmlchars($msg); ?></div>
<?php } elseif(!empty($errors['err'])) { ?>
<div id="errormessage" class="alert-danger"><?php echo Format::htmlchars($errors['err']); ?></div>
<?php } ?>

<div class="flex items-center justify-between mb-4">
    <h2 class="text-lg font-heading font-semibold text-gray-900 flex items-center gap-2">
        <i data-lucide="layout-grid" class="w-5 h-5"></i> Доски задач
    </h2>
    <a href="taskboards.php?a=add" class="btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i> Новая доска
    </a>
</div>

<div class="flex items-center gap-3 mb-4">
    <a href="taskboards.php" class="btn-sm <?php echo !$filter_type?'btn-primary':'btn-secondary'; ?>">Все</a>
    <a href="taskboards.php?type=project" class="btn-sm <?php echo $filter_type=='project'?'btn-primary':'btn-secondary'; ?>">Проекты</a>
    <a href="taskboards.php?type=department" class="btn-sm <?php echo $filter_type=='department'?'btn-primary':'btn-secondary'; ?>">Отделы</a>
    <label class="inline-flex items-center gap-1 ml-4 text-sm cursor-pointer">
        <input type="checkbox" class="checkbox" onchange="window.location='taskboards.php?type=<?php echo urlencode($filter_type); ?>&archived='+(this.checked?1:0);"
               <?php echo $show_archived?'checked':''; ?>>
        Показать архивные
    </label>
</div>

<form action="taskboards.php" method="POST" id="boards-form">
<?php echo Misc::csrfField(); ?>
<input type="hidden" name="a" value="process">

<?php if ($count > 0) { ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
<?php while ($row = db_fetch_array($result)) {
    if (!TaskPermission::canView($row['board_id'], $thisuser->getId())) continue;
    $board = new TaskBoard($row['board_id']);
    $taskCount = $board->getOpenTaskCount();
    $totalTasks = $board->getTaskCount();
    $canAdminBoard = TaskPermission::canAdmin($row['board_id'], $thisuser->getId());
?>
    <div class="card hover:shadow-md transition-shadow" style="border-left:4px solid <?php echo Format::htmlchars($row['color']); ?>;">
        <div class="card-body space-y-2">
            <div class="flex items-start gap-2">
                <input type="checkbox" name="boards[]" value="<?php echo $row['board_id']; ?>" class="checkbox mt-1">
                <div class="flex-1">
                    <a href="tasks.php?board_id=<?php echo $row['board_id']; ?>" class="text-base font-bold text-gray-800 hover:text-indigo-600">
                        <?php echo Format::htmlchars($row['board_name']); ?>
                    </a>
                    <?php if ($row['is_archived']) { ?>
                    <span class="badge-secondary ml-1">Архив</span>
                    <?php } ?>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="<?php echo $row['board_type']=='department'?'badge-info':'badge-secondary'; ?>">
                    <?php echo $row['board_type'] == 'department' ? 'Отдел' : 'Проект'; ?>
                </span>
                <?php if ($row['dept_name']) { ?>
                <small class="text-gray-500"><?php echo Format::htmlchars($row['dept_name']); ?></small>
                <?php } ?>
            </div>
            <?php if ($row['description']) { ?>
            <p class="text-gray-500 text-xs leading-relaxed"><?php echo Format::htmlchars(mb_substr($row['description'], 0, 100)); ?></p>
            <?php } ?>
            <div class="flex items-center gap-2">
                <a href="tasks.php?board_id=<?php echo $row['board_id']; ?>" class="btn-secondary btn-sm">
                    <i data-lucide="list" class="w-3 h-3"></i> Задачи (<?php echo $taskCount; ?>/<?php echo $totalTasks; ?>)
                </a>
                <?php if ($canAdminBoard) { ?>
                <a href="taskboards.php?id=<?php echo $row['board_id']; ?>" class="btn-secondary btn-sm">
                    <i data-lucide="settings" class="w-3 h-3"></i> Настройки
                </a>
                <?php } ?>
            </div>
            <div>
                <small class="text-gray-500">Создал: <?php echo Format::htmlchars($row['creator_name']); ?> | <?php echo date('d.m.Y', strtotime($row['created'])); ?></small>
            </div>
        </div>
    </div>
<?php } ?>
</div>

<div class="flex items-center gap-2 mt-6">
    <button type="submit" name="archive" class="btn-warning btn-sm"><i data-lucide="archive" class="w-4 h-4"></i> Архивировать</button>
    <button type="submit" name="delete" class="btn-danger btn-sm" onclick="return confirm('Удалить выбранные доски и все их задачи?');"><i data-lucide="trash-2" class="w-4 h-4"></i> Удалить</button>
</div>

<?php } else { ?>
<div class="empty-state py-16">
    <div class="empty-state-icon"><i data-lucide="columns" class="w-12 h-12"></i></div>
    <div class="empty-state-title">Досок пока нет</div>
    <div class="empty-state-text mt-2"><a href="taskboards.php?a=add" class="text-indigo-600 hover:underline">Создайте первую доску</a>.</div>
</div>
<?php } ?>

</form>
