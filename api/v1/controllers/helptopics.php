<?php

if (!defined('THAPIV1INC')) die('Access Denied');

require_once(INCLUDE_DIR.'class.apicontroller.php');

class HelptopicsController extends ApiController {

    function index() {
        $this->checkMethod(array('GET'));
        $this->requirePermission('tickets:read');

        $pagination = $this->getPaginationParams();

        $where_parts = array();

        $is_active = $this->getQuery('is_active');
        if ($is_active !== null) {
            $where_parts[] = 'isactive=' . db_input($is_active ? 1 : 0);
        }

        $dept_id = $this->getQuery('dept_id');
        if ($dept_id) {
            $where_parts[] = 'dept_id=' . db_input($dept_id);
        }

        $search = $this->getQuery('search');
        if ($search) {
            $search_term = db_real_escape(str_replace(array('%','_'), array('\\%','\\_'), $search));
            $where_parts[] = "topic LIKE '%{$search_term}%'";
        }

        $where = empty($where_parts) ? '' : ' WHERE ' . implode(' AND ', $where_parts);

        $count_sql = 'SELECT COUNT(*) as total FROM ' . TOPIC_TABLE . $where;
        $count_result = db_query($count_sql);
        $count_row = db_fetch_array($count_result);
        $total = $count_row['total'];

        $sql = 'SELECT
                    topic.*,
                    dept.dept_name,
                    pri.priority_desc
                FROM ' . TOPIC_TABLE . ' topic
                LEFT JOIN ' . DEPT_TABLE . ' dept ON topic.dept_id = dept.dept_id
                LEFT JOIN ' . TICKET_PRIORITY_TABLE . ' pri ON topic.priority_id = pri.priority_id'
                . $where
                . ' ORDER BY topic.topic ASC'
                . $this->buildLimitClause();

        $result = db_query($sql);

        if (!$result) {
            $this->handleDbError('fetch help topics');
        }

        $topics = array();
        while ($row = db_fetch_array($result)) {
            $topics[] = array(
                'topic_id' => (int)$row['topic_id'],
                'name' => $row['topic'],
                'is_active' => $this->formatBool($row['isactive']),
                'department' => array(
                    'id' => (int)$row['dept_id'],
                    'name' => $row['dept_name']
                ),
                'priority' => array(
                    'id' => (int)$row['priority_id'],
                    'name' => $row['priority_desc']
                ),
                'auto_response' => !$this->formatBool($row['noautoresp']),
                'created_at' => $this->formatDate($row['created']),
                'updated_at' => $this->formatDate($row['updated'])
            );
        }

        $this->paginated($topics, $total)->send();
    }

    function show() {
        $this->checkMethod(array('GET'));
        $this->requirePermission('tickets:read');

        $topic_id = $this->getPathParam('id');

        if (!$topic_id || !is_numeric($topic_id)) {
            ApiResponse::badRequest('Invalid help topic ID')->send();
        }

        $sql = 'SELECT
                    topic.*,
                    dept.dept_name,
                    pri.priority_desc
                FROM ' . TOPIC_TABLE . ' topic
                LEFT JOIN ' . DEPT_TABLE . ' dept ON topic.dept_id = dept.dept_id
                LEFT JOIN ' . TICKET_PRIORITY_TABLE . ' pri ON topic.priority_id = pri.priority_id
                WHERE topic.topic_id=' . db_input($topic_id);

        $result = db_query($sql);

        if (!$result || !db_num_rows($result)) {
            ApiResponse::notFound('Help topic not found')->send();
        }

        $row = db_fetch_array($result);

        $tickets_sql = 'SELECT
                            COUNT(*) as total,
                            COUNT(CASE WHEN status="open" THEN 1 END) as open
                        FROM ' . TICKET_TABLE . '
                        WHERE topic_id=' . db_input($topic_id);

        $tickets_result = db_query($tickets_sql);
        $tickets_row = db_fetch_array($tickets_result);

        $data = array(
            'topic_id' => (int)$row['topic_id'],
            'name' => $row['topic'],
            'is_active' => $this->formatBool($row['isactive']),
            'department' => array(
                'id' => (int)$row['dept_id'],
                'name' => $row['dept_name']
            ),
            'priority' => array(
                'id' => (int)$row['priority_id'],
                'name' => $row['priority_desc']
            ),
            'auto_response' => !$this->formatBool($row['noautoresp']),
            'tickets_count' => array(
                'total' => (int)$tickets_row['total'],
                'open' => (int)$tickets_row['open']
            ),
            'created_at' => $this->formatDate($row['created']),
            'updated_at' => $this->formatDate($row['updated'])
        );

        ApiResponse::success($data)->send();
    }
}

?>
