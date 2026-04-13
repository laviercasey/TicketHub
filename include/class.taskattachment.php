<?php
class TaskAttachment {

    public $id;
    public $row;

    public function __construct($id) {
        $this->id = 0;
        if ($id && ($info = $this->getInfoById($id))) {
            $this->row = $info;
            $this->id = $info['attachment_id'];
        }
    }

    function getId() { return $this->id; }
    function getTaskId() { return $this->row['task_id']; }
    function getFileName() { return $this->row['file_name']; }
    function getFileKey() { return $this->row['file_key']; }
    function getFileSize() { return $this->row['file_size']; }
    function getFileMime() { return $this->row['file_mime']; }
    function getUploadedBy() { return $this->row['uploaded_by']; }
    function getUploadedDate() { return $this->row['uploaded_date']; }
    function getUploaderName() { return $this->row['uploader_name']; }
    function getInfo() { return $this->row; }

    function getFileSizeFormatted() {
        $size = $this->row['file_size'];
        if ($size < 1024) return $size . ' Б';
        if ($size < 1048576) return round($size / 1024, 1) . ' КБ';
        return round($size / 1048576, 1) . ' МБ';
    }

    function getFilePath() {
        global $cfg;
        $upload_dir = $cfg ? rtrim($cfg->getUploadDir(), '/\\') : '';
        return $upload_dir . '/tasks/' . $this->row['file_key'] . '_' . $this->row['file_name'];
    }

    function isImage() {
        $mime = strtolower($this->row['file_mime']);
        return in_array($mime, array('image/jpeg', 'image/png', 'image/gif', 'image/webp'));
    }

    function getIconClass() {
        $ext = strtolower(pathinfo($this->row['file_name'], PATHINFO_EXTENSION));
        $map = array(
            'pdf' => 'file-pdf-o',
            'doc' => 'file-word-o', 'docx' => 'file-word-o',
            'xls' => 'file-excel-o', 'xlsx' => 'file-excel-o',
            'ppt' => 'file-powerpoint-o', 'pptx' => 'file-powerpoint-o',
            'zip' => 'file-archive-o', 'rar' => 'file-archive-o', '7z' => 'file-archive-o',
            'jpg' => 'file-image-o', 'jpeg' => 'file-image-o', 'png' => 'file-image-o', 'gif' => 'file-image-o',
            'txt' => 'file-text-o', 'csv' => 'file-text-o'
        );
        return isset($map[$ext]) ? $map[$ext] : 'file-o';
    }

    static function getInfoById($id) {
        $sql = 'SELECT a.*, CONCAT(s.firstname," ",s.lastname) as uploader_name'
             . ' FROM ' . TASK_ATTACHMENTS_TABLE . ' a'
             . ' LEFT JOIN ' . TABLE_PREFIX . 'staff s ON s.staff_id=a.uploaded_by'
             . ' WHERE a.attachment_id=' . db_input($id);
        if (($res = db_query($sql)) && db_num_rows($res))
            return db_fetch_array($res);
        return null;
    }

    static function lookup($id) {
        $a = new TaskAttachment($id);
        return ($a && $a->getId()) ? $a : null;
    }

    static function getByTaskId($task_id) {
        $attachments = array();
        $sql = 'SELECT a.*, CONCAT(s.firstname," ",s.lastname) as uploader_name'
             . ' FROM ' . TASK_ATTACHMENTS_TABLE . ' a'
             . ' LEFT JOIN ' . TABLE_PREFIX . 'staff s ON s.staff_id=a.uploaded_by'
             . ' WHERE a.task_id=' . db_input($task_id)
             . ' ORDER BY a.uploaded_date DESC';
        if (($res = db_query($sql)) && db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                $attachments[] = $row;
            }
        }
        return $attachments;
    }

    static function getCountByTaskId($task_id) {
        $sql = 'SELECT COUNT(*) FROM ' . TASK_ATTACHMENTS_TABLE . ' WHERE task_id=' . db_input($task_id);
        if (($res = db_query($sql)) && db_num_rows($res)) {
            list($count) = db_fetch_row($res);
            return $count;
        }
        return 0;
    }

    static function upload($task_id, $file, $staff_id, &$errors) {
        if (!$task_id) {
            $errors['err'] = 'Не указана задача';
            return false;
        }

        require_once(INCLUDE_DIR . 'class.fileupload.php');
        $info = FileUpload::process($file, 'tasks', 0, $errors);
        if (!$info) return false;

        $sql = sprintf(
            "INSERT INTO %s SET task_id=%d, file_name=%s, file_key=%s, file_size=%d, file_mime=%s, uploaded_by=%d, uploaded_date=NOW()",
            TASK_ATTACHMENTS_TABLE,
            db_input($task_id),
            db_input($info['file_name']),
            db_input($info['file_key']),
            $info['file_size'],
            db_input($info['file_mime']),
            db_input($staff_id)
        );

        if (!db_query($sql) || !($id = db_insert_id())) {
            FileUpload::delete($info['file_path']);
            $errors['file'] = 'Ошибка сохранения в базу данных';
            return false;
        }

        TaskActivity::log($task_id, $staff_id, 'updated', 'Добавлен файл: ' . $info['file_name']);
        return $id;
    }

    static function delete($id) {
        global $cfg;

        $att = new TaskAttachment($id);
        if (!$att->getId()) return false;

        $path = $att->getFilePath();
        if ($path && file_exists($path)) {
            unlink($path);
        }

        $sql = 'DELETE FROM ' . TASK_ATTACHMENTS_TABLE . ' WHERE attachment_id=' . db_input($id);
        return db_query($sql) ? true : false;
    }

    static function deleteByTaskId($task_id) {
        global $cfg;

        $attachments = TaskAttachment::getByTaskId($task_id);
        foreach ($attachments as $att) {
            $upload_dir = $cfg ? rtrim($cfg->getUploadDir(), '/\\') : '';
            $path = $upload_dir . '/tasks/' . $att['file_key'] . '_' . $att['file_name'];
            if (file_exists($path)) unlink($path);
        }

        $sql = 'DELETE FROM ' . TASK_ATTACHMENTS_TABLE . ' WHERE task_id=' . db_input($task_id);
        return db_query($sql) ? true : false;
    }
}
?>
