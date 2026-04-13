<?php
class PriorityUser {

    var $id;
    var $ht;

    function __construct($id) {
        $this->id = 0;
        $this->ht = array();
        $this->load($id);
    }

    function load($id = 0) {
        if (!$id && !($id = $this->id))
            return false;

        $sql = 'SELECT * FROM ' . PRIORITY_USERS_TABLE
             . ' WHERE id=' . db_input($id);

        if (!($res = db_query($sql)) || !db_num_rows($res))
            return false;

        $this->ht = db_fetch_array($res);
        $this->id = $this->ht['id'];

        return true;
    }

    function getId() {
        return $this->id;
    }

    function getEmail() {
        return $this->ht['email'];
    }

    function getDescription() {
        return $this->ht['description'];
    }

    function isActive() {
        return ($this->ht['is_active'] == 1);
    }

    function getCreated() {
        return $this->ht['created'];
    }

    function getUpdated() {
        return $this->ht['updated'];
    }

    function update($vars, &$errors) {

        if (!$vars['email'] || !Validator::is_email($vars['email'])) {
            $errors['email'] = 'Введите корректный email адрес';
        }

        if (!$errors) {
            $sql = sprintf(
                'SELECT id FROM %s WHERE email=%s AND id != %d',
                PRIORITY_USERS_TABLE,
                db_input($vars['email']),
                db_input($this->id)
            );
            $res = db_query($sql);
            if ($res && db_num_rows($res)) {
                $errors['email'] = 'Этот email уже добавлен в приоритетные пользователи';
            }
        }

        if ($errors)
            return false;

        $sql = sprintf(
            'UPDATE %s SET email=%s, description=%s, is_active=%d, updated=NOW() WHERE id=%d',
            PRIORITY_USERS_TABLE,
            db_input($vars['email']),
            db_input($vars['description'] ? $vars['description'] : ''),
            isset($vars['is_active']) ? 1 : 0,
            db_input($this->id)
        );

        return db_query($sql);
    }


    static function create($vars, &$errors) {

        if (!$vars['email'] || !Validator::is_email($vars['email'])) {
            $errors['email'] = 'Введите корректный email адрес';
        }

        if (!$errors) {
            $sql = sprintf(
                'SELECT id FROM %s WHERE email=%s',
                PRIORITY_USERS_TABLE,
                db_input($vars['email'])
            );
            $res = db_query($sql);
            if ($res && db_num_rows($res)) {
                $errors['email'] = 'Этот email уже добавлен в приоритетные пользователи';
            }
        }

        if ($errors)
            return false;

        $sql = sprintf(
            'INSERT INTO %s SET email=%s, description=%s, is_active=%d, created=NOW(), updated=NOW()',
            PRIORITY_USERS_TABLE,
            db_input($vars['email']),
            db_input($vars['description'] ? $vars['description'] : ''),
            isset($vars['is_active']) ? 1 : 0
        );

        if (!db_query($sql))
            return false;

        return db_insert_id();
    }

    static function lookup($id) {
        return ($id && ($pu = new PriorityUser($id)) && $pu->getId()) ? $pu : null;
    }

    static function isPriorityEmail($email) {
        if (!$email)
            return false;

        $sql = sprintf(
            'SELECT id FROM %s WHERE email=%s AND is_active=1',
            PRIORITY_USERS_TABLE,
            db_input(strtolower(trim($email)))
        );

        $res = db_query($sql);
        return ($res && db_num_rows($res) > 0);
    }

    static function getActivePriorityEmails() {
        $emails = array();

        $sql = 'SELECT email FROM ' . PRIORITY_USERS_TABLE . ' WHERE is_active=1';
        $res = db_query($sql);

        if ($res) {
            while ($row = db_fetch_array($res)) {
                $emails[] = strtolower($row['email']);
            }
        }

        return $emails;
    }
}
?>
