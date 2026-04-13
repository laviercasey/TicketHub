<?php
require('staff.inc.php');
require_once(INCLUDE_DIR . 'class.inventory.php');
require_once(INCLUDE_DIR . 'class.inventorylocation.php');
require_once(INCLUDE_DIR . 'class.inventorycatalog.php');

$filter_category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$filter_location = isset($_GET['location']) ? intval($_GET['location']) : 0;
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_staff = isset($_GET['staff']) ? intval($_GET['staff']) : 0;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

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

if ($filter_category) {
    $qwhere .= ' AND i.category_id=' . db_input($filter_category);
}
if ($filter_location) {
    $locIds = array($filter_location);
    $childIds = InventoryLocation::getAllIds($filter_location);
    foreach ($childIds as $cid) $locIds[] = $cid;
    $qwhere .= ' AND i.location_id IN(' . implode(',', array_map('intval', $locIds)) . ')';
}
if ($filter_status) {
    $qwhere .= ' AND i.status=' . db_input($filter_status);
}
if ($filter_staff) {
    $qwhere .= ' AND i.assigned_staff_id=' . db_input($filter_staff);
}
if ($search) {
    $qwhere .= ' AND MATCH(i.inventory_number, i.serial_number, i.part_number, i.description) AGAINST(' . db_input($search) . ')';
}

$query = $qselect . $qfrom . $qwhere . ' ORDER BY i.created DESC LIMIT 50000';
$result = db_query($query);

$statusLabels = InventoryItem::getStatusLabels();
$assignLabels = InventoryItem::getAssignmentLabels();

$filename = 'inventory_export_' . date('Ymd') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

$csvHeaders = array(
    'Инв. номер', 'Категория', 'Бренд', 'Модель', 'Серийный номер',
    'Парт-номер', 'Локация', 'За кем', 'Тип закрепления', 'Статус',
    'Стоимость', 'Дата покупки', 'Гарантия до', 'Описание'
);
fputcsv($out, $csvHeaders, ';');

if ($result && db_num_rows($result)) {
    while ($row = db_fetch_array($result)) {
        $model = $row['model_name'] ? $row['model_name'] : $row['custom_model'];
        $statusLabel = isset($statusLabels[$row['status']]) ? $statusLabels[$row['status']] : $row['status'];
        $assignLabel = isset($assignLabels[$row['assignment_type']]) ? $assignLabels[$row['assignment_type']] : $row['assignment_type'];

        fputcsv($out, array(
            $row['inventory_number'],
            $row['category_name'],
            $row['brand_name'],
            $model,
            $row['serial_number'],
            $row['part_number'],
            $row['location_name'],
            $row['staff_name'],
            $assignLabel,
            $statusLabel,
            $row['cost'] ? number_format($row['cost'], 2, '.', '') : '',
            $row['purchase_date'] ? date('d.m.Y', strtotime($row['purchase_date'])) : '',
            $row['warranty_until'] ? date('d.m.Y', strtotime($row['warranty_until'])) : '',
            $row['description']
        ), ';');
    }
}

fclose($out);
exit;
?>
