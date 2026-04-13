<?php
if (!defined('OSTSCPINC') || !is_object($thisuser) || !$thisuser->isStaff()) die('Доступ запрещён');

$filter_item   = isset($_REQUEST['item_id'])   ? intval($_REQUEST['item_id'])        : 0;
$filter_staff  = isset($_REQUEST['hstaff'])    ? intval($_REQUEST['hstaff'])         : 0;
$filter_action = isset($_REQUEST['action'])    ? $_REQUEST['action']                 : '';
$filter_search = isset($_REQUEST['q'])         ? trim($_REQUEST['q'])                : '';
$filter_from   = isset($_REQUEST['date_from']) ? trim($_REQUEST['date_from'])        : '';
$filter_to     = isset($_REQUEST['date_to'])   ? trim($_REQUEST['date_to'])          : '';

$qselect = 'SELECT h.*, CONCAT(s.firstname," ",s.lastname) as staff_name,'
         . ' i.inventory_number, c.category_name, b.brand_name, m.model_name, i.custom_model';
$qfrom = ' FROM ' . INVENTORY_HISTORY_TABLE . ' h'
       . ' LEFT JOIN ' . STAFF_TABLE . ' s ON s.staff_id=h.staff_id'
       . ' LEFT JOIN ' . INVENTORY_ITEMS_TABLE . ' i ON i.item_id=h.item_id'
       . ' LEFT JOIN ' . INVENTORY_CATEGORIES_TABLE . ' c ON c.category_id=i.category_id'
       . ' LEFT JOIN ' . INVENTORY_BRANDS_TABLE . ' b ON b.brand_id=i.brand_id'
       . ' LEFT JOIN ' . INVENTORY_MODELS_TABLE . ' m ON m.model_id=i.model_id';
$qwhere = ' WHERE 1=1';
$qstr = '&view=history';

if ($filter_item) {
    $qwhere .= ' AND h.item_id=' . db_input($filter_item);
    $qstr .= '&item_id=' . $filter_item;
}
if ($filter_staff) {
    $qwhere .= ' AND h.staff_id=' . db_input($filter_staff);
    $qstr .= '&hstaff=' . $filter_staff;
}
if ($filter_action) {
    $qwhere .= ' AND h.action=' . db_input($filter_action);
    $qstr .= '&action=' . urlencode($filter_action);
}
if ($filter_search !== '') {
    $like = db_input('%' . $filter_search . '%');
    $qwhere .= ' AND (i.inventory_number LIKE ' . $like
             . ' OR b.brand_name LIKE ' . $like
             . ' OR m.model_name LIKE ' . $like
             . ' OR i.custom_model LIKE ' . $like . ')';
    $qstr .= '&q=' . urlencode($filter_search);
}
if ($filter_from) {
    $qwhere .= ' AND h.created >= ' . db_input($filter_from . ' 00:00:00');
    $qstr .= '&date_from=' . urlencode($filter_from);
}
if ($filter_to) {
    $qwhere .= ' AND h.created <= ' . db_input($filter_to . ' 23:59:59');
    $qstr .= '&date_to=' . urlencode($filter_to);
}

$total = db_count('SELECT count(*) ' . $qfrom . $qwhere);
$pagelimit = $thisuser->getPageLimit();
$pagelimit = $pagelimit ? $pagelimit : PAGE_LIMIT;
$page = (isset($_GET['p']) && is_numeric($_GET['p'])) ? $_GET['p'] : 1;
$pageNav = new Pagenate($total, $page, $pagelimit);
$pageNav->setURL('inventory.php', $qstr);

$query = "$qselect $qfrom $qwhere ORDER BY h.created DESC LIMIT " . $pageNav->getStart() . "," . $pageNav->getLimit();
$rows = db_query($query);
$showing = db_num_rows($rows) ? $pageNav->showing() : '';

$actionLabels = InventoryItem::getActionLabels();

$staffList = array();
$sres = db_query('SELECT staff_id, CONCAT(firstname," ",lastname) as name FROM ' . STAFF_TABLE . ' WHERE isactive=1 ORDER BY firstname');
if ($sres) { while ($sr = db_fetch_array($sres)) $staffList[] = $sr; }

$hasFilters = $filter_action || $filter_staff || $filter_item || $filter_search !== '' || $filter_from || $filter_to;
?>

<div class="flex items-center gap-3 mb-6">
    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
        <i data-lucide="history" class="w-5 h-5 text-indigo-600"></i>
    </div>
    <div>
        <h2 class="text-xl font-bold font-heading m-0 leading-tight">Журнал операций</h2>
        <p class="text-gray-500 text-sm mt-0.5">История всех изменений инвентарных единиц</p>
    </div>
</div>

<div class="card mb-5">
    <div class="card-body">
        <form action="inventory.php" method="GET">
            <input type="hidden" name="view" value="history">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
                <div>
                    <label class="label">Поиск по единице</label>
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
                        <input type="text" name="q" value="<?=Format::htmlchars($filter_search)?>"
                            class="input pl-9" placeholder="Инв. номер, бренд, модель...">
                    </div>
                </div>
                <div>
                    <label class="label">Действие</label>
                    <select name="action" class="select">
                        <option value="">Все действия</option>
                        <?php foreach ($actionLabels as $key => $label) {
                            $sel = ($filter_action == $key) ? ' selected' : '';
                        ?>
                        <option value="<?=$key?>"<?=$sel?>><?=Format::htmlchars($label)?></option>
                        <?php } ?>
                    </select>
                </div>
                <div>
                    <label class="label">Сотрудник</label>
                    <select name="hstaff" class="select">
                        <option value="">Все сотрудники</option>
                        <?php foreach ($staffList as $st) {
                            $sel = ($filter_staff == $st['staff_id']) ? ' selected' : '';
                        ?>
                        <option value="<?=$st['staff_id']?>"<?=$sel?>><?=Format::htmlchars($st['name'])?></option>
                        <?php } ?>
                    </select>
                </div>
                <div>
                    <label class="label">Период</label>
                    <div class="flex items-center gap-1.5">
                        <input type="date" name="date_from" value="<?=Format::htmlchars($filter_from)?>" class="input flex-1 min-w-0" title="С">
                        <span class="text-gray-400 flex-shrink-0">—</span>
                        <input type="date" name="date_to" value="<?=Format::htmlchars($filter_to)?>" class="input flex-1 min-w-0" title="По">
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="btn-primary btn-sm"><i data-lucide="search" class="w-4 h-4"></i> Найти</button>
                <?php if ($hasFilters) { ?>
                <a href="inventory.php?view=history" class="btn-secondary btn-sm"><i data-lucide="x" class="w-4 h-4"></i> Сбросить</a>
                <?php } ?>
                <?php if ($showing) { ?>
                <span class="text-sm text-gray-500 ml-auto"><?=$showing?></span>
                <?php } ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="overflow-x-auto">
        <table class="table-modern w-full">
        <thead>
            <tr>
                <th class="table-th w-36">Дата</th>
                <th class="table-th">Единица</th>
                <th class="table-th w-40">Действие</th>
                <th class="table-th">Было</th>
                <th class="table-th">Стало</th>
                <th class="table-th w-36">Сотрудник</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if ($rows && db_num_rows($rows)) {
            while ($row = db_fetch_array($rows)) {
                $actionLabel = isset($actionLabels[$row['action']]) ? $actionLabels[$row['action']] : $row['action'];
                $model = $row['model_name'] ? $row['model_name'] : $row['custom_model'];
                $itemLabel = $row['inventory_number'] ? $row['inventory_number'] : '#' . $row['item_id'];
                $itemSub = trim($row['brand_name'] . ' ' . $model);

                $actionBadge = array(
                    'created'        => 'badge-success',
                    'status_changed' => 'badge-warning',
                    'moved'          => 'badge-info',
                    'assigned'       => 'badge-info',
                    'updated'        => 'badge-secondary',
                    'deleted'        => 'badge-danger',
                );
                $badgeClass = isset($actionBadge[$row['action']]) ? $actionBadge[$row['action']] : 'badge-secondary';
        ?>
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="table-td text-sm text-gray-500 whitespace-nowrap">
                    <?=$row['created'] ? date('d.m.Y', strtotime($row['created'])) : ''?>
                    <div class="text-xs text-gray-400"><?=$row['created'] ? date('H:i', strtotime($row['created'])) : ''?></div>
                </td>
                <td class="table-td">
                    <a href="inventory.php?id=<?=$row['item_id']?>" class="font-medium text-indigo-600 hover:text-indigo-800 leading-tight">
                        <?=Format::htmlchars($itemLabel)?>
                    </a>
                    <?php if ($itemSub) { ?>
                    <div class="text-xs text-gray-400 mt-0.5"><?=Format::htmlchars($itemSub)?></div>
                    <?php } ?>
                </td>
                <td class="table-td"><span class="<?=$badgeClass?> text-xs"><?=Format::htmlchars($actionLabel)?></span></td>
                <td class="table-td text-sm text-gray-500"><?=Format::htmlchars($row['old_value']) ?: '<span class="text-gray-300">—</span>'?></td>
                <td class="table-td text-sm text-gray-800"><?=Format::htmlchars($row['new_value']) ?: '<span class="text-gray-300">—</span>'?></td>
                <td class="table-td text-sm text-gray-700 whitespace-nowrap"><?=Format::htmlchars($row['staff_name'])?></td>
            </tr>
        <?php }
        } else { ?>
            <tr>
                <td colspan="6" class="table-td">
                    <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                        <i data-lucide="history" class="w-12 h-12 mb-3 opacity-40"></i>
                        <span class="text-sm"><?=$hasFilters ? 'Ничего не найдено' : 'Нет записей в журнале'?></span>
                    </div>
                </td>
            </tr>
        <?php } ?>
        </tbody>
        </table>
    </div>
</div>

<?php if ($total > $pagelimit) { ?>
<div class="flex justify-end py-3">
    <?=$pageNav->getPageLinks()?>
</div>
<?php } ?>
