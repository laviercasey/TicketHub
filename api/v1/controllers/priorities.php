<?php

if (!defined('THAPIV1INC')) die('Access Denied');

require_once(INCLUDE_DIR.'class.apicontroller.php');

class PrioritiesController extends ApiController {

    function index() {
        $this->checkMethod(array('GET'));
        $this->requirePermission('tickets:read');

        $sql = 'SELECT * FROM ' . TICKET_PRIORITY_TABLE . ' ORDER BY priority_urgency DESC';

        $result = db_query($sql);

        if (!$result) {
            $this->handleDbError('fetch priorities');
        }

        $priorities = array();
        while ($row = db_fetch_array($result)) {
            $priorities[] = array(
                'priority_id' => (int)$row['priority_id'],
                'name' => $row['priority_desc'],
                'urgency' => (int)$row['priority_urgency'],
                'color' => $row['priority_color']
            );
        }

        ApiResponse::success($priorities)->send();
    }
}

?>
