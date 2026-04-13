<?php
class Document {

    public $id;
    public $row;

    public function __construct($id) {
        $this->id = 0;
        if ($id && ($info = $this->getInfoById($id))) {
            $this->row = $info;
            $this->id = $info['doc_id'];
        }
    }

    function getId() {
        return $this->id;
    }

    function getTitle() {
        return $this->row['title'];
    }

    function getDescription() {
        return $this->row['description'];
    }

    function getDocType() {
        return $this->row['doc_type'];
    }

    function getFileName() {
        return $this->row['file_name'];
    }

    function getFileKey() {
        return $this->row['file_key'];
    }

    function getFileSize() {
        return $this->row['file_size'];
    }

    function getFileMime() {
        return $this->row['file_mime'];
    }

    function getExternalUrl() {
        return $this->row['external_url'];
    }

    function getAudience() {
        return $this->row['audience'];
    }

    function getDeptId() {
        return $this->row['dept_id'];
    }

    function getStaffId() {
        return $this->row['staff_id'];
    }

    function isEnabled() {
        return $this->row['isenabled'] ? true : false;
    }

    function getCreated() {
        return $this->row['created'];
    }

    function getUpdated() {
        return $this->row['updated'];
    }

    function getInfo() {
        return $this->row;
    }

    function isFile() {
        return $this->row['doc_type'] == 'file';
    }

    function isLink() {
        return $this->row['doc_type'] == 'link';
    }

    function getAudienceLabel() {
        $labels = array(
            'staff' => 'Менеджеры',
            'client' => 'Пользователи',
            'all' => 'Все'
        );
        return isset($labels[$this->row['audience']]) ? $labels[$this->row['audience']] : $this->row['audience'];
    }

    function getDocTypeLabel() {
        return $this->row['doc_type'] == 'file' ? 'Файл' : 'Ссылка';
    }

    function getFileSizeFormatted() {
        $size = $this->row['file_size'];
        if ($size < 1024) return $size . ' Б';
        if ($size < 1048576) return round($size / 1024, 1) . ' КБ';
        return round($size / 1048576, 1) . ' МБ';
    }

    function getEmbedUrl() {
        $url = $this->row['external_url'];
        if (!$url) return '';

        if (preg_match('#docs\.google\.com/document/d/([a-zA-Z0-9_-]+)#', $url, $m)) {
            return 'https://docs.google.com/document/d/' . $m[1] . '/preview';
        }
        if (preg_match('#docs\.google\.com/spreadsheets/d/([a-zA-Z0-9_-]+)#', $url, $m)) {
            return 'https://docs.google.com/spreadsheets/d/' . $m[1] . '/pubhtml?widget=true&headers=false';
        }
        if (preg_match('#docs\.google\.com/presentation/d/([a-zA-Z0-9_-]+)#', $url, $m)) {
            return 'https://docs.google.com/presentation/d/' . $m[1] . '/embed?start=false&loop=false&delayms=3000';
        }

        return $url;
    }

    function isGoogleDoc() {
        $url = $this->row['external_url'];
        if (!$url) return false;
        return (strpos($url, 'docs.google.com') !== false);
    }


    function getInfoById($id) {
        $sql = 'SELECT * FROM ' . KB_DOCUMENTS_TABLE . ' WHERE doc_id=' . db_input($id);
        if (($res = db_query($sql)) && db_num_rows($res))
            return db_fetch_array($res);

        return null;
    }

    static function lookup($id) {
        $doc = new Document($id);
        return ($doc && $doc->getId()) ? $doc : null;
    }

    static function create($data, &$errors) {
        global $cfg;

        if (!$data['title']) {
            $errors['title'] = 'Название документа обязательно';
        }

        if (!$data['doc_type'] || !in_array($data['doc_type'], array('file', 'link'))) {
            $errors['doc_type'] = 'Выберите тип документа';
        }

        if ($data['doc_type'] == 'link') {
            if (!$data['external_url']) {
                $errors['external_url'] = 'Укажите ссылку';
            } elseif (!filter_var($data['external_url'], FILTER_VALIDATE_URL)) {
                $errors['external_url'] = 'Некорректная ссылка';
            }
        }

        if (!$data['audience'] || !in_array($data['audience'], array('staff', 'client', 'all'))) {
            $errors['audience'] = 'Выберите аудиторию';
        }

        if (!$data['staff_id']) {
            $errors['err'] = 'Ошибка идентификации пользователя';
        }

        if ($errors) return false;

        $file_name = '';
        $file_key = '';
        $file_size = 0;
        $file_mime = '';

        if ($data['doc_type'] == 'file' && isset($data['file']) && $data['file']['tmp_name']) {
            $upload = Document::handleUpload($data['file'], $errors, $cfg);
            if (!$upload) return false;
            $file_name = $upload['file_name'];
            $file_key = $upload['file_key'];
            $file_size = $upload['file_size'];
            $file_mime = $upload['file_mime'];
        } elseif ($data['doc_type'] == 'file' && (!isset($data['file']) || !$data['file']['tmp_name'])) {
            $errors['file'] = 'Выберите файл для загрузки';
            return false;
        }

        $sql = sprintf(
            "INSERT INTO %s SET
                title=%s,
                description=%s,
                doc_type=%s,
                file_name=%s,
                file_key=%s,
                file_size=%d,
                file_mime=%s,
                external_url=%s,
                audience=%s,
                dept_id=%d,
                staff_id=%d,
                isenabled=%d,
                created=NOW()",
            KB_DOCUMENTS_TABLE,
            db_input(Format::striptags($data['title'])),
            db_input($data['description'] ? Format::striptags($data['description']) : ''),
            db_input($data['doc_type']),
            db_input($file_name),
            db_input($file_key),
            $file_size,
            db_input($file_mime),
            db_input($data['external_url'] ? $data['external_url'] : ''),
            db_input($data['audience']),
            db_input($data['dept_id'] ? $data['dept_id'] : 0),
            db_input($data['staff_id']),
            db_input(isset($data['isenabled']) ? $data['isenabled'] : 1)
        );

        if (!db_query($sql) || !($id = db_insert_id())) {
            $errors['err'] = 'Ошибка создания документа. Попробуйте снова.';
            return false;
        }

        return $id;
    }

    static function update($id, $data, &$errors) {
        global $cfg;

        if (!$id) {
            $errors['err'] = 'Отсутствует ID документа';
            return false;
        }

        if (!$data['title']) {
            $errors['title'] = 'Название документа обязательно';
        }

        if (!$data['doc_type'] || !in_array($data['doc_type'], array('file', 'link'))) {
            $errors['doc_type'] = 'Выберите тип документа';
        }

        if ($data['doc_type'] == 'link') {
            if (!$data['external_url']) {
                $errors['external_url'] = 'Укажите ссылку';
            } elseif (!filter_var($data['external_url'], FILTER_VALIDATE_URL)) {
                $errors['external_url'] = 'Некорректная ссылка';
            }
        }

        if (!$data['audience'] || !in_array($data['audience'], array('staff', 'client', 'all'))) {
            $errors['audience'] = 'Выберите аудиторию';
        }

        if ($errors) return false;

        $file_sql = '';
        if ($data['doc_type'] == 'file' && isset($data['file']) && $data['file']['tmp_name']) {
            $doc = new Document($id);
            if ($doc->getId() && $doc->isFile() && $doc->getFileKey()) {
                Document::deleteFile($doc->getFileKey(), $doc->getFileName(), $cfg);
            }

            $upload = Document::handleUpload($data['file'], $errors, $cfg);
            if (!$upload) return false;

            $file_sql = sprintf(
                ", file_name=%s, file_key=%s, file_size=%d, file_mime=%s",
                db_input($upload['file_name']),
                db_input($upload['file_key']),
                $upload['file_size'],
                db_input($upload['file_mime'])
            );
        }

        $sql = sprintf(
            "UPDATE %s SET
                title=%s,
                description=%s,
                doc_type=%s,
                external_url=%s,
                audience=%s,
                dept_id=%d,
                isenabled=%d,
                updated=NOW()
                %s
            WHERE doc_id=%d",
            KB_DOCUMENTS_TABLE,
            db_input(Format::striptags($data['title'])),
            db_input($data['description'] ? Format::striptags($data['description']) : ''),
            db_input($data['doc_type']),
            db_input($data['external_url'] ? $data['external_url'] : ''),
            db_input($data['audience']),
            db_input($data['dept_id'] ? $data['dept_id'] : 0),
            db_input(isset($data['isenabled']) ? $data['isenabled'] : 1),
            $file_sql,
            db_input($id)
        );

        if (!db_query($sql)) {
            $errors['err'] = 'Ошибка обновления документа. Попробуйте снова.';
            return false;
        }

        return true;
    }

    static function delete($id) {
        global $cfg;

        $doc = new Document($id);
        if (!$doc->getId()) return false;

        if ($doc->isFile() && $doc->getFileKey()) {
            Document::deleteFile($doc->getFileKey(), $doc->getFileName(), $cfg);
        }

        $sql = 'DELETE FROM ' . KB_DOCUMENTS_TABLE . ' WHERE doc_id=' . db_input($id);
        return db_query($sql) ? true : false;
    }

    static function handleUpload($file, &$errors, $cfg) {
        require_once(INCLUDE_DIR . 'class.fileupload.php');
        $info = FileUpload::process($file, 'docs', 0, $errors);
        if (!$info) return false;
        return [
            'file_name' => $info['file_name'],
            'file_key'  => $info['file_key'],
            'file_size' => $info['file_size'],
            'file_mime' => $info['file_mime'],
        ];
    }

    static function deleteFile($file_key, $file_name, $cfg) {
        $upload_dir = $cfg ? rtrim($cfg->getUploadDir(), '/\\') : '';
        $file_path = $upload_dir . '/docs/' . $file_key . '_' . $file_name;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    function getFilePath() {
        global $cfg;
        if (!$this->isFile() || !$this->getFileKey()) return '';
        $upload_dir = $cfg ? rtrim($cfg->getUploadDir(), '/\\') : '';
        return $upload_dir . '/docs/' . $this->getFileKey() . '_' . $this->getFileName();
    }
}
?>
