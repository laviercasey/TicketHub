<?php

if (!defined('THAPIV1INC')) die('Access Denied');

require_once(INCLUDE_DIR.'class.apicontroller.php');

class UsersController extends ApiController {

    function index() {
        $this->checkMethod(array('GET'));
        $this->requirePermission('users:read');

        $pagination = $this->getPaginationParams();

        $where_parts = array();

        $search = $this->getQuery('search');
        if ($search) {
            $search_term = db_real_escape(str_replace(array('%','_'), array('\\%','\\_'), $search));
            $where_parts[] = "(email LIKE '%{$search_term}%' OR name LIKE '%{$search_term}%')";
        }

        $email = $this->getQuery('email');
        if ($email) {
            $where_parts[] = 'email=' . db_input($email);
        }

        $created_from = $this->getQuery('created_from');
        if ($created_from) {
            $where_parts[] = 'first_created >= ' . db_input($created_from);
        }

        $created_to = $this->getQuery('created_to');
        if ($created_to) {
            $where_parts[] = 'first_created <= ' . db_input($created_to);
        }

        $where = empty($where_parts) ? '' : ' WHERE ' . implode(' AND ', $where_parts);

        $count_sql = 'SELECT COUNT(DISTINCT email) as total FROM ' . TICKET_TABLE . $where;
        $count_result = db_query($count_sql);
        $count_row = db_fetch_array($count_result);
        $total = $count_row['total'];

        $allowed_sort = array('email', 'name', 'tickets_count', 'first_created', 'last_created');
        $sort_params = $this->getSortParams($allowed_sort, 'last_created', 'DESC');

        $sql = 'SELECT
                    email,
                    MAX(name) as name,
                    MAX(phone) as phone,
                    COUNT(*) as tickets_count,
                    COUNT(CASE WHEN status="open" THEN 1 END) as open_tickets,
                    COUNT(CASE WHEN status="closed" THEN 1 END) as closed_tickets,
                    MIN(created) as first_created,
                    MAX(created) as last_created
                FROM ' . TICKET_TABLE .
                $where . '
                GROUP BY email
                ORDER BY ' . $sort_params['sort'] . ' ' . $sort_params['order'] .
                $this->buildLimitClause();

        $result = db_query($sql);

        if (!$result) {
            $this->handleDbError('fetch users');
        }

        $users = array();
        while ($row = db_fetch_array($result)) {
            $users[] = $this->formatUserListItem($row);
        }

        $this->paginated($users, $total)->send();
    }

    function formatUserListItem($row) {
        return array(
            'email' => $row['email'],
            'name' => $row['name'],
            'phone' => $row['phone'],
            'tickets' => array(
                'total' => (int)$row['tickets_count'],
                'open' => (int)$row['open_tickets'],
                'closed' => (int)$row['closed_tickets']
            ),
            'first_ticket_at' => $this->formatDate($row['first_created']),
            'last_ticket_at' => $this->formatDate($row['last_created'])
        );
    }

    function show() {
        $this->checkMethod(array('GET'));
        $this->requirePermission('users:read');

        $email = $this->getPathParam('id');

        if (!$email) {
            ApiResponse::badRequest('Email is required')->send();
        }

        $errors = array();
        if (!$this->validateEmail($email, $errors)) {
            ApiResponse::validationError($errors)->send();
        }

        $sql = 'SELECT
                    email,
                    MAX(name) as name,
                    MAX(phone) as phone,
                    MAX(cabinet) as cabinet,
                    MAX(phone_ext) as phone_ext,
                    COUNT(*) as tickets_count,
                    COUNT(CASE WHEN status="open" THEN 1 END) as open_tickets,
                    COUNT(CASE WHEN status="closed" THEN 1 END) as closed_tickets,
                    MIN(created) as first_created,
                    MAX(created) as last_created,
                    MAX(updated) as last_updated
                FROM ' . TICKET_TABLE . '
                WHERE email=' . db_input($email) . '
                GROUP BY email';

        $result = db_query($sql);

        if (!$result || !db_num_rows($result)) {
            ApiResponse::notFound('User not found')->send();
        }

        $row = db_fetch_array($result);

        $tickets_sql = 'SELECT
                            ticket_id,
                            ticketID as number,
                            subject,
                            status,
                            priority_id,
                            dept_id,
                            created
                        FROM ' . TICKET_TABLE . '
                        WHERE email=' . db_input($email) . '
                        ORDER BY created DESC
                        LIMIT 10';

        $tickets_result = db_query($tickets_sql);
        $recent_tickets = array();

        while ($ticket_row = db_fetch_array($tickets_result)) {
            $recent_tickets[] = array(
                'ticket_id' => (int)$ticket_row['ticket_id'],
                'number' => $ticket_row['number'],
                'subject' => $ticket_row['subject'],
                'status' => $ticket_row['status'],
                'created_at' => $this->formatDate($ticket_row['created'])
            );
        }

        $data = array(
            'email' => $row['email'],
            'name' => $row['name'],
            'phone' => $row['phone'],
            'phone_ext' => $row['phone_ext'],
            'cabinet' => $row['cabinet'],
            'tickets' => array(
                'total' => (int)$row['tickets_count'],
                'open' => (int)$row['open_tickets'],
                'closed' => (int)$row['closed_tickets']
            ),
            'first_ticket_at' => $this->formatDate($row['first_created']),
            'last_ticket_at' => $this->formatDate($row['last_created']),
            'last_updated_at' => $this->formatDate($row['last_updated']),
            'recent_tickets' => $recent_tickets
        );

        ApiResponse::success($data)->send();
    }

    function create() {
        $this->checkMethod(array('POST'));
        $this->requirePermission('users:write');

        $errors = array();

        $this->validateRequired(array('email'), $errors);

        $email = $this->getInput('email');
        if ($email) {
            $this->validateEmail($email, $errors);
        }

        $name = $this->getInput('name');
        if (!$name && $email) {
            $name = $email;
        }

        if ($errors) {
            ApiResponse::validationError($errors)->send();
        }

        $check_sql = 'SELECT email, name FROM ' . TICKET_TABLE .
                     ' WHERE email=' . db_input($email) . ' LIMIT 1';
        $check_result = db_query($check_sql);

        if ($check_result && db_num_rows($check_result)) {
            $existing = db_fetch_array($check_result);
            ApiResponse::conflict(
                'User with this email already exists. Use PUT to update.'
            )->send();
        }

        $data = array(
            'email' => $email,
            'name' => $name,
            'phone' => $this->getInput('phone'),
            'note' => 'User will be created when first ticket is submitted'
        );

        ApiResponse::created($data, 'User data validated')->send();
    }

    function update() {
        $this->checkMethod(array('PUT', 'PATCH'));
        $this->requirePermission('users:write');

        $email = $this->getPathParam('id');

        if (!$email) {
            ApiResponse::badRequest('Email is required')->send();
        }

        $errors = array();
        if (!$this->validateEmail($email, $errors)) {
            ApiResponse::validationError($errors)->send();
        }

        $check_sql = 'SELECT COUNT(*) as count FROM ' . TICKET_TABLE .
                     ' WHERE email=' . db_input($email);
        $check_result = db_query($check_sql);
        $check_row = db_fetch_array($check_result);

        if ($check_row['count'] == 0) {
            ApiResponse::notFound('User not found')->send();
        }

        $updates = array();

        $name = $this->getInput('name');
        if ($name !== null) {
            if (strlen($name) < 2) {
                $errors['name'] = 'Name must be at least 2 characters';
            } else {
                $updates[] = 'name=' . db_input($name);
            }
        }

        $phone = $this->getInput('phone');
        if ($phone !== null) {
            $updates[] = 'phone=' . db_input($phone);
        }

        $phone_ext = $this->getInput('phone_ext');
        if ($phone_ext !== null) {
            $updates[] = 'phone_ext=' . db_input($phone_ext);
        }

        $cabinet = $this->getInput('cabinet');
        if ($cabinet !== null) {
            $updates[] = 'cabinet=' . db_input($cabinet);
        }

        if ($errors) {
            ApiResponse::validationError($errors)->send();
        }

        if (empty($updates)) {
            ApiResponse::badRequest('No fields to update')->send();
        }

        $updates[] = 'updated=NOW()';
        $sql = 'UPDATE ' . TICKET_TABLE .
               ' SET ' . implode(', ', $updates) .
               ' WHERE email=' . db_input($email);

        if (!db_query($sql)) {
            $this->handleDbError('update user');
        }

        $affected = db_affected_rows();

        $sql = 'SELECT
                    email,
                    MAX(name) as name,
                    MAX(phone) as phone,
                    MAX(cabinet) as cabinet,
                    MAX(phone_ext) as phone_ext,
                    COUNT(*) as tickets_count
                FROM ' . TICKET_TABLE . '
                WHERE email=' . db_input($email) . '
                GROUP BY email';

        $result = db_query($sql);
        $row = db_fetch_array($result);

        $data = array(
            'email' => $row['email'],
            'name' => $row['name'],
            'phone' => $row['phone'],
            'phone_ext' => $row['phone_ext'],
            'cabinet' => $row['cabinet'],
            'tickets_updated' => $affected
        );

        ApiResponse::success($data, 'User updated successfully')->send();
    }

    function delete() {
        $this->checkMethod(array('DELETE'));
        $this->requirePermission('users:write');

        $email = $this->getPathParam('id');

        if (!$email) {
            ApiResponse::badRequest('Email is required')->send();
        }

        $errors = array();
        if (!$this->validateEmail($email, $errors)) {
            ApiResponse::validationError($errors)->send();
        }

        $check_sql = 'SELECT COUNT(*) as count FROM ' . TICKET_TABLE .
                     ' WHERE email=' . db_input($email);
        $check_result = db_query($check_sql);
        $check_row = db_fetch_array($check_result);

        if ($check_row['count'] == 0) {
            ApiResponse::notFound('User not found')->send();
        }

        ApiResponse::unprocessableEntity(
            'Cannot delete user with existing tickets. Close or delete all user tickets first.'
        )->send();
    }
}

?>
