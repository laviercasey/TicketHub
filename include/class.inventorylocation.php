<?php
class InventoryLocation {

    public $id;
    public $row;

    public function __construct($id) {
        $this->id = 0;
        if ($id && ($info = $this->getInfoById($id))) {
            $this->row = $info;
            $this->id = $info['location_id'];
        }
    }

    function getInfoById($id) {
        $sql = 'SELECT * FROM ' . LOCATIONS_TABLE . ' WHERE location_id=' . db_input($id);
        $res = db_query($sql);
        if ($res && ($row = db_fetch_array($res)))
            return $row;
        return false;
    }

    function getId() { return $this->id; }
    function getName() { return $this->row['location_name']; }
    function getParentId() { return $this->row['parent_id']; }
    function getLocationType() { return $this->row['location_type']; }
    function getDescription() { return $this->row['description']; }
    function getSortOrder() { return $this->row['sort_order']; }
    function isActive() { return $this->row['is_active'] ? true : false; }
    function getCreated() { return $this->row['created']; }
    function getUpdated() { return $this->row['updated']; }

    static function lookup($id) {
        $obj = new InventoryLocation($id);
        return $obj->getId() ? $obj : null;
    }

    function getParent() {
        if ($this->row['parent_id'])
            return InventoryLocation::lookup($this->row['parent_id']);
        return null;
    }

    function getChildren() {
        $children = array();
        $sql = 'SELECT location_id FROM ' . LOCATIONS_TABLE
             . ' WHERE parent_id=' . db_input($this->id)
             . ' ORDER BY sort_order, location_name';
        $res = db_query($sql);
        if ($res) {
            while ($row = db_fetch_array($res)) {
                $children[] = InventoryLocation::lookup($row['location_id']);
            }
        }
        return $children;
    }

    function getBreadcrumb() {
        $parts = array();
        $current = $this;
        while ($current) {
            $parts[] = $current->getName();
            $current = $current->getParent();
        }
        return implode(' > ', array_reverse($parts));
    }

    static function getAllIds($parent_id) {
        $ids = array();
        $sql = 'SELECT location_id FROM ' . LOCATIONS_TABLE
             . ' WHERE parent_id=' . db_input($parent_id);
        $res = db_query($sql);
        if ($res) {
            while ($row = db_fetch_array($res)) {
                $ids[] = $row['location_id'];
                $childIds = InventoryLocation::getAllIds($row['location_id']);
                foreach ($childIds as $cid) {
                    $ids[] = $cid;
                }
            }
        }
        return $ids;
    }

    static function getTree($parent_id = 0, $depth = 0) {
        $tree = array();
        $parentCond = $parent_id ? 'parent_id=' . db_input($parent_id) : '(parent_id IS NULL OR parent_id=0)';
        $sql = 'SELECT * FROM ' . LOCATIONS_TABLE
             . ' WHERE ' . $parentCond
             . ' AND is_active=1'
             . ' ORDER BY sort_order, location_name';
        $res = db_query($sql);
        if ($res) {
            while ($row = db_fetch_array($res)) {
                $row['depth'] = $depth;
                $tree[] = $row;
                $children = InventoryLocation::getTree($row['location_id'], $depth + 1);
                foreach ($children as $child) {
                    $tree[] = $child;
                }
            }
        }
        return $tree;
    }

    static function getFullTree($parent_id = 0, $depth = 0) {
        $tree = array();
        $parentCond = $parent_id ? 'parent_id=' . db_input($parent_id) : '(parent_id IS NULL OR parent_id=0)';
        $sql = 'SELECT * FROM ' . LOCATIONS_TABLE
             . ' WHERE ' . $parentCond
             . ' ORDER BY sort_order, location_name';
        $res = db_query($sql);
        if ($res) {
            while ($row = db_fetch_array($res)) {
                $row['depth'] = $depth;
                $tree[] = $row;
                $children = InventoryLocation::getFullTree($row['location_id'], $depth + 1);
                foreach ($children as $child) {
                    $tree[] = $child;
                }
            }
        }
        return $tree;
    }

    function getItemCount($recursive = true) {
        $ids = array($this->id);
        if ($recursive) {
            $childIds = InventoryLocation::getAllIds($this->id);
            foreach ($childIds as $cid) {
                $ids[] = $cid;
            }
        }
        $sql = 'SELECT COUNT(*) as cnt FROM ' . INVENTORY_ITEMS_TABLE
             . ' WHERE location_id IN (' . implode(',', array_map('intval', $ids)) . ')';
        $res = db_query($sql);
        if ($res && ($row = db_fetch_array($res)))
            return intval($row['cnt']);
        return 0;
    }

    static function create($data, &$errors) {
        if (!$data['location_name']) {
            $errors['location_name'] = 'Название обязательно';
            return 0;
        }
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO ' . LOCATIONS_TABLE . ' SET'
             . ' location_name=' . db_input(Format::striptags($data['location_name']))
             . ', parent_id=' . ($data['parent_id'] ? db_input($data['parent_id']) : 'NULL')
             . ', location_type=' . db_input($data['location_type'] ? $data['location_type'] : 'room')
             . ', description=' . db_input(Format::striptags($data['description']))
             . ', sort_order=' . intval($data['sort_order'])
             . ', is_active=' . (isset($data['is_active']) ? intval($data['is_active']) : 1)
             . ', created=' . db_input($now)
             . ', updated=' . db_input($now);
        if (db_query($sql) && ($id = db_insert_id()))
            return $id;
        $errors['err'] = 'Ошибка создания локации';
        return 0;
    }

    static function update($id, $data, &$errors) {
        if (!$data['location_name']) {
            $errors['location_name'] = 'Название обязательно';
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $sql = 'UPDATE ' . LOCATIONS_TABLE . ' SET'
             . ' location_name=' . db_input(Format::striptags($data['location_name']))
             . ', parent_id=' . ($data['parent_id'] ? db_input($data['parent_id']) : 'NULL')
             . ', location_type=' . db_input($data['location_type'] ? $data['location_type'] : 'room')
             . ', description=' . db_input(Format::striptags($data['description']))
             . ', sort_order=' . intval($data['sort_order'])
             . ', is_active=' . intval($data['is_active'])
             . ', updated=' . db_input($now)
             . ' WHERE location_id=' . db_input($id);
        return db_query($sql) ? true : false;
    }

    static function delete($id) {
        $loc = InventoryLocation::lookup($id);
        if (!$loc) return false;

        $parentId = $loc->getParentId() ? $loc->getParentId() : 'NULL';
        db_query('UPDATE ' . LOCATIONS_TABLE . ' SET parent_id=' . ($parentId === 'NULL' ? 'NULL' : db_input($parentId))
               . ' WHERE parent_id=' . db_input($id));

        db_query('UPDATE ' . INVENTORY_ITEMS_TABLE . ' SET location_id=NULL WHERE location_id=' . db_input($id));

        return db_query('DELETE FROM ' . LOCATIONS_TABLE . ' WHERE location_id=' . db_input($id));
    }

    static function getTypeLabel($type) {
        $labels = array(
            'building' => 'Здание',
            'floor' => 'Этаж',
            'room' => 'Кабинет',
            'storage' => 'Склад',
            'rack' => 'Стеллаж',
            'other' => 'Другое'
        );
        return isset($labels[$type]) ? $labels[$type] : $type;
    }

    static function getTypeIcon($type) {
        $icons = array(
            'building' => 'building',
            'floor' => 'bars',
            'room' => 'home',
            'storage' => 'archive',
            'rack' => 'th',
            'other' => 'map-marker'
        );
        return isset($icons[$type]) ? $icons[$type] : 'map-marker';
    }

    static function getTypes() {
        return array(
            'building' => 'Здание',
            'floor' => 'Этаж',
            'room' => 'Кабинет',
            'storage' => 'Склад',
            'rack' => 'Стеллаж',
            'other' => 'Другое'
        );
    }
}
?>
