<?php

declare(strict_types=1);

namespace App\Utils;

use App\Types\StandardTypes;

/**
 * @phpstan-import-type NonNegInt from StandardTypes
 * @phpstan-import-type Permissions from StandardTypes
 */
class File
{
    /**
     * @param non-empty-string $url
     * @param non-empty-string $file
     * @param bool $overwrite
     * @param Permissions $filePermissions
     * @param Permissions $mkdirPermissions
     * @return bool
     */
    public static function download(string $url, string $file, bool $overwrite, int $filePermissions = 0600, int $mkdirPermissions = 0700): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            Log::error("Invalid URL for download: {$url}");
            return false;
        }

        $tempFile = $file . '.part';

        if (!self::ensureTargetWritable($file, $overwrite, $mkdirPermissions)) {
            return false;
        }

        if (!self::ensureTargetWritable($tempFile, $overwrite, $mkdirPermissions)) {
            return false;
        }

        error_clear_last();

        $fileHandle = fopen($tempFile, 'wb');

        if ($fileHandle === false) {
            $error = error_get_last();
            Log::error("fopen failed: {$tempFile}" . ($error ? ' - ' . $error['message'] : ''));
            return false;
        }

        $res = Curl::fileResponse($url, [], [], $fileHandle);
        fclose($fileHandle);

        if ($res === false) {
            self::delete($tempFile);
            return false;
        }

        if (filesize($tempFile) === 0) {
            Log::error("Downloaded file was empty at {$tempFile}");
            self::delete($tempFile);
            return false;
        }

        if (!self::rename($tempFile, $file)) {
            Log::error("Failed to rename temp file {$tempFile} to {$file}");
            self::delete($tempFile);
            return false;
        }

        if (!self::setPermissions($file, $filePermissions)) {
            return false;
        }

        return true;
    }

    /**
     * @param non-empty-string $file
     * @return bool
     */
    public static function delete(string $file): bool
    {
        if (!self::ensureWritable($file)) {
            return false;
        }

        error_clear_last();

        if (!unlink($file)) {
            $error = error_get_last();
            Log::error("fopen failed: {$file}" . ($error ? ' - ' . $error['message'] : ''));
            return false;

        }

        // Verify post-conditions
        clearstatcache(true, $file);

        if (file_exists($file)) {
            Log::error("File still exists after deletion: {$file}");
            return false;
        }

        return true;
    }

    /**
     * Atomically renames or moves a file within the same filesystem.
     *
     * @param non-empty-string $source
     * @param non-empty-string $target
     * @param bool $overwrite
     * @param Permissions $mkdirPermissions
     * @return bool
     */
    public static function rename(string $source, string $target, bool $overwrite = false, int $mkdirPermissions = 0700): bool
    {
        if (!self::ensureReadable($source)) {
            return false;
        }

        if (!self::ensureTargetWritable($target, $overwrite, $mkdirPermissions)) {
            return false;
        }

        $oldPermissions = fileperms($source) & 0777;

        error_clear_last();

        if (!rename($source, $target)) {
            $error = error_get_last();
            Log::error("rename failed: {$source} → {$target}" . ($error ? ' - ' . $error['message'] : ''));
            return false;
        }

        // Verify post-conditions
        clearstatcache(true, $source);
        clearstatcache(true, $target);

        if (file_exists($source)) {
            Log::error("Source file still exists after rename: {$source}");
            return false;
        }

        if (!file_exists($target)) {
            Log::error("Target file missing after rename: {$target}");
            return false;
        }

        $newPermissions = fileperms($target) & 0777;

        if ($oldPermissions !== $newPermissions) {
            $oldOctal = FilePermissions::toOctal($oldPermissions);
            $newOctal = FilePermissions::toOctal($newPermissions);

            Log::warning("Permissions were not preserved. From {$oldOctal} to {$newOctal}");
            self::setPermissions($target, $oldPermissions);
        }

        return true;
    }

    /**
     * Moves a file by copying and then deleting the original.
     *
     * This is intended for moving files across filesystems or partitions where `rename()` would fail.
     *
     * @param non-empty-string $source
     * @param non-empty-string $target
     * @param bool $overwrite
     * @param Permissions $mkdirPermissions
     * @return bool
     */
    public static function move(string $source, string $target, bool $overwrite = false, int $mkdirPermissions = 0700): bool
    {
        if (!self::ensureReadable($source)) {
            return false;
        }

        if (!self::ensureTargetWritable($target, $overwrite, $mkdirPermissions)) {
            return false;
        }

        $oldPermissions = fileperms($source) & 0777;

        error_clear_last();

        if (!copy($source, $target)) {
            $error = error_get_last();
            Log::error("copy failed: {$source} → {$target}" . ($error ? ' - ' . $error['message'] : ''));
            return false;
        }

        if (!self::setPermissions($target, $oldPermissions)) {
            return false;
        }

        // Verify post-condition
        if (!self::delete($source)) {
            Log::error("Failed to delete source file after move: {$source}");
            return false;
        }

        return true;
    }

    /**
     * @param non-empty-string $location
     * @param NonNegInt $permissions
     * @return bool
     */
    public static function setPermissions(string $location, int $permissions): bool
    {
        error_clear_last();

        if (!chmod($location, $permissions)) {
            $error = error_get_last();
            Log::error("chmod failed: {$location}" . ($error ? ' - ' . $error['message'] : ''));
            return false;
        }

        // Verify post-conditions
        clearstatcache(true, $location);

        $newPermissions = fileperms($location) & 0777;

        if ($newPermissions !== $permissions) {
            $octal = FilePermissions::toOctal($permissions);
            $newOctal = FilePermissions::toOctal($newPermissions);

            Log::error("chmod succeeded but permissions mismatch on {$location}: expected {$octal}, got {$newOctal}");
            return false;
        }

        return true;
    }

    /**
     * @param non-empty-string $file
     * @return bool
     */
    protected static function ensureReadable(string $file): bool
    {
        if (!file_exists($file)) {
            Log::error("File doesn't exist: {$file}");
            return false;
        }

        if (!is_readable($file)) {
            Log::error("File isn't readable: {$file}");
            return false;
        }

        return true;
    }

    /**
     * @param non-empty-string $file
     * @return bool
     */
    protected static function ensureWritable(string $file): bool
    {
        if (!file_exists($file)) {
            Log::error("File doesn't exist: {$file}");
            return false;
        }

        if (!is_writable($file)) {
            Log::error("File isn't readable: {$file}");
            return false;
        }

        return true;
    }

    /**
     * @param non-empty-string $file
     * @param bool $overwrite
     * @param Permissions $mkdirPermissions
     * @return bool
     */
    protected static function ensureTargetWritable(string $file, bool $overwrite, int $mkdirPermissions): bool
    {
        $path = dirname($file);

        // If directory doesn't exist, create it
        if (!is_dir($path)) {
            if (!mkdir($path, $mkdirPermissions, true) && !is_dir($path)) {
                Log::error("Failed to create directory: {$path}");
                return false;
            }

            return true;
        }

        // Otherwise, check if target file already exist
        if (file_exists($file)) {
            if (!$overwrite) {
                Log::error("Target file already exists: {$file}");
                return false;
            }

            if (!is_writable($file)) {
                Log::error("Target file exists but is not writable: {$file}");
                return false;
            }

            Log::warning("Overwriting file: {$file}");

            if (!self::delete($file)) {
                return false;
            }
        }

        return true;
    }
}
