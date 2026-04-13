<?php
if(!defined('OSTSCPINC') || !is_object($thisuser)) die('Доступ запрещён');

$filter_board = isset($_REQUEST['board_id']) ? intval($_REQUEST['board_id']) : 0;
$cal_view = isset($_REQUEST['cal']) ? $_REQUEST['cal'] : 'month';
if (!in_array($cal_view, array('month', 'week'))) $cal_view = 'month';

$boards = array();
$bsql = 'SELECT board_id, board_name, color FROM ' . TASK_BOARDS_TABLE . ' WHERE is_archived=0 ORDER BY board_name';
if (($bres = db_query($bsql)) && db_num_rows($bres)) {
    while ($brow = db_fetch_array($bres)) {
        $boards[] = $brow;
    }
}
?>

<?php if($msg) { ?>
<div id="infomessage" class="alert-success"><?php echo Format::htmlchars($msg); ?></div>
<?php } elseif(!empty($errors['err'])) { ?>
<div id="errormessage" class="alert-danger"><?php echo Format::htmlchars($errors['err']); ?></div>
<?php } ?>

<div class="flex items-center justify-between mb-4">
    <h2 class="text-lg font-heading font-semibold text-gray-900 flex items-center gap-2">
        <i data-lucide="check-square" class="w-5 h-5"></i> Календарь задач
    </h2>
    <div class="flex items-center gap-2">
        <div class="inline-flex rounded-lg border border-gray-200 overflow-hidden">
            <a href="tasks.php?display=dashboard" class="btn-secondary btn-sm" title="Панель"><i data-lucide="layout-dashboard" class="w-4 h-4"></i></a>
            <a href="tasks.php<?php echo $filter_board ? '?board_id='.$filter_board : ''; ?>" class="btn-secondary btn-sm" title="Список"><i data-lucide="list" class="w-4 h-4"></i></a>
            <a href="tasks.php?display=kanban<?php echo $filter_board ? '&board_id='.$filter_board : ''; ?>" class="btn-secondary btn-sm" title="Канбан"><i data-lucide="columns" class="w-4 h-4"></i></a>
            <a href="tasks.php?display=calendar<?php echo $filter_board ? '&board_id='.$filter_board : ''; ?>" class="btn-primary btn-sm" title="Календарь"><i data-lucide="calendar" class="w-4 h-4"></i></a>
        </div>
        <a href="tasks.php?a=add<?php echo $filter_board ? '&board_id='.$filter_board : ''; ?>" class="btn-primary">
            <i data-lucide="plus" class="w-4 h-4"></i> Новая задача
        </a>
    </div>
</div>

<div class="flex items-center justify-between mb-4 flex-wrap gap-3">
    <form method="get" action="tasks.php" class="flex items-center gap-2">
        <input type="hidden" name="display" value="calendar">
        <input type="hidden" name="cal" value="<?php echo Format::htmlchars($cal_view); ?>">
        <select name="board_id" class="select text-sm w-auto" onchange="this.form.submit();">
            <option value="">Все доски</option>
            <?php foreach ($boards as $b) { ?>
            <option value="<?php echo $b['board_id']; ?>"
                <?php echo $filter_board==$b['board_id']?'selected':''; ?>>
                <?php echo Format::htmlchars($b['board_name']); ?>
            </option>
            <?php } ?>
        </select>
    </form>

    <div class="flex items-center gap-4 text-sm">
        <span class="flex items-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-full bg-red-500"></span> Срочный</span>
        <span class="flex items-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-full bg-yellow-500"></span> Высокий</span>
        <span class="flex items-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-full bg-indigo-400"></span> Обычный</span>
        <span class="flex items-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-full bg-gray-400"></span> Низкий</span>
    </div>
</div>

<div class="card">
    <div class="card-body p-4">
        <div id="task-calendar"></div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    TaskCalendar.init({
        view: '<?php echo $cal_view; ?>',
        boardId: <?php echo $filter_board ? $filter_board : 0; ?>
    });
});
</script>
