<?php
class InventoryCategory {

    public $id;
    public $row;

    public function __construct($id) {
        $this->id = 0;
        $sql = 'SELECT * FROM ' . INVENTORY_CATEGORIES_TABLE . ' WHERE category_id=' . db_input($id);
        $res = db_query($sql);
        if ($res && ($row = db_fetch_array($res))) {
            $this->row = $row;
            $this->id = $row['category_id'];
        }
    }

    function getId() { return $this->id; }
    function getName() { return $this->row['category_name']; }
    function getParentId() { return $this->row['parent_id']; }
    function getDescription() { return $this->row['description']; }
    function getIcon() { return $this->row['icon']; }
    function getSortOrder() { return $this->row['sort_order']; }
    function isActive() { return $this->row['is_active'] ? true : false; }

    static function lookup($id) {
        $obj = new InventoryCategory($id);
        return $obj->getId() ? $obj : null;
    }

    static function getAll($active_only = true) {
        $sql = 'SELECT * FROM ' . INVENTORY_CATEGORIES_TABLE;
        if ($active_only) $sql .= ' WHERE is_active=1';
        $sql .= ' ORDER BY sort_order, category_name';
        $items = array();
        $res = db_query($sql);
        if ($res) {
            while ($row = db_fetch_array($res))
                $items[] = $row;
        }
        return $items;
    }

    static function getTree($parent_id = 0, $depth = 0, $active_only = true) {
        $tree = array();
        $parentCond = $parent_id ? 'parent_id=' . db_input($parent_id) : '(parent_id IS NULL OR parent_id=0)';
        $sql = 'SELECT * FROM ' . INVENTORY_CATEGORIES_TABLE . ' WHERE ' . $parentCond;
        if ($active_only) $sql .= ' AND is_active=1';
        $sql .= ' ORDER BY sort_order, category_name';
        $res = db_query($sql);
        if ($res) {
            while ($row = db_fetch_array($res)) {
                $row['depth'] = $depth;
                $tree[] = $row;
                $children = InventoryCategory::getTree($row['category_id'], $depth + 1, $active_only);
                foreach ($children as $child) {
                    $tree[] = $child;
                }
            }
        }
        return $tree;
    }

    static function create($data, &$errors) {
        if (!$data['category_name']) {
            $errors['category_name'] = 'Название категории обязательно';
            return 0;
        }
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO ' . INVENTORY_CATEGORIES_TABLE . ' SET'
             . ' category_name=' . db_input(Format::striptags($data['category_name']))
             . ', parent_id=' . ($data['parent_id'] ? db_input($data['parent_id']) : 'NULL')
             . ', description=' . db_input(Format::striptags($data['description']))
             . ', icon=' . db_input($data['icon'] ? $data['icon'] : 'desktop')
             . ', sort_order=' . intval($data['sort_order'])
             . ', is_active=1'
             . ', created=' . db_input($now)
             . ', updated=' . db_input($now);
        if (db_query($sql) && ($id = db_insert_id()))
            return $id;
        $errors['err'] = 'Ошибка создания категории';
        return 0;
    }

    static function update($id, $data, &$errors) {
        if (!$data['category_name']) {
            $errors['category_name'] = 'Название категории обязательно';
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $sql = 'UPDATE ' . INVENTORY_CATEGORIES_TABLE . ' SET'
             . ' category_name=' . db_input(Format::striptags($data['category_name']))
             . ', parent_id=' . ($data['parent_id'] ? db_input($data['parent_id']) : 'NULL')
             . ', description=' . db_input(Format::striptags($data['description']))
             . ', icon=' . db_input($data['icon'] ? $data['icon'] : 'desktop')
             . ', sort_order=' . intval($data['sort_order'])
             . ', is_active=' . intval($data['is_active'])
             . ', updated=' . db_input($now)
             . ' WHERE category_id=' . db_input($id);
        return db_query($sql) ? true : false;
    }

    static function delete($id) {
        $cat = InventoryCategory::lookup($id);
        if (!$cat) return false;
        $parentId = $cat->getParentId() ? $cat->getParentId() : 'NULL';
        db_query('UPDATE ' . INVENTORY_CATEGORIES_TABLE . ' SET parent_id=' . ($parentId === 'NULL' ? 'NULL' : db_input($parentId))
               . ' WHERE parent_id=' . db_input($id));
        db_query('UPDATE ' . INVENTORY_ITEMS_TABLE . ' SET category_id=NULL WHERE category_id=' . db_input($id));
        return db_query('DELETE FROM ' . INVENTORY_CATEGORIES_TABLE . ' WHERE category_id=' . db_input($id));
    }
}


class InventoryBrand {

    public $id;
    public $row;

    public function __construct($id) {
        $this->id = 0;
        $sql = 'SELECT * FROM ' . INVENTORY_BRANDS_TABLE . ' WHERE brand_id=' . db_input($id);
        $res = db_query($sql);
        if ($res && ($row = db_fetch_array($res))) {
            $this->row = $row;
            $this->id = $row['brand_id'];
        }
    }

    function getId() { return $this->id; }
    function getName() { return $this->row['brand_name']; }
    function isActive() { return $this->row['is_active'] ? true : false; }

    static function lookup($id) {
        $obj = new InventoryBrand($id);
        return $obj->getId() ? $obj : null;
    }

    static function getAll($active_only = true) {
        $sql = 'SELECT * FROM ' . INVENTORY_BRANDS_TABLE;
        if ($active_only) $sql .= ' WHERE is_active=1';
        $sql .= ' ORDER BY brand_name';
        $items = array();
        $res = db_query($sql);
        if ($res) {
            while ($row = db_fetch_array($res))
                $items[] = $row;
        }
        return $items;
    }

    static function create($data, &$errors) {
        if (!$data['brand_name']) {
            $errors['brand_name'] = 'Название бренда обязательно';
            return 0;
        }
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO ' . INVENTORY_BRANDS_TABLE . ' SET'
             . ' brand_name=' . db_input(Format::striptags($data['brand_name']))
             . ', is_active=1'
             . ', created=' . db_input($now);
        if (db_query($sql) && ($id = db_insert_id()))
            return $id;
        $errors['err'] = 'Ошибка создания бренда';
        return 0;
    }

    static function update($id, $data, &$errors) {
        if (!$data['brand_name']) {
            $errors['brand_name'] = 'Название бренда обязательно';
            return false;
        }
        $sql = 'UPDATE ' . INVENTORY_BRANDS_TABLE . ' SET'
             . ' brand_name=' . db_input(Format::striptags($data['brand_name']))
             . ', is_active=' . intval($data['is_active'])
             . ' WHERE brand_id=' . db_input($id);
        return db_query($sql) ? true : false;
    }

    static function delete($id) {
        db_query('UPDATE ' . INVENTORY_MODELS_TABLE . ' SET brand_id=0 WHERE brand_id=' . db_input($id));
        db_query('UPDATE ' . INVENTORY_ITEMS_TABLE . ' SET brand_id=NULL WHERE brand_id=' . db_input($id));
        return db_query('DELETE FROM ' . INVENTORY_BRANDS_TABLE . ' WHERE brand_id=' . db_input($id));
    }
}


class InventoryModel {

    public $id;
    public $row;

    public function __construct($id) {
        $this->id = 0;
        $sql = 'SELECT m.*, b.brand_name FROM ' . INVENTORY_MODELS_TABLE . ' m'
             . ' LEFT JOIN ' . INVENTORY_BRANDS_TABLE . ' b ON b.brand_id=m.brand_id'
             . ' WHERE m.model_id=' . db_input($id);
        $res = db_query($sql);
        if ($res && ($row = db_fetch_array($res))) {
            $this->row = $row;
            $this->id = $row['model_id'];
        }
    }

    function getId() { return $this->id; }
    function getName() { return $this->row['model_name']; }
    function getBrandId() { return $this->row['brand_id']; }
    function getBrandName() { return $this->row['brand_name']; }
    function getCategoryId() { return $this->row['category_id']; }
    function isActive() { return $this->row['is_active'] ? true : false; }

    static function lookup($id) {
        $obj = new InventoryModel($id);
        return $obj->getId() ? $obj : null;
    }

    static function getAll($active_only = true) {
        $sql = 'SELECT m.*, b.brand_name FROM ' . INVENTORY_MODELS_TABLE . ' m'
             . ' LEFT JOIN ' . INVENTORY_BRANDS_TABLE . ' b ON b.brand_id=m.brand_id';
        if ($active_only) $sql .= ' WHERE m.is_active=1';
        $sql .= ' ORDER BY b.brand_name, m.model_name';
        $items = array();
        $res = db_query($sql);
        if ($res) {
            while ($row = db_fetch_array($res))
                $items[] = $row;
        }
        return $items;
    }

    static function getByBrand($brand_id) {
        $sql = 'SELECT * FROM ' . INVENTORY_MODELS_TABLE
             . ' WHERE brand_id=' . db_input($brand_id)
             . ' AND is_active=1'
             . ' ORDER BY model_name';
        $items = array();
        $res = db_query($sql);
        if ($res) {
            while ($row = db_fetch_array($res))
                $items[] = $row;
        }
        return $items;
    }

    static function create($data, &$errors) {
        if (!$data['model_name']) {
            $errors['model_name'] = 'Название модели обязательно';
            return 0;
        }
        if (!$data['brand_id']) {
            $errors['brand_id'] = 'Бренд обязателен';
            return 0;
        }
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO ' . INVENTORY_MODELS_TABLE . ' SET'
             . ' model_name=' . db_input(Format::striptags($data['model_name']))
             . ', brand_id=' . db_input($data['brand_id'])
             . ', category_id=' . ($data['category_id'] ? db_input($data['category_id']) : 'NULL')
             . ', is_active=1'
             . ', created=' . db_input($now);
        if (db_query($sql) && ($id = db_insert_id()))
            return $id;
        $errors['err'] = 'Ошибка создания модели';
        return 0;
    }

    static function update($id, $data, &$errors) {
        if (!$data['model_name']) {
            $errors['model_name'] = 'Название модели обязательно';
            return false;
        }
        $sql = 'UPDATE ' . INVENTORY_MODELS_TABLE . ' SET'
             . ' model_name=' . db_input(Format::striptags($data['model_name']))
             . ', brand_id=' . db_input($data['brand_id'])
             . ', category_id=' . ($data['category_id'] ? db_input($data['category_id']) : 'NULL')
             . ', is_active=' . intval($data['is_active'])
             . ' WHERE model_id=' . db_input($id);
        return db_query($sql) ? true : false;
    }

    static function delete($id) {
        db_query('UPDATE ' . INVENTORY_ITEMS_TABLE . ' SET model_id=NULL WHERE model_id=' . db_input($id));
        return db_query('DELETE FROM ' . INVENTORY_MODELS_TABLE . ' WHERE model_id=' . db_input($id));
    }
}
?>
