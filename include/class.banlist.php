<?php
class Banlist {
    
    static function add($email,$submitter='') {
        $sql='INSERT IGNORE INTO '.BANLIST_TABLE.' SET added=NOW(),email='.db_input($email).',submitter='.db_input($submitter);
        return (db_query($sql) && ($id=db_insert_id()))?$id:0;
    }
    
    static function remove($email) {
        $sql='DELETE FROM '.BANLIST_TABLE.' WHERE email='.db_input($email);
        return (db_query($sql) && db_affected_rows())?true:false;
    }
    
    static function isbanned($email) {
        return db_num_rows(db_query('SELECT id FROM '.BANLIST_TABLE.' WHERE email='.db_input($email)))?true:false;
    }
}
