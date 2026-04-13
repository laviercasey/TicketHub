<?php
if (!defined('OSTAJAXINC')) die('Доступ запрещён');

require_once(INCLUDE_DIR.'class.apimetrics.php');

class ApimetricsAjaxAPI {

    function summary($req) {
        global $thisuser;

        if (!$thisuser || !$thisuser->isadmin()) {
            Http::response(403, 'Доступ запрещён');
            return;
        }

        $hours = isset($req['hours']) ? (int)$req['hours'] : 24;
        if (!in_array($hours, array(1, 6, 24, 168))) {
            $hours = 24;
        }

        $m = new ApiMetrics();
        $data = $m->getSummary($hours);

        header('Content-Type: application/json');
        return json_encode($data);
    }

    function realtime($req) {
        global $thisuser;

        if (!$thisuser || !$thisuser->isadmin()) {
            Http::response(403, 'Доступ запрещён');
            return;
        }

        $hours = isset($req['hours']) ? (int)$req['hours'] : 24;
        $m = new ApiMetrics();
        $data = $m->getRealtimeStats($hours);

        header('Content-Type: application/json');
        return json_encode($data);
    }

    function health($req) {
        global $thisuser;

        if (!$thisuser || !$thisuser->isadmin()) {
            Http::response(403, 'Доступ запрещён');
            return;
        }

        $m = new ApiMetrics();
        $data = $m->healthCheck();

        header('Content-Type: application/json');
        return json_encode($data);
    }

    function alerts($req) {
        global $thisuser;

        if (!$thisuser || !$thisuser->isadmin()) {
            Http::response(403, 'Доступ запрещён');
            return;
        }

        $m = new ApiMetrics();
        $data = $m->checkAlerts();

        header('Content-Type: application/json');
        return json_encode($data);
    }

    function cleanup($req) {
        global $thisuser;

        if (!$thisuser || !$thisuser->isadmin()) {
            Http::response(403, 'Доступ запрещён');
            return;
        }

        $days = isset($req['days']) ? (int)$req['days'] : 30;
        $m = new ApiMetrics();
        $result = $m->cleanupOldLogs($days);

        header('Content-Type: application/json');
        return json_encode($result);
    }
}
?>
