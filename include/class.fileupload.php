<?php
class FileUpload {

    private const MIME_MAP = [
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt'  => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'odt'  => 'application/vnd.oasis.opendocument.text',
        'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',
        'txt'  => 'text/plain',
        'csv'  => 'text/csv',
        'rtf'  => 'application/rtf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'bmp'  => 'image/bmp',
        'zip'  => 'application/zip',
        'rar'  => 'application/x-rar-compressed',
        '7z'   => 'application/x-7z-compressed',
        'tar'  => 'application/x-tar',
        'gz'   => 'application/gzip',
    ];

    private const INLINE_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf',
    ];

    private const UPLOAD_ERRORS = [
        UPLOAD_ERR_INI_SIZE   => 'Файл превышает допустимый размер (php.ini)',
        UPLOAD_ERR_FORM_SIZE  => 'Файл превышает допустимый размер формы',
        UPLOAD_ERR_PARTIAL    => 'Файл загружен частично',
        UPLOAD_ERR_NO_FILE    => 'Файл не выбран',
        UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная директория',
        UPLOAD_ERR_CANT_WRITE => 'Ошибка записи на диск',
    ];

    public static function process(array $file, string $subdir, int $max_size, array &$errors): array|false {
        global $cfg;

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['file'] = self::UPLOAD_ERRORS[$file['error']] ?? 'Ошибка загрузки файла';
            return false;
        }

        if ($max_size <= 0) {
            $max_size = $cfg ? $cfg->getMaxFileSize() : 10485760;
        }
        if ($file['size'] > $max_size) {
            $errors['file'] = sprintf('Файл слишком большой. Максимум: %s МБ', round($max_size / 1048576, 1));
            return false;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!array_key_exists($ext, self::MIME_MAP)) {
            $errors['file'] = 'Недопустимый тип файла. Разрешены: ' . implode(', ', array_unique(array_keys(self::MIME_MAP)));
            return false;
        }

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $knownMimes   = array_values(self::MIME_MAP);
        $expectedMime = self::MIME_MAP[$ext];

        if ($realMime !== $expectedMime && !in_array($realMime, $knownMimes, true)) {
            $errors['file'] = 'Тип содержимого файла не соответствует расширению';
            return false;
        }
        $mime = in_array($realMime, $knownMimes, true) ? $realMime : 'application/octet-stream';

        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
        if (!$safeName || $safeName === '.') {
            $safeName = 'file.' . $ext;
        }

        $fileKey = bin2hex(random_bytes(16));

        $uploadDir = $cfg ? rtrim($cfg->getUploadDir(), '/\\') : '';
        if (!$uploadDir) {
            $errors['file'] = 'Директория загрузки не настроена. Обратитесь к администратору.';
            return false;
        }
        $destDir = $uploadDir . '/' . ltrim($subdir, '/\\');
        if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) {
            $errors['file'] = 'Не удалось создать директорию загрузки';
            return false;
        }

        $dest = $destDir . '/' . $fileKey . '_' . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            $errors['file'] = 'Ошибка сохранения файла на диск';
            return false;
        }

        return [
            'file_name' => $safeName,
            'file_key'  => $fileKey,
            'file_size' => (int)$file['size'],
            'file_mime' => $mime,
            'file_path' => $dest,
        ];
    }

    public static function buildPath(string $subdir, string $fileKey, string $fileName): string {
        global $cfg;
        $uploadDir = $cfg ? rtrim($cfg->getUploadDir(), '/\\') : '';
        return $uploadDir . '/' . ltrim($subdir, '/\\') . '/' . $fileKey . '_' . $fileName;
    }

    public static function delete(string $path): bool {
        return !file_exists($path) || unlink($path);
    }

    public static function serve(string $path, string $filename, string $mime, bool $inline = false): never {
        if (!file_exists($path)) {
            Http::response(404, 'Файл не найден');
            exit;
        }

        global $cfg;
        $uploadDir = $cfg ? realpath(rtrim($cfg->getUploadDir(), '/\\')) : false;
        $realPath  = realpath($path);
        if (!$realPath || !$uploadDir || !str_starts_with($realPath, $uploadDir)) {
            Http::response(403, 'Доступ запрещён');
            exit;
        }

        $safeName    = str_replace(["\r", "\n", '"'], '', basename($filename));
        $disposition = ($inline && in_array(strtolower($mime), self::INLINE_TYPES, true))
            ? 'inline'
            : 'attachment';

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: ' . $disposition . '; filename="' . $safeName . '"');
        header('Cache-Control: private, max-age=3600');
        readfile($path);
        exit;
    }

    public static function allowedExtensions(): array {
        return array_unique(array_keys(self::MIME_MAP));
    }
}
