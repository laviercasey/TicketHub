<?php
class TicketLock {
    public $id;
    public $staff_id;
    public $created;
    public $expire;
    public $expiretime;

    public function __construct($id,$load=true){
        $this->id=$id;
        if($load) $this->load();
    }

    function load() {

        if(!$this->id)
            return false;

        $sql='SELECT *,TIME_TO_SEC(TIMEDIFF(expire,NOW())) as timeleft FROM '.TICKET_LOCK_TABLE.' WHERE lock_id='.db_input($this->id);
        if(($res=db_query($sql)) && db_num_rows($res)) {
            $info=db_fetch_array($res);
            $this->id=$info['lock_id'];
            $this->staff_id=$info['staff_id'];
            $this->created=$info['created'];
            $this->expire=$info['expire'];
            $this->expiretime=time()+$info['timeleft'];
            return true;
        }
        $this->id=0;
        return false;
    }

    function reload() {
        return $this->load();
    }

    static function acquire($ticketId,$staffId) {
        global $cfg;

        if(!$ticketId or !$staffId or !$cfg->getLockTime())
            return 0;

        db_query('DELETE FROM '.TICKET_LOCK_TABLE.' WHERE ticket_id='.db_input($ticketId).' AND expire<NOW()');
        $sql='INSERT IGNORE INTO '.TICKET_LOCK_TABLE.' SET created=NOW() '.
            ',ticket_id='.db_input($ticketId).
            ',staff_id='.db_input($staffId).
            ',expire=DATE_ADD(NOW(),INTERVAL '.$cfg->getLockTime().' MINUTE) ';

        return db_query($sql)?db_insert_id():0;
    }

    function renew() {
        global $cfg;

        $sql='UPDATE '.TICKET_LOCK_TABLE.' SET expire=DATE_ADD(NOW(),INTERVAL '.$cfg->getLockTime().' MINUTE) '.
            ' WHERE lock_id='.db_input($this->getId());
        if(db_query($sql) && db_affected_rows()) {
            $this->reload();
            return true;
        }
        return false;
    }

    function release(){

        $sql='DELETE FROM '.TICKET_LOCK_TABLE.' WHERE lock_id='.db_input($this->getId());
        return (db_query($sql) && db_affected_rows())?true:false;
    }

    function getId(){
        return $this->id;
    }

    function getStaffId(){
        return $this->staff_id;
    }

    function getCreateTime() {
        return $this->created;
    }

    function getExpireTime() {
        return $this->expire;
    }

    function getTime() {
        return $this->isExpired()?0:($this->expiretime-time());
    }

    function isExpired(){
        return (time()>$this->expiretime)?true:false;
    }

    static function removeStaffLocks($staffId,$ticketId=0) {
        $sql='DELETE FROM '.TICKET_LOCK_TABLE.' WHERE staff_id='.db_input($staffId);
        if($ticketId)
            $sql.=' AND ticket_id='.db_input($ticketId);

        return db_query($sql)?true:false;
    }

    static function cleanup() {
        db_query('DELETE FROM '.TICKET_LOCK_TABLE.' WHERE expire<NOW()');
        @db_query('OPTIMIZE TABLE '.TICKET_LOCK_TABLE);
    }
}
?>
