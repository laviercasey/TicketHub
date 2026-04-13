<?php
if (!defined('OSTSCPINC') || !is_object($thisuser) || !$thisuser->isStaff()) die('Доступ запрещён');

$filter_category = isset($_REQUEST['category']) ? intval($_REQUEST['category']) : 0;
$filter_location = isset($_REQUEST['location']) ? intval($_REQUEST['location']) : 0;
$filter_status = isset($_REQUEST['status']) ? $_REQUEST['status'] : '';
$filter_staff = isset($_REQUEST['staff']) ? intval($_REQUEST['staff']) : 0;
$search = isset($_REQUEST['q']) ? trim($_REQUEST['q']) : '';

$qselect = 'SELECT i.*, c.category_name, b.brand_name, m.model_name,'
         . ' l.location_name,'
         . ' CONCAT(s.firstname," ",s.lastname) as staff_name';
$qfrom = ' FROM ' . INVENTORY_ITEMS_TABLE . ' i'
       . ' LEFT JOIN ' . INVENTORY_CATEGORIES_TABLE . ' c ON c.category_id=i.category_id'
       . ' LEFT JOIN ' . INVENTORY_BRANDS_TABLE . ' b ON b.brand_id=i.brand_id'
       . ' LEFT JOIN ' . INVENTORY_MODELS_TABLE . ' m ON m.model_id=i.model_id'
       . ' LEFT JOIN ' . LOCATIONS_TABLE . ' l ON l.location_id=i.location_id'
       . ' LEFT JOIN ' . STAFF_TABLE . ' s ON s.staff_id=i.assigned_staff_id';
$qwhere = ' WHERE 1=1';
$qstr = '';

if ($filter_category) {
    $qwhere .= ' AND i.category_id=' . db_input($filter_category);
    $qstr .= '&category=' . $filter_category;
}
if ($filter_location) {
    $locIds = array($filter_location);
    $childIds = InventoryLocation::getAllIds($filter_location);
    foreach ($childIds as $cid) $locIds[] = $cid;
    $qwhere .= ' AND i.location_id IN(' . implode(',', array_map('intval', $locIds)) . ')';
    $qstr .= '&location=' . $filter_location;
}
if ($filter_status) {
    $qwhere .= ' AND i.status=' . db_input($filter_status);
    $qstr .= '&status=' . urlencode($filter_status);
}
if ($filter_staff) {
    $qwhere .= ' AND i.assigned_staff_id=' . db_input($filter_staff);
    $qstr .= '&staff=' . $filter_staff;
}
if ($search) {
    $qwhere .= ' AND MATCH(i.inventory_number, i.serial_number, i.part_number, i.description) AGAINST(' . db_input($search) . ')';
    $qstr .= '&q=' . urlencode($search);
}

$sortOptions = array(
    'inv' => 'i.inventory_number', 'category' => 'c.category_name',
    'brand' => 'b.brand_name', 'location' => 'l.location_name',
    'staff' => 'staff_name', 'status' => 'i.status',
    'cost' => 'i.cost', 'created' => 'i.created'
);
$orderWays = array('DESC' => 'DESC', 'ASC' => 'ASC');
$_sort = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : '';
$_order = isset($_REQUEST['order']) ? $_REQUEST['order'] : '';
$order_column = isset($sortOptions[$_sort]) ? $sortOptions[$_sort] : 'i.created';
$order = isset($orderWays[strtoupper($_order)]) ? $orderWays[strtoupper($_order)] : 'DESC';
$order_by = " ORDER BY $order_column $order";

$total = db_count('SELECT count(*) ' . $qfrom . $qwhere);
$pagelimit = $thisuser->getPageLimit();
$pagelimit = $pagelimit ? $pagelimit : PAGE_LIMIT;
$page = (isset($_GET['p']) && is_numeric($_GET['p'])) ? $_GET['p'] : 1;
$pageNav = new Pagenate($total, $page, $pagelimit);
$pageNav->setURL('inventory.php', $qstr . '&sort=' . urlencode($_sort) . '&order=' . urlencode($_order));

$query = "$qselect $qfrom $qwhere $order_by LIMIT " . $pageNav->getStart() . "," . $pageNav->getLimit();
$items = db_query($query);
$showing = db_num_rows($items) ? $pageNav->showing() : '';
$negorder = $order == 'DESC' ? 'ASC' : 'DESC';

$categories = InventoryCategory::getTree();
$locations = InventoryLocation::getTree();
$statusLabels = InventoryItem::getStatusLabels();
$assignLabels = InventoryItem::getAssignmentLabels();

$staffList = array();
$sres = db_query('SELECT staff_id, CONCAT(firstname," ",lastname) as name FROM ' . STAFF_TABLE . ' WHERE isactive=1 ORDER BY firstname');
if ($sres) { while ($sr = db_fetch_array($sres)) $staffList[] = $sr; }
?>
<div>
    <?php if (isset($errors['err']) && $errors['err']) { ?>
        <div class="alert-danger mb-4"><i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0"></i><span><?=Format::htmlchars($errors['err'])?></span></div>
    <?php } elseif ($msg) { ?>
        <div class="alert-success mb-4"><i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i><span><?=Format::htmlchars($msg)?></span></div>
    <?php } elseif ($warn) { ?>
        <div class="alert-warning mb-4"><i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0"></i><span><?=Format::htmlchars($warn)?></span></div>
    <?php } ?>
</div>

<div class="flex justify-between items-center mb-4">
    <h2 class="text-lg font-heading font-semibold text-gray-900 flex items-center gap-2">
        <i data-lucide="boxes" class="w-5 h-5"></i> Вся техника
    </h2>
    <a href="inventory.php?a=add" class="btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i> Добавить технику
    </a>
</div>

<div class="card">
    <div class="card-header">Фильтрация</div>
    <div class="card-body">
        <form action="inventory.php" method="GET" class="flex flex-wrap items-end gap-3 inv-filters">
            <div class="form-group">
                <label>Поиск:</label>
                <input type="text" name="q" class="input" value="<?=Format::htmlchars($search)?>" placeholder="Инв.#, S/N, P/N...">
            </div>
            <div class="form-group">
                <label>Категория:</label>
                <select name="category" class="select inv-filter-auto">
                    <option value="">Все</option>
                    <?php foreach ($categories as $cat) {
                        $indent = str_repeat('&nbsp;&nbsp;', $cat['depth']);
                        $sel = ($filter_category == $cat['category_id']) ? ' selected' : '';
                    ?>
                    <option value="<?=$cat['category_id']?>"<?=$sel?>><?=$indent?><?=Format::htmlchars($cat['category_name'])?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group">
                <label>Локация:</label>
                <select name="location" class="select inv-filter-auto">
                    <option value="">Все</option>
                    <?php foreach ($locations as $loc) {
                        $indent = str_repeat('&nbsp;&nbsp;', $loc['depth']);
                        $sel = ($filter_location == $loc['location_id']) ? ' selected' : '';
                    ?>
                    <option value="<?=$loc['location_id']?>"<?=$sel?>><?=$indent?><?=Format::htmlchars($loc['location_name'])?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group">
                <label>Статус:</label>
                <select name="status" class="select inv-filter-auto">
                    <option value="">Все</option>
                    <?php foreach ($statusLabels as $key => $label) {
                        $sel = ($filter_status == $key) ? ' selected' : '';
                    ?>
                    <option value="<?=$key?>"<?=$sel?>><?=Format::htmlchars($label)?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group">
                <label>Сотрудник:</label>
                <select name="staff" class="select inv-filter-auto">
                    <option value="">Все</option>
                    <?php foreach ($staffList as $st) {
                        $sel = ($filter_staff == $st['staff_id']) ? ' selected' : '';
                    ?>
                    <option value="<?=$st['staff_id']?>"<?=$sel?>><?=Format::htmlchars($st['name'])?></option>
                    <?php } ?>
                </select>
            </div>
            <button type="submit" class="btn-secondary btn-sm"><i data-lucide="search" class="w-4 h-4"></i></button>
            <?php if ($qstr) { ?>
            <a href="inventory.php" class="btn-secondary btn-sm"><i data-lucide="x" class="w-4 h-4"></i> Сбросить</a>
            <?php } ?>
        </form>
    </div>
</div>

<div class="flex justify-end items-center gap-2 mb-4">
    <a href="inventory_export.php?<?=ltrim($qstr,'&')?>" class="btn-secondary btn-sm"><i data-lucide="download" class="w-4 h-4"></i> Экспорт CSV</a>
    <strong><?=$showing?></strong>
</div>

<form action="inventory.php" method="POST">
<?=Misc::csrfField()?>
<input type="hidden" name="a" value="process">
<table class="table-modern w-full inv-table">
<thead>
    <tr>
        <th class="table-th" width="20"><input type="checkbox" id="inv-select-all" class="checkbox"></th>
        <th class="table-th"><a href="inventory.php?sort=inv&order=<?=$negorder?><?=$qstr?>" class="text-indigo-600 hover:text-indigo-800">Инв.#</a></th>
        <th class="table-th"><a href="inventory.php?sort=category&order=<?=$negorder?><?=$qstr?>" class="text-indigo-600 hover:text-indigo-800">Категория</a></th>
        <th class="table-th">Бренд / Модель</th>
        <th class="table-th">S/N</th>
        <th class="table-th"><a href="inventory.php?sort=location&order=<?=$negorder?><?=$qstr?>" class="text-indigo-600 hover:text-indigo-800">Локация</a></th>
        <th class="table-th"><a href="inventory.php?sort=staff&order=<?=$negorder?><?=$qstr?>" class="text-indigo-600 hover:text-indigo-800">За кем</a></th>
        <th class="table-th"><a href="inventory.php?sort=status&order=<?=$negorder?><?=$qstr?>" class="text-indigo-600 hover:text-indigo-800">Статус</a></th>
        <th class="table-th inv-cost"><a href="inventory.php?sort=cost&order=<?=$negorder?><?=$qstr?>" class="text-indigo-600 hover:text-indigo-800">Стоимость</a></th>
    </tr>
</thead>
<tbody>
<?php
if ($items && db_num_rows($items)) {
    while ($row = db_fetch_array($items)) {
        $model = $row['model_name'] ? $row['model_name'] : $row['custom_model'];
        $brandModel = trim($row['brand_name'] . ' ' . $model);
        $statusClass = 'badge-' . $row['status'];
        $statusLabel = isset($statusLabels[$row['status']]) ? $statusLabels[$row['status']] : $row['status'];
?>
    <tr class="hover:bg-gray-50 transition-colors">
        <td class="table-td"><input type="checkbox" name="items[]" value="<?=$row['item_id']?>" class="checkbox inv-checkbox"></td>
        <td class="table-td"><a href="inventory.php?id=<?=$row['item_id']?>" class="text-indigo-600 hover:text-indigo-800"><?=$row['inventory_number'] ? Format::htmlchars($row['inventory_number']) : '#'.$row['item_id']?></a></td>
        <td class="table-td"><?=Format::htmlchars($row['category_name'])?></td>
        <td class="table-td"><?=Format::htmlchars($brandModel)?></td>
        <td class="table-td"><?=Format::htmlchars($row['serial_number'])?></td>
        <td class="table-td"><?=Format::htmlchars($row['location_name'])?></td>
        <td class="table-td"><?=Format::htmlchars($row['staff_name'])?></td>
        <td class="table-td"><span class="<?=$statusClass?>"><?=$statusLabel?></span></td>
        <td class="table-td inv-cost"><?=$row['cost'] ? number_format($row['cost'], 2, '.', ' ') : ''?></td>
    </tr>
<?php }
} else { ?>
    <tr><td colspan="9" class="table-td text-center py-12 text-gray-500">
        <i data-lucide="boxes" class="w-12 h-12 mx-auto mb-4 text-gray-300"></i><br>
        <?=$search || $qstr ? 'Ничего не найдено по заданным фильтрам' : 'Пока нет записей. <a href="inventory.php?a=add" class="text-indigo-600 hover:text-indigo-800">Добавить первую</a>'?>
    </td></tr>
<?php } ?>
</tbody>
</table>

<?php if ($total) { ?>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <div class="flex flex-wrap items-end gap-3 bulk-actions py-3">
            <label>С выбранными:</label>
            <select name="bulk_status_value" class="select">
                <option value="">Сменить статус на...</option>
                <?php foreach ($statusLabels as $key => $label) { ?>
                <option value="<?=$key?>"><?=Format::htmlchars($label)?></option>
                <?php } ?>
            </select>
            <button type="submit" name="bulk_status" value="1" class="btn-secondary btn-sm" id="inv-bulk-action">Применить</button>
            <button type="submit" name="bulk_delete" value="1" class="btn-danger btn-sm inv-delete-btn" id="inv-bulk-action">Удалить</button>
        </div>
    </div>
    <div class="px-6 py-3 flex flex-wrap items-center gap-2 text-sm justify-end">
        <?=$pageNav->getPageLinks()?>
    </div>
</div>
<?php } ?>
</form>
