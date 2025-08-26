<?php

declare(strict_types=1);

namespace App\Utils;

use App\Types\StandardTypes;

/**
 * @phpstan-import-type Permissions from StandardTypes
 */
class Image
{
    /**
     * Download an image from a URL to the temp directory to determine it's mime type, then move it to the passed
     * path/filename with the correct extension added.
     *
     * @param non-empty-string $url
     * @param non-empty-string $path
     * @param non-empty-string $filename
     * @param bool $overwrite
     * @param Permissions $filePermissions
     * @param Permissions $mkdirPermissions
     * @return non-empty-string|false The filename with extension, or false on failure
     */
    public static function download(string $url, string $path, string $filename, bool $overwrite, int $filePermissions = 0600, int $mkdirPermissions = 0700): string|false
    {
        $tempFile = sys_get_temp_dir() . '/' . $filename;

        if (!File::download($url, $tempFile, true, $filePermissions, $mkdirPermissions)) {
            return false;
        }

        $mime = mime_content_type($tempFile);

        if ($mime === false || empty($mime)) {
            Log::error("`mime_content_type()` failed for: {$url}", $mime);
            File::delete($tempFile);
            return false;
        }

        $ext = self::extensionFromMime($mime);

        if ($ext === null) {
            Log::error("Unknown MIME type: {$mime} for: {$url}");
            File::delete($tempFile);
            return false;
        }

        $filename .= ".{$ext}";

        if (!File::rename($tempFile, $path . '/' . $filename, $overwrite, $mkdirPermissions)) {
            return false;
        }

        return $filename;
    }

    /**
     * Map known MIME types to extensions.
     *
     * @param non-empty-string $mime
     * @return ?non-empty-string
     */
    protected static function extensionFromMime(string $mime): ?string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            default      => null,
        };
    }
}
