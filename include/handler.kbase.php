<?php
if(!defined('OSTAJAXINC') || !defined('INCLUDE_DIR')) die('!');
	    
class KbaseAjaxAPI{
    
    function cannedResp($params) {
       
	    $sql='SELECT answer FROM '.KB_PREMADE_TABLE.' WHERE isenabled=1 AND premade_id='.db_input($params['id']);
	    if(($res=db_query($sql)) && db_num_rows($res))
		    list($response)=db_fetch_row($res);

        if($response && $params['tid'] && strpos($response,'%')!==false) {
            include_once(INCLUDE_DIR.'class.ticket.php');

            $ticket = new Ticket($params['tid']);
            if($ticket && $ticket->getId()){
                $response=$ticket->replaceTemplateVars($response);
            }
        }

        return $response;
	}
}
?>
