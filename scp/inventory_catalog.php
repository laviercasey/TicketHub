<?php
require('staff.inc.php');
require_once(INCLUDE_DIR . 'class.inventory.php');
require_once(INCLUDE_DIR . 'class.inventorylocation.php');
require_once(INCLUDE_DIR . 'class.inventorycatalog.php');

$tab = isset($_REQUEST['t']) ? $_REQUEST['t'] : 'categories';
$errors = array();
$msg = '';

if ($_POST):
    if (!Misc::validateCSRFToken($_POST['csrf_token'])) {
        $errors['err'] = 'Ошибка проверки безопасности. Попробуйте снова.';
    } else {
    $action = strtolower($_POST['a']);

    if ($action == 'add_category') {
        $data = array(
            'category_name' => $_POST['category_name'],
            'parent_id' => $_POST['parent_id'],
            'description' => $_POST['description'],
            'icon' => $_POST['icon'],
            'sort_order' => $_POST['sort_order']
        );
        if (InventoryCategory::create($data, $errors))
            $msg = 'Категория создана';
        $tab = 'categories';
    } elseif ($action == 'update_category') {
        $data = array(
            'category_name' => $_POST['category_name'],
            'parent_id' => $_POST['parent_id'],
            'description' => $_POST['description'],
            'icon' => $_POST['icon'],
            'sort_order' => $_POST['sort_order'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        );
        if (InventoryCategory::update($_POST['id'], $data, $errors))
            $msg = 'Категория обновлена';
        $tab = 'categories';
    } elseif ($action == 'delete_category') {
        if (InventoryCategory::delete($_POST['id']))
            $msg = 'Категория удалена';
        else
            $errors['err'] = 'Ошибка удаления категории';
        $tab = 'categories';

    } elseif ($action == 'add_brand') {
        $data = array('brand_name' => $_POST['brand_name']);
        if (InventoryBrand::create($data, $errors))
            $msg = 'Бренд создан';
        $tab = 'brands';
    } elseif ($action == 'update_brand') {
        $data = array(
            'brand_name' => $_POST['brand_name'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        );
        if (InventoryBrand::update($_POST['id'], $data, $errors))
            $msg = 'Бренд обновлён';
        $tab = 'brands';
    } elseif ($action == 'delete_brand') {
        if (InventoryBrand::delete($_POST['id']))
            $msg = 'Бренд удалён';
        else
            $errors['err'] = 'Ошибка удаления бренда';
        $tab = 'brands';

    } elseif ($action == 'add_model') {
        $data = array(
            'model_name' => $_POST['model_name'],
            'brand_id' => $_POST['brand_id'],
            'category_id' => $_POST['category_id']
        );
        if (InventoryModel::create($data, $errors))
            $msg = 'Модель создана';
        $tab = 'models';
    } elseif ($action == 'update_model') {
        $data = array(
            'model_name' => $_POST['model_name'],
            'brand_id' => $_POST['brand_id'],
            'category_id' => $_POST['category_id'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        );
        if (InventoryModel::update($_POST['id'], $data, $errors))
            $msg = 'Модель обновлена';
        $tab = 'models';
    } elseif ($action == 'delete_model') {
        if (InventoryModel::delete($_POST['id']))
            $msg = 'Модель удалена';
        else
            $errors['err'] = 'Ошибка удаления модели';
        $tab = 'models';
    }

    }

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
require_once(STAFFINC_DIR . 'inventory-catalog.inc.php');
require_once(STAFFINC_DIR . 'footer.inc.php');
?>
