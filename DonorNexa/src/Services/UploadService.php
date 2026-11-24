<?php
declare(strict_types=1);

namespace DonorNexa\Services;

use DonorNexa\Exceptions\ValidationException;

final class UploadService
{
    public function save(array $file, string $kind): string
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new ValidationException('The upload did not complete. Please select the file again.');
        }

        $maxBytes = $kind === 'photo' ? 2 * 1024 * 1024 : 5 * 1024 * 1024;
        if ((int) $file['size'] < 1 || (int) $file['size'] > $maxBytes) {
            throw new ValidationException($kind === 'photo'
                ? 'Profile photos must be smaller than 2 MB.'
                : 'Licence documents must be smaller than 5 MB.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
        $allowed = $kind === 'photo'
            ? ['image/jpeg' => 'jpg', 'image/png' => 'png']
            : ['application/pdf' => 'pdf'];
        if (!isset($allowed[$mime])) {
            throw new ValidationException($kind === 'photo'
                ? 'Upload a JPG or PNG image.'
                : 'Upload a PDF licence document.');
        }

        if ($kind === 'photo') {
            $directory = APP_ROOT . '/storage/donors';
            $relativeDirectory = 'storage/donors';
        } else {
            $directory = APP_ROOT . '/storage/licences';
            $relativeDirectory = 'storage/licences';
        }
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new \RuntimeException('The upload directory could not be created.');
        }

        $fileName = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
        if (!move_uploaded_file((string) $file['tmp_name'], $directory . '/' . $fileName)) {
            throw new \RuntimeException('The uploaded file could not be saved.');
        }

        return $relativeDirectory . '/' . $fileName;
    }
}
