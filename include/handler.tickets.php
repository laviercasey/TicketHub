<?php
if(!defined('OSTAJAXINC') || !defined('INCLUDE_DIR')) die('403');

include_once(INCLUDE_DIR.'class.ticket.php');

class TicketsAjaxAPI{

    function searchbyemail($params) {

        $inputEscaped = str_replace(array('%','_'),array('\\%','\\_'),db_real_escape(strtolower($params['input'])));
        $limit = isset($params['limit']) ? (int) $params['limit']:25;
        $items=array();
        $sql='SELECT DISTINCT email,name FROM '.TICKET_TABLE.' WHERE email LIKE '.db_input($inputEscaped.'%').' ORDER BY created LIMIT '.$limit;
        $resp=db_query($sql);
        if($resp && db_num_rows($resp)){
            while(list($email,$name)=db_fetch_row($resp)) {
                $name=(strpos($name,'@')===false)?$name:'';
                $items[] = array('id' => $email, 'value' => $email, 'info' => $name);
            }
        }
        return json_encode(array('results' => $items), JSON_UNESCAPED_UNICODE);
    }

    function search($params) {

        $inputEscaped = str_replace(array('%','_'),array('\\%','\\_'),db_real_escape(strtolower($params['input'])));
        $limit = isset($params['limit']) ? (int) $params['limit']:25;
        $items=array();
        $ticketid=false;
        if(is_numeric($params['input'])) {
            $WHERE=' WHERE ticketID LIKE '.db_input($inputEscaped.'%');
            $ticketid=true;
        }else{
            $WHERE=' WHERE email LIKE '.db_input($inputEscaped.'%');
        }
        $sql='SELECT DISTINCT ticketID,email FROM '.TICKET_TABLE.' '.$WHERE.' ORDER BY created LIMIT '.$limit;
        $resp=db_query($sql);
        if($resp && db_num_rows($resp)){
            while(list($id,$email)=db_fetch_row($resp)) {
                $info=($ticketid)?$email:$id;
                $id=($ticketid)?$id:$email;
                $items[] = array('id' => $id, 'value' => $id, 'info' => $info);
            }
        }
        return json_encode(array('results' => $items), JSON_UNESCAPED_UNICODE);
    }

    function acquireLock($params) {
        global $cfg,$thisuser;

        if(!$params['tid'] or !is_numeric($params['tid']))
            return json_encode(array('id' => 0, 'retry' => false));

        $ticket = new Ticket($params['tid']);

        if(!$ticket || (!$thisuser->canAccessDept($ticket->getDeptId()) && ($ticket->isAssigned() && $thisuser->getId()!=$ticket->getStaffId())))
             return json_encode(array('id' => 0, 'retry' => false));

        if($ticket->isLocked() && ($lock=$ticket->getLock()) && !$lock->isExpired()) {

            if($lock->getStaffId()!=$thisuser->getId())
                return json_encode(array('id' => 0, 'retry' => false));

            $lock->renew();

            return json_encode(array('id' => (int)$lock->getId(), 'time' => (int)$lock->getTime()));
        }


        if(($lock=$ticket->acquireLock()))
            return json_encode(array('id' => (int)$lock->getId(), 'time' => (int)$lock->getTime()));

        return json_encode(array('id' => 0, 'retry' => true));
    }

    function renewLock($params) {
        global $thisuser;

        if(!$params['id'] or !is_numeric($params['id']))
            return json_encode(array('id' => 0, 'retry' => true));

        $lock= new TicketLock($params['id']);

        if(!$lock->load() || !$lock->getStaffId() || $lock->isExpired())
            return TicketsAjaxAPI::acquireLock($params);

        if($lock->getStaffId()!=$thisuser->getId())
            return json_encode(array('id' => 0, 'retry' => false));

        $lock->renew();

        return json_encode(array('id' => (int)$lock->getId(), 'time' => (int)$lock->getTime()));
    }

    function releaseLock($params) {
        global $thisuser;

        if($params['id'] && is_numeric($params['id'])){

            $lock= new TicketLock($params['id']);
            if(!$lock->load() || !$lock->getStaffId() || $lock->isExpired())
                return json_encode(array('ok' => 1));

            return json_encode(array('ok' => ($lock->getStaffId()==$thisuser->getId() && $lock->release()) ? 1 : 0));

        }elseif($params['tid']){
            return json_encode(array('ok' => TicketLock::removeStaffLocks($thisuser->getId(),$params['tid']) ? 1 : 0));
        }

        return json_encode(array('ok' => 0));
    }
}
?>
