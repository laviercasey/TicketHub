<?php
if (!defined('OSTAJAXINC') || !$thisuser || !$thisuser->isStaff())
    die('Доступ запрещён');

require_once(INCLUDE_DIR . 'class.inventory.php');
require_once(INCLUDE_DIR . 'class.inventorylocation.php');
require_once(INCLUDE_DIR . 'class.inventorycatalog.php');

class InventoryAjaxAPI {

    static function getModels($params) {
        $brand_id = isset($params['brand_id']) ? intval($params['brand_id']) : 0;
        if (!$brand_id) return json_encode(array());

        $models = InventoryModel::getByBrand($brand_id);
        $result = array();
        foreach ($models as $m) {
            $result[] = array(
                'model_id' => intval($m['model_id']),
                'model_name' => $m['model_name']
            );
        }
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    static function getLocationChildren($params) {
        $parent_id = isset($params['parent_id']) ? intval($params['parent_id']) : 0;
        $sql = 'SELECT * FROM ' . LOCATIONS_TABLE
             . ' WHERE parent_id=' . db_input($parent_id)
             . ' AND is_active=1'
             . ' ORDER BY sort_order, location_name';
        $res = db_query($sql);
        $result = array();
        if ($res) {
            while ($row = db_fetch_array($res)) {
                $result[] = array(
                    'location_id' => intval($row['location_id']),
                    'location_name' => $row['location_name'],
                    'location_type' => $row['location_type']
                );
            }
        }
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    static function getLocationTree($params) {
        $tree = InventoryLocation::getTree();
        $result = array();
        foreach ($tree as $loc) {
            $result[] = array(
                'location_id' => intval($loc['location_id']),
                'location_name' => $loc['location_name'],
                'depth' => intval($loc['depth']),
                'location_type' => $loc['location_type']
            );
        }
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    static function quickSearch($params) {
        $q = isset($params['q']) ? trim($params['q']) : '';
        if (strlen($q) < 2) return json_encode(array());

        $search = db_input('%' . $q . '%');
        $sql = 'SELECT i.item_id, i.inventory_number, i.serial_number, '
             . 'c.category_name, b.brand_name, m.model_name, i.custom_model'
             . ' FROM ' . INVENTORY_ITEMS_TABLE . ' i'
             . ' LEFT JOIN ' . INVENTORY_CATEGORIES_TABLE . ' c ON c.category_id=i.category_id'
             . ' LEFT JOIN ' . INVENTORY_BRANDS_TABLE . ' b ON b.brand_id=i.brand_id'
             . ' LEFT JOIN ' . INVENTORY_MODELS_TABLE . ' m ON m.model_id=i.model_id'
             . ' WHERE i.inventory_number LIKE ' . $search
             . ' OR i.serial_number LIKE ' . $search
             . ' OR i.part_number LIKE ' . $search
             . ' LIMIT 10';
        $res = db_query($sql);
        $result = array();
        if ($res) {
            while ($row = db_fetch_array($res)) {
                $model = $row['model_name'] ? $row['model_name'] : $row['custom_model'];
                $label = $row['inventory_number'] . ' - ' . $row['brand_name'] . ' ' . $model;
                $result[] = array(
                    'item_id' => intval($row['item_id']),
                    'label' => $label
                );
            }
        }
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    static function addBrand($params) {
        $errors = array();
        $data = array('brand_name' => isset($params['brand_name']) ? $params['brand_name'] : '');
        $id = InventoryBrand::create($data, $errors);
        if ($id) {
            return json_encode(array('brand_id' => $id, 'brand_name' => $data['brand_name']), JSON_UNESCAPED_UNICODE);
        }
        $err = $errors['brand_name'] ? $errors['brand_name'] : 'Ошибка';
        return json_encode(array('error' => $err), JSON_UNESCAPED_UNICODE);
    }

    static function addModel($params) {
        $errors = array();
        $data = array(
            'model_name' => isset($params['model_name']) ? $params['model_name'] : '',
            'brand_id' => isset($params['brand_id']) ? intval($params['brand_id']) : 0
        );
        $id = InventoryModel::create($data, $errors);
        if ($id) {
            return json_encode(array('model_id' => $id, 'model_name' => $data['model_name']), JSON_UNESCAPED_UNICODE);
        }
        $err = $errors['model_name'] ? $errors['model_name'] : ($errors['brand_id'] ? $errors['brand_id'] : 'Ошибка');
        return json_encode(array('error' => $err), JSON_UNESCAPED_UNICODE);
    }

    static function moveItem($params) {
        global $thisuser;
        $item_id = isset($params['item_id']) ? intval($params['item_id']) : 0;
        $location_id = isset($params['location_id']) ? intval($params['location_id']) : 0;
        $item = InventoryItem::lookup($item_id);
        if (!$item) return json_encode(array('error' => 'Запись не найдена'), JSON_UNESCAPED_UNICODE);
        if ($item->moveTo($location_id, $thisuser->getId())) {
            return json_encode(array('success' => true));
        }
        return json_encode(array('error' => 'Ошибка перемещения'), JSON_UNESCAPED_UNICODE);
    }

    static function changeStatus($params) {
        global $thisuser;
        $item_id = isset($params['item_id']) ? intval($params['item_id']) : 0;
        $status = isset($params['status']) ? $params['status'] : '';
        $item = InventoryItem::lookup($item_id);
        if (!$item) return json_encode(array('error' => 'Запись не найдена'), JSON_UNESCAPED_UNICODE);
        if ($item->changeStatus($status, $thisuser->getId())) {
            return json_encode(array('success' => true));
        }
        return json_encode(array('error' => 'Ошибка'), JSON_UNESCAPED_UNICODE);
    }
}
?>
