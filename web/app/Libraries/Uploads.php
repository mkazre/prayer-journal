<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;

/**
 * Stores user uploads under public/uploads/<folder>/ with random names and
 * strict type checks. Returns the public-relative path ("uploads/...").
 */
class Uploads
{
    public const IMAGE_TYPES = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];
    public const FILE_TYPES  = ['pdf' => 'application/pdf', 'mp3' => 'audio/mpeg', 'm4a' => 'audio/mp4', 'mp4' => 'video/mp4', 'zip' => 'application/zip', 'epub' => 'application/epub+zip'];

    public static function image(?UploadedFile $file, string $folder, int $maxKb = 5120): ?string
    {
        if (! $file || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        self::check($file, self::IMAGE_TYPES, $maxKb);
        if (@getimagesize($file->getTempName()) === false) {
            throw new RuntimeException('That file is not a valid image.');
        }

        return self::move($file, $folder);
    }

    public static function file(?UploadedFile $file, string $folder, int $maxKb = 51200, bool $private = false): ?string
    {
        if (! $file || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        self::check($file, self::FILE_TYPES + self::IMAGE_TYPES, $maxKb);

        return self::move($file, $folder, $private);
    }

    /**
     * Absolute path for a stored file (public or private).
     */
    public static function path(string $relative): string
    {
        return str_starts_with($relative, 'private/') ? WRITEPATH . 'uploads/' . substr($relative, 8) : FCPATH . $relative;
    }

    public static function delete(?string $relative): void
    {
        if ($relative && (str_starts_with($relative, 'uploads/') || str_starts_with($relative, 'private/'))) {
            $abs = self::path($relative);
            if (is_file($abs)) {
                @unlink($abs);
            }
        }
    }

    private static function check(UploadedFile $file, array $types, int $maxKb): void
    {
        if (! $file->isValid()) {
            throw new RuntimeException($file->getErrorString());
        }
        $ext  = strtolower($file->getClientExtension() ?: $file->guessExtension() ?: '');
        $mime = $file->getMimeType();
        if (! isset($types[$ext]) || ($mime !== $types[$ext] && ! ($ext === 'm4a' && str_starts_with($mime, 'audio/')) && ! ($ext === 'epub' && $mime === 'application/zip'))) {
            throw new RuntimeException('Unsupported file type. Allowed: ' . implode(', ', array_keys($types)) . '.');
        }
        if ($file->getSizeByUnit('kb') > $maxKb) {
            throw new RuntimeException('File is too large (max ' . round($maxKb / 1024, 1) . ' MB).');
        }
    }

    private static function move(UploadedFile $file, string $folder, bool $private = false): string
    {
        $folder = trim(preg_replace('/[^a-z0-9\/_-]/i', '', $folder), '/');
        $dir    = ($private ? WRITEPATH . 'uploads/' : FCPATH . 'uploads/') . $folder . '/' . date('Y/m');
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException('Could not create the upload folder.');
        }
        $name = bin2hex(random_bytes(10)) . '.' . strtolower($file->getClientExtension() ?: $file->guessExtension());
        $file->move($dir, $name);

        return ($private ? 'private/' : 'uploads/') . $folder . '/' . date('Y/m') . '/' . $name;
    }
}
