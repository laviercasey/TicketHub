<?php
require('staff.inc.php');
require_once(INCLUDE_DIR . 'class.inventory.php');
require_once(INCLUDE_DIR . 'class.inventorylocation.php');
require_once(INCLUDE_DIR . 'class.inventorycatalog.php');

$page = '';
$item = null;
$errors = array();
$msg = '';
$reqAction = isset($_REQUEST['a']) ? $_REQUEST['a'] : '';

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : (isset($_POST['id']) ? $_POST['id'] : 0);
if ($id && is_numeric($id)) {
    $item = InventoryItem::lookup($id);
    if (!$item || !is_object($item) || !$item->getId())
        $errors['err'] = 'Запись не найдена #' . $id;
    elseif ($reqAction == 'edit')
        $page = 'inventory-item.inc.php';
    elseif ($reqAction != 'add')
        $page = 'inventory-view.inc.php';
}

if ($_POST):
    if (!Misc::validateCSRFToken($_POST['csrf_token'])) {
        $errors['err'] = 'Ошибка проверки безопасности. Попробуйте снова.';
    } else {
    switch (strtolower($_POST['a'])):
    case 'add':
    case 'update':
        $data = array(
            'inventory_number' => $_POST['inventory_number'],
            'category_id' => $_POST['category_id'],
            'brand_id' => $_POST['brand_id'],
            'model_id' => $_POST['model_id'],
            'custom_model' => $_POST['custom_model'],
            'serial_number' => $_POST['serial_number'],
            'part_number' => $_POST['part_number'],
            'location_id' => $_POST['location_id'],
            'assigned_staff_id' => $_POST['assigned_staff_id'],
            'assignment_type' => $_POST['assignment_type'],
            'status' => $_POST['status'],
            'purchase_date' => $_POST['purchase_date'],
            'warranty_until' => $_POST['warranty_until'],
            'cost' => $_POST['cost'],
            'description' => $_POST['description'],
            'created_by' => $thisuser->getId(),
            'updated_by' => $thisuser->getId()
        );

        if (!$errors) {
            if ($_POST['a'] == 'add') {
                $newId = InventoryItem::create($data, $errors);
                if ($newId) {
                    $msg = 'Техника успешно добавлена';
                    $item = InventoryItem::lookup($newId);
                    $page = 'inventory-view.inc.php';
                }
            } elseif ($_POST['a'] == 'update' && $_POST['id']) {
                if (InventoryItem::update($_POST['id'], $data, $errors)) {
                    $msg = 'Запись успешно обновлена';
                    $item = InventoryItem::lookup($_POST['id']);
                    $page = 'inventory-view.inc.php';
                }
            }
        }

        if ($errors && !$errors['err'])
            $errors['err'] = 'Исправьте ошибки и попробуйте снова';

        if ($errors && $item && $item->getId())
            $page = 'inventory-item.inc.php';
        break;

    case 'delete':
        if ($_POST['id'] && is_numeric($_POST['id'])) {
            if (InventoryItem::delete($_POST['id'])) {
                $msg = 'Запись удалена';
                $page = '';
                $item = null;
            } else {
                $errors['err'] = 'Ошибка удаления';
            }
        }
        break;

    case 'move':
        if ($_POST['id'] && $_POST['location_id']) {
            $itm = InventoryItem::lookup($_POST['id']);
            if ($itm && $itm->moveTo($_POST['location_id'], $thisuser->getId())) {
                $msg = 'Техника перемещена';
                $item = InventoryItem::lookup($_POST['id']);
                $page = 'inventory-view.inc.php';
            }
        }
        break;

    case 'change_status':
        if ($_POST['id'] && $_POST['status']) {
            $itm = InventoryItem::lookup($_POST['id']);
            if ($itm && $itm->changeStatus($_POST['status'], $thisuser->getId())) {
                $msg = 'Статус изменён';
                $item = InventoryItem::lookup($_POST['id']);
                $page = 'inventory-view.inc.php';
            }
        }
        break;

    case 'assign':
        if ($_POST['id']) {
            $itm = InventoryItem::lookup($_POST['id']);
            if ($itm && $itm->assignTo($_POST['assigned_staff_id'], $_POST['assignment_type'], $thisuser->getId())) {
                $msg = 'Закрепление изменено';
                $item = InventoryItem::lookup($_POST['id']);
                $page = 'inventory-view.inc.php';
            }
        }
        break;

    case 'process':
        if (!$_POST['items'] || !is_array($_POST['items'])) {
            $errors['err'] = 'Выберите хотя бы одну запись';
        } else {
            $ids = implode(',', array_map('intval', $_POST['items']));
            $selected = count($_POST['items']);

            if (isset($_POST['bulk_delete'])) {
                $count = 0;
                foreach ($_POST['items'] as $iid) {
                    if (InventoryItem::delete(intval($iid))) $count++;
                }
                $msg = "$count из $selected записей удалено";
            } elseif (isset($_POST['bulk_status']) && $_POST['bulk_status_value']) {
                $sql = 'UPDATE ' . INVENTORY_ITEMS_TABLE . ' SET status=' . db_input($_POST['bulk_status_value'])
                     . ', updated=' . db_input(date('Y-m-d H:i:s'))
                     . ' WHERE item_id IN(' . $ids . ')';
                db_query($sql);
                $affected = db_affected_rows();
                foreach ($_POST['items'] as $iid) {
                    InventoryItem::logHistory(intval($iid), 'status_changed', '', $_POST['bulk_status_value'], $thisuser->getId());
                }
                $msg = "$affected из $selected записей обновлено";
            }

            if (!$msg)
                $errors['err'] = 'Ошибка выполнения';
        }
        break;

    default:
        $errors['err'] = 'Неизвестное действие';
    endswitch;
    }
endif;

if (!$page && $reqAction == 'add' && !isset($newId))
    $page = 'inventory-item.inc.php';

$inc = $page ? $page : 'inventory.inc.php';

$nav->setTabActive('inventory');
$nav->addSubMenu(array('desc' => 'Вся техника', 'href' => 'inventory.php', 'iconclass' => 'inventoryAll'));
$nav->addSubMenu(array('desc' => 'Локации', 'href' => 'inventory_locations.php', 'iconclass' => 'inventoryLocations'));
$nav->addSubMenu(array('desc' => 'Справочники', 'href' => 'inventory_catalog.php', 'iconclass' => 'inventoryCatalog'));
$nav->addSubMenu(array('desc' => 'История', 'href' => 'inventory.php?view=history', 'iconclass' => 'inventoryHistory'));
$nav->addSubMenu(array('desc' => 'Экспорт CSV', 'href' => 'inventory_export.php', 'iconclass' => 'inventoryExport'));

$reqView = isset($_REQUEST['view']) ? $_REQUEST['view'] : '';
if ($reqView == 'history' && !$page)
    $inc = 'inventory-history.inc.php';

require_once(STAFFINC_DIR . 'header.inc.php');
require_once(STAFFINC_DIR . $inc);
require_once(STAFFINC_DIR . 'footer.inc.php');
?>
