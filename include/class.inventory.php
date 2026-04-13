<?php
class InventoryItem {

    public $id;
    public $row;

    public function __construct($id) {
        $this->id = 0;
        if ($id && ($info = $this->getInfoById($id))) {
            $this->row = $info;
            $this->id = $info['item_id'];
        }
    }

    function getInfoById($id) {
        $sql = 'SELECT i.*, '
             . ' c.category_name, b.brand_name, m.model_name,'
             . ' l.location_name,'
             . ' CONCAT(s.firstname," ",s.lastname) as staff_name,'
             . ' CONCAT(cb.firstname," ",cb.lastname) as created_by_name'
             . ' FROM ' . INVENTORY_ITEMS_TABLE . ' i'
             . ' LEFT JOIN ' . INVENTORY_CATEGORIES_TABLE . ' c ON c.category_id=i.category_id'
             . ' LEFT JOIN ' . INVENTORY_BRANDS_TABLE . ' b ON b.brand_id=i.brand_id'
             . ' LEFT JOIN ' . INVENTORY_MODELS_TABLE . ' m ON m.model_id=i.model_id'
             . ' LEFT JOIN ' . LOCATIONS_TABLE . ' l ON l.location_id=i.location_id'
             . ' LEFT JOIN ' . STAFF_TABLE . ' s ON s.staff_id=i.assigned_staff_id'
             . ' LEFT JOIN ' . STAFF_TABLE . ' cb ON cb.staff_id=i.created_by'
             . ' WHERE i.item_id=' . db_input($id);
        $res = db_query($sql);
        if ($res && ($row = db_fetch_array($res)))
            return $row;
        return false;
    }

    static function lookup($id) {
        $obj = new InventoryItem($id);
        return $obj->getId() ? $obj : null;
    }

    function getId() { return $this->id; }
    function getInventoryNumber() { return $this->row['inventory_number']; }
    function getSerialNumber() { return $this->row['serial_number']; }
    function getPartNumber() { return $this->row['part_number']; }
    function getCategoryId() { return $this->row['category_id']; }
    function getCategoryName() { return $this->row['category_name']; }
    function getBrandId() { return $this->row['brand_id']; }
    function getBrandName() { return $this->row['brand_name']; }
    function getModelId() { return $this->row['model_id']; }
    function getModelName() { return $this->row['model_name']; }
    function getCustomModel() { return $this->row['custom_model']; }
    function getLocationId() { return $this->row['location_id']; }
    function getLocationName() { return $this->row['location_name']; }
    function getAssignedStaffId() { return $this->row['assigned_staff_id']; }
    function getAssignedStaffName() { return $this->row['staff_name']; }
    function getCreatedById() { return $this->row['created_by']; }
    function getCreatedByName() { return $this->row['created_by_name']; }
    function getStatus() { return $this->row['status']; }
    function getAssignmentType() { return $this->row['assignment_type']; }
    function getPurchaseDate() { return $this->row['purchase_date']; }
    function getWarrantyUntil() { return $this->row['warranty_until']; }
    function getCost() { return $this->row['cost']; }
    function getDescription() { return $this->row['description']; }
    function getCreated() { return $this->row['created']; }
    function getUpdated() { return $this->row['updated']; }

    function getModelDisplay() {
        if ($this->row['model_name'])
            return $this->row['model_name'];
        if ($this->row['custom_model'])
            return $this->row['custom_model'];
        return '';
    }

    function getLocation() {
        if ($this->row['location_id'])
            return InventoryLocation::lookup($this->row['location_id']);
        return null;
    }

    static function getStatusLabels() {
        return array(
            'active' => 'Активно',
            'in_repair' => 'В ремонте',
            'reserved' => 'Резерв',
            'decommissioned' => 'Списано',
            'written_off' => 'Утилизировано'
        );
    }

    function getStatusLabel() {
        $labels = InventoryItem::getStatusLabels();
        return isset($labels[$this->row['status']]) ? $labels[$this->row['status']] : $this->row['status'];
    }

    static function getAssignmentLabels() {
        return array(
            'workplace' => 'Рабочее место',
            'remote' => 'Удалённо',
            'storage' => 'Склад',
            'repair' => 'Ремонт',
            'decommissioned' => 'Списано'
        );
    }

    function getAssignmentLabel() {
        $labels = InventoryItem::getAssignmentLabels();
        return isset($labels[$this->row['assignment_type']]) ? $labels[$this->row['assignment_type']] : $this->row['assignment_type'];
    }

    function getHistory() {
        $history = array();
        $sql = 'SELECT h.*, CONCAT(s.firstname," ",s.lastname) as staff_name'
             . ' FROM ' . INVENTORY_HISTORY_TABLE . ' h'
             . ' LEFT JOIN ' . STAFF_TABLE . ' s ON s.staff_id=h.staff_id'
             . ' WHERE h.item_id=' . db_input($this->id)
             . ' ORDER BY h.created DESC';
        $res = db_query($sql);
        if ($res) {
            while ($row = db_fetch_array($res))
                $history[] = $row;
        }
        return $history;
    }

    static function logHistory($item_id, $action, $old_value, $new_value, $staff_id) {
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO ' . INVENTORY_HISTORY_TABLE . ' SET'
             . ' item_id=' . db_input($item_id)
             . ', action=' . db_input($action)
             . ', old_value=' . db_input($old_value)
             . ', new_value=' . db_input($new_value)
             . ', staff_id=' . db_input($staff_id)
             . ', created=' . db_input($now);
        return db_query($sql);
    }

    static function create($data, &$errors) {
        if (!$data['category_id'])
            $errors['category_id'] = 'Выберите категорию';

        if ($data['inventory_number']) {
            $chk = db_query('SELECT item_id FROM ' . INVENTORY_ITEMS_TABLE
                          . ' WHERE inventory_number=' . db_input($data['inventory_number']));
            if ($chk && db_num_rows($chk))
                $errors['inventory_number'] = 'Инвентарный номер уже существует';
        }

        if ($errors) return 0;

        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO ' . INVENTORY_ITEMS_TABLE . ' SET'
             . ' inventory_number=' . ($data['inventory_number'] ? db_input(Format::striptags($data['inventory_number'])) : 'NULL')
             . ', category_id=' . ($data['category_id'] ? db_input($data['category_id']) : 'NULL')
             . ', brand_id=' . ($data['brand_id'] ? db_input($data['brand_id']) : 'NULL')
             . ', model_id=' . ($data['model_id'] ? db_input($data['model_id']) : 'NULL')
             . ', custom_model=' . ($data['custom_model'] ? db_input(Format::striptags($data['custom_model'])) : 'NULL')
             . ', serial_number=' . ($data['serial_number'] ? db_input(Format::striptags($data['serial_number'])) : 'NULL')
             . ', part_number=' . ($data['part_number'] ? db_input(Format::striptags($data['part_number'])) : 'NULL')
             . ', location_id=' . ($data['location_id'] ? db_input($data['location_id']) : 'NULL')
             . ', assigned_staff_id=' . ($data['assigned_staff_id'] ? db_input($data['assigned_staff_id']) : 'NULL')
             . ', assignment_type=' . db_input($data['assignment_type'] ? $data['assignment_type'] : 'workplace')
             . ', status=' . db_input($data['status'] ? $data['status'] : 'active')
             . ', purchase_date=' . ($data['purchase_date'] ? db_input($data['purchase_date']) : 'NULL')
             . ', warranty_until=' . ($data['warranty_until'] ? db_input($data['warranty_until']) : 'NULL')
             . ', cost=' . ($data['cost'] ? db_input($data['cost']) : 'NULL')
             . ', description=' . db_input(Format::striptags($data['description']))
             . ', created_by=' . db_input($data['created_by'])
             . ', created=' . db_input($now)
             . ', updated=' . db_input($now);

        if (db_query($sql) && ($id = db_insert_id())) {
            InventoryItem::logHistory($id, 'created', '', 'Создано', $data['created_by']);
            return $id;
        }
        $errors['err'] = 'Ошибка создания записи';
        return 0;
    }

    static function update($id, $data, &$errors) {
        $item = InventoryItem::lookup($id);
        if (!$item) {
            $errors['err'] = 'Запись не найдена';
            return false;
        }

        if (!$data['category_id'])
            $errors['category_id'] = 'Выберите категорию';

        if ($data['inventory_number']) {
            $chk = db_query('SELECT item_id FROM ' . INVENTORY_ITEMS_TABLE
                          . ' WHERE inventory_number=' . db_input($data['inventory_number'])
                          . ' AND item_id!=' . db_input($id));
            if ($chk && db_num_rows($chk))
                $errors['inventory_number'] = 'Инвентарный номер уже существует';
        }

        if ($errors) return false;

        $changes = array();
        if ($item->getLocationId() != $data['location_id'])
            $changes[] = array('moved', $item->getLocationName(), '');
        if ($item->getAssignedStaffId() != $data['assigned_staff_id'])
            $changes[] = array('assigned', $item->getAssignedStaffName(), '');
        if ($item->getStatus() != $data['status'])
            $changes[] = array('status_changed', $item->getStatus(), $data['status']);

        $now = date('Y-m-d H:i:s');
        $sql = 'UPDATE ' . INVENTORY_ITEMS_TABLE . ' SET'
             . ' inventory_number=' . ($data['inventory_number'] ? db_input(Format::striptags($data['inventory_number'])) : 'NULL')
             . ', category_id=' . ($data['category_id'] ? db_input($data['category_id']) : 'NULL')
             . ', brand_id=' . ($data['brand_id'] ? db_input($data['brand_id']) : 'NULL')
             . ', model_id=' . ($data['model_id'] ? db_input($data['model_id']) : 'NULL')
             . ', custom_model=' . ($data['custom_model'] ? db_input(Format::striptags($data['custom_model'])) : 'NULL')
             . ', serial_number=' . ($data['serial_number'] ? db_input(Format::striptags($data['serial_number'])) : 'NULL')
             . ', part_number=' . ($data['part_number'] ? db_input(Format::striptags($data['part_number'])) : 'NULL')
             . ', location_id=' . ($data['location_id'] ? db_input($data['location_id']) : 'NULL')
             . ', assigned_staff_id=' . ($data['assigned_staff_id'] ? db_input($data['assigned_staff_id']) : 'NULL')
             . ', assignment_type=' . db_input($data['assignment_type'] ? $data['assignment_type'] : 'workplace')
             . ', status=' . db_input($data['status'] ? $data['status'] : 'active')
             . ', purchase_date=' . ($data['purchase_date'] ? db_input($data['purchase_date']) : 'NULL')
             . ', warranty_until=' . ($data['warranty_until'] ? db_input($data['warranty_until']) : 'NULL')
             . ', cost=' . ($data['cost'] ? db_input($data['cost']) : 'NULL')
             . ', description=' . db_input(Format::striptags($data['description']))
             . ', updated=' . db_input($now)
             . ' WHERE item_id=' . db_input($id);

        if (db_query($sql)) {
            $staff_id = $data['updated_by'] ? $data['updated_by'] : $data['created_by'];
            if ($changes) {
                foreach ($changes as $ch) {
                    InventoryItem::logHistory($id, $ch[0], $ch[1], $ch[2], $staff_id);
                }
            } else {
                InventoryItem::logHistory($id, 'edited', '', 'Обновлено', $staff_id);
            }
            return true;
        }
        $errors['err'] = 'Ошибка обновления';
        return false;
    }

    static function delete($id) {
        db_query('DELETE FROM ' . INVENTORY_HISTORY_TABLE . ' WHERE item_id=' . db_input($id));
        return db_query('DELETE FROM ' . INVENTORY_ITEMS_TABLE . ' WHERE item_id=' . db_input($id));
    }

    function moveTo($location_id, $staff_id) {
        $oldLoc = $this->getLocationName();
        $sql = 'UPDATE ' . INVENTORY_ITEMS_TABLE . ' SET'
             . ' location_id=' . ($location_id ? db_input($location_id) : 'NULL')
             . ', updated=' . db_input(date('Y-m-d H:i:s'))
             . ' WHERE item_id=' . db_input($this->id);
        if (db_query($sql)) {
            $newLoc = '';
            if ($location_id) {
                $loc = InventoryLocation::lookup($location_id);
                if ($loc) $newLoc = $loc->getName();
            }
            InventoryItem::logHistory($this->id, 'moved', $oldLoc, $newLoc, $staff_id);
            return true;
        }
        return false;
    }

    function changeStatus($status, $staff_id) {
        $old = $this->getStatus();
        $sql = 'UPDATE ' . INVENTORY_ITEMS_TABLE . ' SET'
             . ' status=' . db_input($status)
             . ', updated=' . db_input(date('Y-m-d H:i:s'))
             . ' WHERE item_id=' . db_input($this->id);
        if (db_query($sql)) {
            InventoryItem::logHistory($this->id, 'status_changed', $old, $status, $staff_id);
            return true;
        }
        return false;
    }

    function assignTo($staff_id, $type, $acting_staff_id) {
        $oldStaff = $this->getAssignedStaffName();
        $sql = 'UPDATE ' . INVENTORY_ITEMS_TABLE . ' SET'
             . ' assigned_staff_id=' . ($staff_id ? db_input($staff_id) : 'NULL')
             . ', assignment_type=' . db_input($type ? $type : 'workplace')
             . ', updated=' . db_input(date('Y-m-d H:i:s'))
             . ' WHERE item_id=' . db_input($this->id);
        if (db_query($sql)) {
            $newStaff = '';
            if ($staff_id) {
                $sres = db_query('SELECT CONCAT(firstname," ",lastname) as name FROM ' . STAFF_TABLE . ' WHERE staff_id=' . db_input($staff_id));
                if ($sres && ($srow = db_fetch_array($sres)))
                    $newStaff = $srow['name'];
            }
            InventoryItem::logHistory($this->id, 'assigned', $oldStaff, $newStaff, $acting_staff_id);
            return true;
        }
        return false;
    }

    static function getActionLabels() {
        return array(
            'created' => 'Создано',
            'moved' => 'Перемещено',
            'assigned' => 'Назначено',
            'status_changed' => 'Статус изменён',
            'edited' => 'Редактировано',
            'decommissioned' => 'Списано'
        );
    }
}
?>
