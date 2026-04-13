<?php
require('staff.inc.php');
require_once(INCLUDE_DIR . 'class.inventory.php');
require_once(INCLUDE_DIR . 'class.inventorylocation.php');
require_once(INCLUDE_DIR . 'class.inventorycatalog.php');

$errors = array();
$msg = '';

if ($_POST):
    if (!Misc::validateCSRFToken($_POST['csrf_token'])) {
        $errors['err'] = 'Ошибка проверки безопасности. Попробуйте снова.';
    } else {
    $action = strtolower($_POST['a']);

    if ($action == 'add_location') {
        $data = array(
            'location_name' => $_POST['location_name'],
            'parent_id' => $_POST['parent_id'],
            'location_type' => $_POST['location_type'],
            'description' => $_POST['description'],
            'sort_order' => $_POST['sort_order']
        );
        if (InventoryLocation::create($data, $errors))
            $msg = 'Локация создана';
    } elseif ($action == 'update_location') {
        $data = array(
            'location_name' => $_POST['location_name'],
            'parent_id' => $_POST['parent_id'],
            'location_type' => $_POST['location_type'],
            'description' => $_POST['description'],
            'sort_order' => $_POST['sort_order'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        );
        if (InventoryLocation::update($_POST['id'], $data, $errors))
            $msg = 'Локация обновлена';
    } elseif ($action == 'delete_location') {
        if (InventoryLocation::delete($_POST['id']))
            $msg = 'Локация удалена';
        else
            $errors['err'] = 'Ошибка удаления локации';
    }

    } // end CSRF else

    if ($errors && !$errors['err'])
        $errors['err'] = 'Исправьте ошибки и попробуйте снова';
endif;

$nav->setTabActive('inventory');
$nav->addSubMenu(array('desc' => 'Вся техника', 'href' => 'inventory.php', 'iconclass' => 'inventoryAll'));
$nav->addSubMenu(array('desc' => 'Локации', 'href' => 'inventory_locations.php', 'iconclass' => 'inventoryLocations'));
$nav->addSubMenu(array('desc' => 'Справочники', 'href' => 'inventory_catalog.php', 'iconclass' => 'inventoryCatalog'));
$nav->addSubMenu(array('desc' => 'История', 'href' => 'inventory.php?view=history', 'iconclass' => 'inventoryHistory'));
$nav->addSubMenu(array('desc' => 'Экспорт CSV', 'href' => 'inventory_export.php', 'iconclass' => 'inventoryExport'));

require_once(STAFFINC_DIR . 'header.inc.php');
require_once(STAFFINC_DIR . 'inventory-locations.inc.php');
require_once(STAFFINC_DIR . 'footer.inc.php');
?>
