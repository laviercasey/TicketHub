<?php

if (!defined('THAPIV1INC')) die('Access Denied');

require_once(INCLUDE_DIR.'class.apicontroller.php');
require_once(INCLUDE_DIR.'class.ticket.php');
require_once(INCLUDE_DIR.'class.dept.php');
require_once(INCLUDE_DIR.'class.staff.php');

class TicketsController extends ApiController {

    function index() {
        $this->checkMethod(array('GET'));
        $this->requirePermission('tickets:read');

        $pagination = $this->getPaginationParams();

        $allowed_filters = array(
            'status' => array('column' => 'status', 'operator' => '=', 'type' => 'string'),
            'priority_id' => array('column' => 'priority_id', 'operator' => '=', 'type' => 'int'),
            'dept_id' => array('column' => 'dept_id', 'operator' => '=', 'type' => 'int'),
            'staff_id' => array('column' => 'staff_id', 'operator' => '=', 'type' => 'int'),
            'topic_id' => array('column' => 'topic_id', 'operator' => '=', 'type' => 'int'),
            'email' => array('column' => 'email', 'operator' => '=', 'type' => 'email'),
            'created_from' => array('column' => 'created', 'operator' => '>=', 'type' => 'string'),
            'created_to' => array('column' => 'created', 'operator' => '<=', 'type' => 'string'),
            'is_overdue' => array('column' => 'isoverdue', 'operator' => '=', 'type' => 'int')
        );

        $where_parts = array();

        foreach ($allowed_filters as $param => $config) {
            $value = $this->getQuery($param);
            if ($value !== null && $value !== '') {
                $column = 'ticket.' . $config['column'];
                $operator = $config['operator'];
                $value = $this->sanitize($value, $config['type']);

                $where_parts[] = $column . ' ' . $operator . ' ' . db_input($value);
            }
        }

        $search = $this->getQuery('search');
        if ($search) {
            $search_clause = $this->buildSearchClause(
                $search,
                array('subject', 'name', 'email'),
                'ticket'
            );
            if ($search_clause) {
                $where_parts[] = $search_clause;
            }
        }

        $where = empty($where_parts) ? '' : ' WHERE ' . implode(' AND ', $where_parts);

        $count_sql = 'SELECT COUNT(*) as total FROM ' . TICKET_TABLE . ' ticket' . $where;
        $count_result = db_query($count_sql);
        $count_row = db_fetch_array($count_result);
        $total = $count_row['total'];

        $allowed_sort_fields = array('created', 'updated', 'priority_id', 'status', 'subject', 'duedate');
        $sort_params = $this->getSortParams($allowed_sort_fields, 'created', 'DESC');

        $sql = 'SELECT
                    ticket.ticket_id,
                    ticket.ticketID as number,
                    ticket.subject,
                    ticket.name,
                    ticket.email,
                    ticket.phone,
                    ticket.status,
                    ticket.source,
                    ticket.ip_address,
                    ticket.priority_id,
                    ticket.dept_id,
                    ticket.topic_id,
                    ticket.staff_id,
                    ticket.isoverdue,
                    ticket.isanswered,
                    ticket.duedate,
                    ticket.created,
                    ticket.updated,
                    ticket.closed,
                    ticket.lastmessage,
                    ticket.lastresponse,
                    dept.dept_name,
                    pri.priority_desc,
                    topic.topic,
                    staff.firstname as staff_firstname,
                    staff.lastname as staff_lastname
                FROM ' . TICKET_TABLE . ' ticket
                LEFT JOIN ' . DEPT_TABLE . ' dept ON ticket.dept_id = dept.dept_id
                LEFT JOIN ' . TICKET_PRIORITY_TABLE . ' pri ON ticket.priority_id = pri.priority_id
                LEFT JOIN ' . TOPIC_TABLE . ' topic ON ticket.topic_id = topic.topic_id
                LEFT JOIN ' . STAFF_TABLE . ' staff ON ticket.staff_id = staff.staff_id'
                . $where
                . ' ORDER BY ticket.' . $sort_params['sort'] . ' ' . $sort_params['order']
                . $this->buildLimitClause();

        $result = db_query($sql);

        if (!$result) {
            $this->handleDbError('fetch tickets');
        }

        $tickets = array();
        while ($row = db_fetch_array($result)) {
            $tickets[] = $this->formatTicketListItem($row);
        }

        $this->paginated($tickets, $total)->send();
    }

    function formatTicketListItem($row) {
        return array(
            'ticket_id' => (int)$row['ticket_id'],
            'number' => $row['number'],
            'subject' => $row['subject'],
            'status' => $row['status'],
            'priority' => array(
                'id' => (int)$row['priority_id'],
                'name' => $row['priority_desc']
            ),
            'department' => array(
                'id' => (int)$row['dept_id'],
                'name' => $row['dept_name']
            ),
            'topic' => $row['topic_id'] ? array(
                'id' => (int)$row['topic_id'],
                'name' => $row['topic']
            ) : null,
            'user' => array(
                'name' => $row['name'],
                'email' => $row['email'],
                'phone' => $row['phone']
            ),
            'assigned_staff' => $row['staff_id'] ? array(
                'id' => (int)$row['staff_id'],
                'name' => trim($row['staff_firstname'] . ' ' . $row['staff_lastname'])
            ) : null,
            'source' => $row['source'],
            'is_overdue' => $this->formatBool($row['isoverdue']),
            'is_answered' => $this->formatBool($row['isanswered']),
            'due_date' => $this->formatDate($row['duedate']),
            'created_at' => $this->formatDate($row['created']),
            'updated_at' => $this->formatDate($row['updated']),
            'closed_at' => $this->formatDate($row['closed']),
            'last_message_at' => $this->formatDate($row['lastmessage']),
            'last_response_at' => $this->formatDate($row['lastresponse'])
        );
    }

    function show() {
        $this->checkMethod(array('GET'));
        $this->requirePermission('tickets:read');

        $ticket_id = $this->getPathParam('id');

        if (!$ticket_id || !is_numeric($ticket_id)) {
            ApiResponse::badRequest('Invalid ticket ID')->send();
        }

        $ticket = new Ticket($ticket_id);

        if (!$ticket || !$ticket->getId()) {
            ApiResponse::notFound('Ticket not found')->send();
        }

        $data = $this->formatTicketDetails($ticket);

        ApiResponse::success($data)->send();
    }

    function formatTicketDetails($ticket) {
        $row = $ticket->row;

        $msg_count_sql = 'SELECT COUNT(*) as count FROM ' . TICKET_MESSAGE_TABLE .
                         ' WHERE ticket_id=' . db_input($ticket->getId());
        $msg_count_result = db_query($msg_count_sql);
        $msg_count_row = db_fetch_array($msg_count_result);
        $messages_count = $msg_count_row['count'];

        $note_count_sql = 'SELECT COUNT(*) as count FROM ' . TICKET_NOTE_TABLE .
                          ' WHERE ticket_id=' . db_input($ticket->getId());
        $note_count_result = db_query($note_count_sql);
        $note_count_row = db_fetch_array($note_count_result);
        $notes_count = $note_count_row['count'];

        $att_count_sql = 'SELECT COUNT(*) as count FROM ' . TICKET_ATTACHMENT_TABLE .
                         ' WHERE ticket_id=' . db_input($ticket->getId());
        $att_count_result = db_query($att_count_sql);
        $att_count_row = db_fetch_array($att_count_result);
        $attachments_count = $att_count_row['count'];

        return array(
            'ticket_id' => $ticket->getId(),
            'number' => $ticket->getExtId(),
            'subject' => $ticket->getSubject(),
            'status' => $ticket->getStatus(),
            'priority' => array(
                'id' => $ticket->getPriorityId(),
                'name' => $ticket->getPriority()
            ),
            'department' => array(
                'id' => $ticket->getDeptId(),
                'name' => $ticket->getDeptName()
            ),
            'help_topic' => array(
                'id' => $ticket->getTopicId(),
                'name' => $ticket->getHelpTopic()
            ),
            'user' => array(
                'name' => $ticket->getName(),
                'email' => $ticket->getEmail(),
                'phone' => isset($row['phone']) ? $row['phone'] : null,
                'phone_ext' => isset($row['phone_ext']) ? $row['phone_ext'] : null
            ),
            'assigned_staff' => $ticket->getStaffId() ? array(
                'id' => $ticket->getStaffId(),
                'name' => $ticket->getStaffName()
            ) : null,
            'source' => isset($row['source']) ? $row['source'] : null,
            'ip_address' => isset($row['ip_address']) ? $row['ip_address'] : null,
            'is_overdue' => $ticket->isOverdue(),
            'is_answered' => isset($row['isanswered']) ? $this->formatBool($row['isanswered']) : false,
            'due_date' => $this->formatDate($ticket->getDueDate()),
            'created_at' => $this->formatDate($ticket->getCreateDate()),
            'updated_at' => $this->formatDate($ticket->getUpdateDate()),
            'closed_at' => $this->formatDate($ticket->getCloseDate()),
            'last_message_at' => $this->formatDate($ticket->getLastMessageDate()),
            'last_response_at' => $this->formatDate($ticket->getLastResponseDate()),
            'counts' => array(
                'messages' => (int)$messages_count,
                'notes' => (int)$notes_count,
                'attachments' => (int)$attachments_count
            )
        );
    }

    function create() {
        $this->checkMethod(array('POST'));
        $this->requirePermission('tickets:write');

        $errors = array();

        $required = array('email', 'subject', 'message');
        $this->validateRequired($required, $errors);

        $email = $this->getInput('email');
        if ($email) {
            $this->validateEmail($email, $errors);
        }

        $subject = $this->getInput('subject');
        if ($subject && strlen($subject) < 3) {
            $errors['subject'] = 'Subject must be at least 3 characters';
        }

        $message = $this->getInput('message');
        if ($message && strlen($message) < 10) {
            $errors['message'] = 'Message must be at least 10 characters';
        }

        if ($errors) {
            ApiResponse::validationError($errors)->send();
        }

        $data = array(
            'name' => $this->getInput('name', $email),
            'email' => $email,
            'phone' => $this->getInput('phone'),
            'subject' => $subject,
            'message' => $message,
            'pri' => $this->getInput('priority_id', 2),
            'topicId' => $this->getInput('topic_id'),
            'deptId' => $this->getInput('dept_id'),
            'ip' => $this->getInput('ip_address', $_SERVER['REMOTE_ADDR']),
            'source' => $this->getInput('source', 'API')
        );

        require_once(INCLUDE_DIR.'class.ticket.php');

        $ticket = Ticket::create($data, $errors, 'api');

        if (!$ticket) {
            if ($errors) {
                ApiResponse::validationError($errors, 'Failed to create ticket')->send();
            } else {
                ApiResponse::internalError('Failed to create ticket')->send();
            }
        }

        $response_data = array(
            'ticket_id' => $ticket->getId(),
            'number' => $ticket->getExtId(),
            'subject' => $ticket->getSubject(),
            'status' => $ticket->getStatus(),
            'created_at' => $this->formatDate($ticket->getCreateDate())
        );

        ApiResponse::created($response_data, 'Ticket created successfully')->send();
    }

    function update() {
        $this->checkMethod(array('PUT', 'PATCH'));
        $this->requirePermission('tickets:write');

        $ticket_id = $this->getPathParam('id');

        if (!$ticket_id || !is_numeric($ticket_id)) {
            ApiResponse::badRequest('Invalid ticket ID')->send();
        }

        $ticket = new Ticket($ticket_id);

        if (!$ticket || !$ticket->getId()) {
            ApiResponse::notFound('Ticket not found')->send();
        }

        $errors = array();
        $updated = false;

        $subject = $this->getInput('subject');
        if ($subject !== null) {
            if (strlen($subject) < 3) {
                $errors['subject'] = 'Subject must be at least 3 characters';
            } else {
                $sql = 'UPDATE ' . TICKET_TABLE . ' SET subject=' . db_input($subject) .
                       ', updated=NOW() WHERE ticket_id=' . db_input($ticket_id);
                if (db_query($sql)) {
                    $updated = true;
                }
            }
        }

        $priority_id = $this->getInput('priority_id');
        if ($priority_id !== null) {
            $sql = 'UPDATE ' . TICKET_TABLE . ' SET priority_id=' . db_input($priority_id) .
                   ', updated=NOW() WHERE ticket_id=' . db_input($ticket_id);
            if (db_query($sql)) {
                $updated = true;
            }
        }

        $dept_id = $this->getInput('dept_id');
        if ($dept_id !== null) {
            $sql = 'UPDATE ' . TICKET_TABLE . ' SET dept_id=' . db_input($dept_id) .
                   ', updated=NOW() WHERE ticket_id=' . db_input($ticket_id);
            if (db_query($sql)) {
                $updated = true;
            }
        }

        $staff_id = $this->getInput('staff_id');
        if ($staff_id !== null) {
            $sql = 'UPDATE ' . TICKET_TABLE . ' SET staff_id=' . db_input($staff_id) .
                   ', updated=NOW() WHERE ticket_id=' . db_input($ticket_id);
            if (db_query($sql)) {
                $updated = true;
            }
        }

        $status = $this->getInput('status');
        if ($status !== null) {
            if (!in_array($status, array('open', 'closed'))) {
                $errors['status'] = 'Invalid status. Must be "open" or "closed"';
            } else {
                $sql = 'UPDATE ' . TICKET_TABLE . ' SET status=' . db_input($status) .
                       ', updated=NOW() WHERE ticket_id=' . db_input($ticket_id);
                if (db_query($sql)) {
                    $updated = true;
                }
            }
        }

        if ($errors) {
            ApiResponse::validationError($errors)->send();
        }

        if (!$updated) {
            ApiResponse::badRequest('No fields to update')->send();
        }

        $ticket = new Ticket($ticket_id);
        $data = $this->formatTicketDetails($ticket);

        ApiResponse::success($data, 'Ticket updated successfully')->send();
    }

    function delete() {
        $this->checkMethod(array('DELETE'));
        $this->requirePermission('tickets:delete');

        $ticket_id = $this->getPathParam('id');

        if (!$ticket_id || !is_numeric($ticket_id)) {
            ApiResponse::badRequest('Invalid ticket ID')->send();
        }

        $check_sql = 'SELECT ticket_id, status FROM ' . TICKET_TABLE .
                     ' WHERE ticket_id=' . db_input($ticket_id);
        $check_result = db_query($check_sql);

        if (!$check_result || !db_num_rows($check_result)) {
            ApiResponse::notFound('Ticket not found')->send();
        }

        $row = db_fetch_array($check_result);

        if ($row['status'] == 'closed') {
            ApiResponse::unprocessableEntity('Ticket is already closed')->send();
        }

        $sql = 'UPDATE ' . TICKET_TABLE .
               ' SET status="closed", closed=NOW(), updated=NOW() ' .
               ' WHERE ticket_id=' . db_input($ticket_id);

        if (!db_query($sql)) {
            $this->handleDbError('close ticket');
        }

        ApiResponse::noContent();
    }

    function messages() {
        $this->checkMethod(array('GET'));
        $this->requirePermission('tickets:read');

        $ticket_id = $this->getPathParam('id');

        if (!$ticket_id || !is_numeric($ticket_id)) {
            ApiResponse::badRequest('Invalid ticket ID')->send();
        }

        $ticket = new Ticket($ticket_id);
        if (!$ticket || !$ticket->getId()) {
            ApiResponse::notFound('Ticket not found')->send();
        }

        $sql = 'SELECT * FROM ' . TICKET_MESSAGE_TABLE .
               ' WHERE ticket_id=' . db_input($ticket_id) .
               ' ORDER BY created ASC';

        $result = db_query($sql);

        $messages = array();
        while ($row = db_fetch_array($result)) {
            $messages[] = array(
                'message_id' => (int)$row['msg_id'],
                'message' => $row['message'],
                'source' => $row['source'],
                'ip_address' => $row['ip_address'],
                'created_at' => $this->formatDate($row['created'])
            );
        }

        ApiResponse::success($messages)->send();
    }

    function addMessage() {
        $this->checkMethod(array('POST'));
        $this->requirePermission('tickets:write');

        $ticket_id = $this->getPathParam('id');

        if (!$ticket_id || !is_numeric($ticket_id)) {
            ApiResponse::badRequest('Invalid ticket ID')->send();
        }

        $ticket = new Ticket($ticket_id);
        if (!$ticket || !$ticket->getId()) {
            ApiResponse::notFound('Ticket not found')->send();
        }

        $errors = array();
        $this->validateRequired(array('message'), $errors);

        $message = $this->getInput('message');
        if ($message && strlen($message) < 5) {
            $errors['message'] = 'Message must be at least 5 characters';
        }

        if ($errors) {
            ApiResponse::validationError($errors)->send();
        }

        $source = $this->getInput('source', 'API');
        $msgid = $ticket->postMessage($message, $source);

        if (!$msgid) {
            ApiResponse::internalError('Failed to post message')->send();
        }

        ApiResponse::created(
            array('message_id' => $msgid),
            'Message added successfully'
        )->send();
    }

    function notes() {
        $this->checkMethod(array('GET'));
        $this->requirePermission('tickets:read');

        $ticket_id = $this->getPathParam('id');

        if (!$ticket_id || !is_numeric($ticket_id)) {
            ApiResponse::badRequest('Invalid ticket ID')->send();
        }

        $ticket = new Ticket($ticket_id);
        if (!$ticket || !$ticket->getId()) {
            ApiResponse::notFound('Ticket not found')->send();
        }

        $sql = 'SELECT note.*, staff.firstname, staff.lastname FROM ' . TICKET_NOTE_TABLE . ' note' .
               ' LEFT JOIN ' . STAFF_TABLE . ' staff ON note.staff_id = staff.staff_id' .
               ' WHERE note.ticket_id=' . db_input($ticket_id) .
               ' ORDER BY note.created DESC';

        $result = db_query($sql);

        $notes = array();
        while ($row = db_fetch_array($result)) {
            $notes[] = array(
                'note_id' => (int)$row['note_id'],
                'note' => $row['note'],
                'staff' => array(
                    'id' => (int)$row['staff_id'],
                    'name' => trim($row['firstname'] . ' ' . $row['lastname'])
                ),
                'created_at' => $this->formatDate($row['created'])
            );
        }

        ApiResponse::success($notes)->send();
    }

    function addNote() {
        $this->checkMethod(array('POST'));
        $this->requirePermission('tickets:write');

        $ticket_id = $this->getPathParam('id');

        if (!$ticket_id || !is_numeric($ticket_id)) {
            ApiResponse::badRequest('Invalid ticket ID')->send();
        }

        $ticket = new Ticket($ticket_id);
        if (!$ticket || !$ticket->getId()) {
            ApiResponse::notFound('Ticket not found')->send();
        }

        $errors = array();
        $this->validateRequired(array('note'), $errors);

        $note = $this->getInput('note');
        if ($note && strlen($note) < 5) {
            $errors['note'] = 'Note must be at least 5 characters';
        }

        $staff_id = $this->getInput('staff_id');
        if (!$staff_id) {
            $errors['staff_id'] = 'Staff ID is required';
        }

        if ($errors) {
            ApiResponse::validationError($errors)->send();
        }

        $sql = 'INSERT INTO ' . TICKET_NOTE_TABLE . ' SET' .
               ' ticket_id=' . db_input($ticket_id) .
               ', staff_id=' . db_input($staff_id) .
               ', note=' . db_input($note) .
               ', created=NOW()';

        if (!db_query($sql)) {
            $this->handleDbError('add note');
        }

        $note_id = db_insert_id();

        ApiResponse::created(
            array('note_id' => $note_id),
            'Note added successfully'
        )->send();
    }

    function attachments() {
        $this->checkMethod(array('GET'));
        $this->requirePermission('tickets:read');

        $ticket_id = $this->getPathParam('id');

        if (!$ticket_id || !is_numeric($ticket_id)) {
            ApiResponse::badRequest('Invalid ticket ID')->send();
        }

        $ticket = new Ticket($ticket_id);
        if (!$ticket || !$ticket->getId()) {
            ApiResponse::notFound('Ticket not found')->send();
        }

        $sql = 'SELECT * FROM ' . TICKET_ATTACHMENT_TABLE .
               ' WHERE ticket_id=' . db_input($ticket_id) .
               ' ORDER BY created DESC';

        $result = db_query($sql);

        $attachments = array();
        while ($row = db_fetch_array($result)) {
            $attachments[] = array(
                'attachment_id' => (int)$row['attach_id'],
                'file_name' => $row['file_name'],
                'file_key' => $row['file_key'],
                'file_size' => (int)$row['file_size'],
                'created_at' => $this->formatDate($row['created'])
            );
        }

        ApiResponse::success($attachments)->send();
    }
}

?>
