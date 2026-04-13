<?php

if (!defined('THAPIV1INC')) die('Access Denied');

require_once(INCLUDE_DIR.'class.apicontroller.php');
require_once(INCLUDE_DIR.'class.document.php');

class KbController extends ApiController {

    function index() {
        $this->checkMethod(array('GET'));
        $this->requirePermission('kb:read');

        $pagination = $this->getPaginationParams();

        $where_parts = array();

        $doc_type = $this->getQuery('doc_type');
        if ($doc_type && in_array($doc_type, array('file', 'link'))) {
            $where_parts[] = 'doc_type=' . db_input($doc_type);
        }

        $audience = $this->getQuery('audience');
        if ($audience && in_array($audience, array('staff', 'client', 'all'))) {
            $where_parts[] = 'audience=' . db_input($audience);
        }

        $dept_id = $this->getQuery('dept_id');
        if ($dept_id) {
            $where_parts[] = 'dept_id=' . db_input($dept_id);
        }

        $is_enabled = $this->getQuery('is_enabled');
        if ($is_enabled !== null) {
            $where_parts[] = 'isenabled=' . db_input($is_enabled ? 1 : 0);
        }

        $search = $this->getQuery('search');
        if ($search) {
            $search_term = db_real_escape(str_replace(array('%','_'), array('\\%','\\_'), $search));
            $where_parts[] = "(title LIKE '%{$search_term}%' OR description LIKE '%{$search_term}%')";
        }

        $where = empty($where_parts) ? '' : ' WHERE ' . implode(' AND ', $where_parts);

        $count_sql = 'SELECT COUNT(*) as total FROM ' . KB_DOCUMENTS_TABLE . $where;
        $count_result = db_query($count_sql);
        $count_row = db_fetch_array($count_result);
        $total = $count_row['total'];

        $allowed_sort = array('doc_id', 'title', 'doc_type', 'audience', 'created', 'updated');
        $sort_params = $this->getSortParams($allowed_sort, 'created', 'DESC');

        $sql = 'SELECT
                    d.*,
                    dept.dept_name,
                    CONCAT(s.firstname, " ", s.lastname) as author_name
                FROM ' . KB_DOCUMENTS_TABLE . ' d
                LEFT JOIN ' . DEPT_TABLE . ' dept ON d.dept_id = dept.dept_id
                LEFT JOIN ' . STAFF_TABLE . ' s ON d.staff_id = s.staff_id'
                . $where
                . ' ORDER BY d.' . $sort_params['sort'] . ' ' . $sort_params['order']
                . $this->buildLimitClause();

        $result = db_query($sql);

        if (!$result) {
            $this->handleDbError('fetch KB documents');
        }

        $documents = array();
        while ($row = db_fetch_array($result)) {
            $documents[] = $this->formatDocumentListItem($row);
        }

        $this->paginated($documents, $total)->send();
    }

    function formatDocumentListItem($row) {
        $item = array(
            'doc_id' => (int)$row['doc_id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'doc_type' => $row['doc_type'],
            'audience' => $row['audience'],
            'is_enabled' => $this->formatBool($row['isenabled']),
            'department' => $row['dept_id'] ? array(
                'id' => (int)$row['dept_id'],
                'name' => $row['dept_name']
            ) : null,
            'author' => array(
                'id' => (int)$row['staff_id'],
                'name' => $row['author_name']
            ),
            'created_at' => $this->formatDate($row['created']),
            'updated_at' => $this->formatDate($row['updated'])
        );

        if ($row['doc_type'] == 'link') {
            $item['external_url'] = $row['external_url'];
        } elseif ($row['doc_type'] == 'file') {
            $item['file'] = array(
                'name' => $row['file_name'],
                'size' => (int)$row['file_size'],
                'mime' => $row['file_mime']
            );
        }

        return $item;
    }

    function show() {
        $this->checkMethod(array('GET'));
        $this->requirePermission('kb:read');

        $doc_id = $this->getPathParam('id');

        if (!$doc_id || !is_numeric($doc_id)) {
            ApiResponse::badRequest('Invalid document ID')->send();
        }

        $doc = Document::lookup($doc_id);
        if (!$doc) {
            ApiResponse::notFound('Document not found')->send();
        }

        $info = $doc->getInfo();

        $sql = 'SELECT
                    d.*,
                    dept.dept_name,
                    CONCAT(s.firstname, " ", s.lastname) as author_name,
                    s.email as author_email
                FROM ' . KB_DOCUMENTS_TABLE . ' d
                LEFT JOIN ' . DEPT_TABLE . ' dept ON d.dept_id = dept.dept_id
                LEFT JOIN ' . STAFF_TABLE . ' s ON d.staff_id = s.staff_id
                WHERE d.doc_id=' . db_input($doc_id);

        $result = db_query($sql);
        if ($result && db_num_rows($result)) {
            $row = db_fetch_array($result);
        } else {
            ApiResponse::notFound('Document not found')->send();
        }

        $data = array(
            'doc_id' => (int)$row['doc_id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'doc_type' => $row['doc_type'],
            'audience' => $row['audience'],
            'is_enabled' => $this->formatBool($row['isenabled']),
            'department' => $row['dept_id'] ? array(
                'id' => (int)$row['dept_id'],
                'name' => $row['dept_name']
            ) : null,
            'author' => array(
                'id' => (int)$row['staff_id'],
                'name' => $row['author_name'],
                'email' => $row['author_email']
            ),
            'created_at' => $this->formatDate($row['created']),
            'updated_at' => $this->formatDate($row['updated'])
        );

        if ($row['doc_type'] == 'link') {
            $data['external_url'] = $row['external_url'];
            $data['embed_url'] = $doc->getEmbedUrl();
            $data['is_google_doc'] = $doc->isGoogleDoc();
        } elseif ($row['doc_type'] == 'file') {
            $data['file'] = array(
                'name' => $row['file_name'],
                'key' => $row['file_key'],
                'size' => (int)$row['file_size'],
                'size_formatted' => $doc->getFileSizeFormatted(),
                'mime' => $row['file_mime'],
                'download_url' => '/doc_attachment.php?key=' . $row['file_key']
            );
        }

        ApiResponse::success($data)->send();
    }

    function create() {
        $this->checkMethod(array('POST'));
        $this->requirePermission('kb:write');

        $errors = array();

        $this->validateRequired(array('title', 'doc_type', 'audience'), $errors);

        $staff = $this->getAuthUser();
        if (!$staff) {
            ApiResponse::unauthorized('Authentication required')->send();
        }

        $doc_type = $this->getInput('doc_type');

        if ($doc_type != 'link') {
            $errors['doc_type'] = 'Only "link" type documents are supported via API. Use web interface for file uploads.';
        }

        $audience = $this->getInput('audience');
        if ($audience && !in_array($audience, array('staff', 'client', 'all'))) {
            $errors['audience'] = 'Invalid audience. Must be: staff, client, or all';
        }

        $external_url = $this->getInput('external_url');
        if ($doc_type == 'link') {
            if (!$external_url) {
                $errors['external_url'] = 'External URL is required for link type';
            } elseif (!filter_var($external_url, FILTER_VALIDATE_URL)) {
                $errors['external_url'] = 'Invalid URL format';
            }
        }

        if ($errors) {
            ApiResponse::validationError($errors)->send();
        }

        $data = array(
            'title' => $this->getInput('title'),
            'description' => $this->getInput('description'),
            'doc_type' => $doc_type,
            'external_url' => $external_url,
            'audience' => $audience,
            'dept_id' => $this->getInput('dept_id', 0),
            'staff_id' => $staff->getId(),
            'isenabled' => $this->getInput('is_enabled', 1)
        );

        $doc_id = Document::create($data, $errors);

        if (!$doc_id) {
            ApiResponse::validationError($errors)->send();
        }

        $doc = Document::lookup($doc_id);
        $info = $doc->getInfo();

        $response_data = array(
            'doc_id' => (int)$doc_id,
            'title' => $info['title'],
            'doc_type' => $info['doc_type'],
            'external_url' => $info['external_url'],
            'created_at' => $this->formatDate($info['created'])
        );

        ApiResponse::created($response_data, 'KB document created successfully')->send();
    }

    function update() {
        $this->checkMethod(array('PUT', 'PATCH'));
        $this->requirePermission('kb:write');

        $doc_id = $this->getPathParam('id');

        if (!$doc_id || !is_numeric($doc_id)) {
            ApiResponse::badRequest('Invalid document ID')->send();
        }

        $doc = Document::lookup($doc_id);
        if (!$doc) {
            ApiResponse::notFound('Document not found')->send();
        }

        $info = $doc->getInfo();
        $errors = array();

        $doc_type = $info['doc_type'];

        $audience = $this->getInput('audience', $info['audience']);
        if ($audience && !in_array($audience, array('staff', 'client', 'all'))) {
            $errors['audience'] = 'Invalid audience. Must be: staff, client, or all';
        }

        $external_url = $this->getInput('external_url', $info['external_url']);
        if ($doc_type == 'link' && $external_url) {
            if (!filter_var($external_url, FILTER_VALIDATE_URL)) {
                $errors['external_url'] = 'Invalid URL format';
            }
        }

        if ($errors) {
            ApiResponse::validationError($errors)->send();
        }

        $data = array(
            'title' => $this->getInput('title', $info['title']),
            'description' => $this->getInput('description', $info['description']),
            'doc_type' => $doc_type,
            'external_url' => $external_url,
            'audience' => $audience,
            'dept_id' => $this->getInput('dept_id', $info['dept_id']),
            'isenabled' => $this->getInput('is_enabled', $info['isenabled'])
        );

        if (!Document::update($doc_id, $data, $errors)) {
            ApiResponse::validationError($errors)->send();
        }

        $doc = Document::lookup($doc_id);
        $info = $doc->getInfo();

        $response_data = array(
            'doc_id' => (int)$doc_id,
            'title' => $info['title'],
            'updated_at' => $this->formatDate($info['updated'])
        );

        ApiResponse::success($response_data, 'KB document updated successfully')->send();
    }

    function delete() {
        $this->checkMethod(array('DELETE'));
        $this->requirePermission('kb:write');

        $doc_id = $this->getPathParam('id');

        if (!$doc_id || !is_numeric($doc_id)) {
            ApiResponse::badRequest('Invalid document ID')->send();
        }

        $doc = Document::lookup($doc_id);
        if (!$doc) {
            ApiResponse::notFound('Document not found')->send();
        }

        if (!Document::delete($doc_id)) {
            ApiResponse::error('Failed to delete document')->send();
        }

        ApiResponse::success(null, 'KB document deleted successfully')->send();
    }

    function search() {
        $this->checkMethod(array('POST', 'GET'));
        $this->requirePermission('kb:read');

        $errors = array();
        $this->validateRequired(array('q'), $errors);

        $query = $this->getInput('q');
        if (!$query) {
            $query = $this->getQuery('q');
        }

        if (!$query) {
            ApiResponse::validationError(array('q' => 'Search query is required'))->send();
        }

        $pagination = $this->getPaginationParams();

        $search_sql = "MATCH(title, description) AGAINST(" . db_input($query) . " IN BOOLEAN MODE)";

        $count_sql = 'SELECT COUNT(*) as total FROM ' . KB_DOCUMENTS_TABLE . ' WHERE ' . $search_sql;
        $count_result = db_query($count_sql);
        $count_row = db_fetch_array($count_result);
        $total = $count_row['total'];

        $sql = 'SELECT
                    d.*,
                    dept.dept_name,
                    CONCAT(s.firstname, " ", s.lastname) as author_name,
                    ' . $search_sql . ' as relevance
                FROM ' . KB_DOCUMENTS_TABLE . ' d
                LEFT JOIN ' . DEPT_TABLE . ' dept ON d.dept_id = dept.dept_id
                LEFT JOIN ' . STAFF_TABLE . ' s ON d.staff_id = s.staff_id
                WHERE ' . $search_sql . '
                ORDER BY relevance DESC'
                . $this->buildLimitClause();

        $result = db_query($sql);

        if (!$result) {
            $this->handleDbError('search KB documents');
        }

        $documents = array();
        while ($row = db_fetch_array($result)) {
            $doc = $this->formatDocumentListItem($row);
            $doc['relevance'] = (float)$row['relevance'];
            $documents[] = $doc;
        }

        $response = array(
            'query' => $query,
            'total' => $total,
            'page' => $pagination['page'],
            'per_page' => $pagination['per_page'],
            'results' => $documents
        );

        ApiResponse::success($response)->send();
    }
}

?>
