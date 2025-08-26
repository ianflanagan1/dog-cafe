<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AppUserRepository;
use App\Types\StandardTypes;
use App\Utils\File;
use App\Utils\Image;

/**
 * @phpstan-import-type PosInt from StandardTypes
 */
class AppUserPictureService
{
    protected const string PATH = SHARED_FRONTEND_PATH . '/user';

    public function __construct(
        protected AppUserRepository $appUserRepository,
    ) {
    }

    /**
     * Download an image from the given URL and saves to disk.
     *
     * Returning null indicates failure to download or save, which should be treated the same as if no image URL was given.
     *
     * @param non-empty-string $url
     * @param non-empty-string $uniqueSuffix
     * @return ?non-empty-string
     */
    public static function saveFile(string $url, string $uniqueSuffix): ?string
    {
        $filename = Image::download(
            $url,
            self::PATH,
            self::createFilename($url, $uniqueSuffix),
            false
        );

        if ($filename === false) {
            return null;
        }

        return $filename;
    }

    /**
     * @param non-empty-string $picture
     * @return bool
     */
    public static function deleteFile(string $picture): bool
    {
        return File::delete(self::PATH . '/' . $picture);
    }

    /**
     * @param PosInt $appUserId
     * @param ?non-empty-string $existingFilename
     * @param ?non-empty-string $newUrl
     * @param non-empty-string $uniqueSuffix
     * @return ?non-empty-string
     */
    public function updateIfChanged(int $appUserId, ?string $existingFilename, ?string $newUrl, string $uniqueSuffix): ?string
    {
        if ($existingFilename === null) {

            // No change, and no picture, so return null
            if ($newUrl === null) {
                return null;
            }

            // New picture, so save it
            return $this->update($appUserId, $existingFilename, $newUrl, $uniqueSuffix);
        }

        // User has removed the 3rd-party picture, so delete the file (from disk) and the reference (from the database)
        if ($newUrl === null) {
            $this->remove($appUserId, $existingFilename);
            return null;
        }

        // No change
        if (self::compare($existingFilename, $newUrl, $uniqueSuffix)) {
            return $existingFilename;
        }

        // New picture, so save it
        return $this->update($appUserId, $existingFilename, $newUrl, $uniqueSuffix);
    }

    /**
     * Check if the existing picture `$filename` is equal to the picture provided during this OAuth login.
     *
     * `$filename` is appended by the file extension, so use `str_starts_with()` rather than a direct comparison.
     *
     * @param non-empty-string $filename
     * @param non-empty-string $newUrl
     * @param non-empty-string $uniqueSuffix
     * @return bool
     */
    protected static function compare(string $filename, string $newUrl, string $uniqueSuffix): bool
    {
        return str_starts_with(
            $filename,
            self::createFilename($newUrl, $uniqueSuffix),
        );
    }

    /**
     * @param PosInt $appUserId
     * @param non-empty-string $filename
     * @return void
     */
    protected function remove(int $appUserId, string $filename): void
    {
        $this->appUserRepository->updatePicture($appUserId, null);
        self::deleteFile($filename);
    }

    /**
     * Save a new picture file to disk, update the user's record, and delete the old picture file if there is one.
     *
     * @param PosInt $appUserId
     * @param ?non-empty-string $existingFilename
     * @param non-empty-string $newUrl
     * @param non-empty-string $uniqueSuffix
     * @return ?non-empty-string
     */
    protected function update(int $appUserId, ?string $existingFilename, string $newUrl, string $uniqueSuffix): ?string
    {
        $filename = self::saveFile($newUrl, $uniqueSuffix);
        $this->appUserRepository->updatePicture($appUserId, $filename);

        if ($existingFilename !== null) {
            self::deleteFile($existingFilename);
        }

        return $filename;
    }

    /**
     * @param non-empty-string $url
     * @param non-empty-string $uniqueSuffix
     * @return non-empty-string
     */
    protected static function createFilename(string $url, string $uniqueSuffix): string
    {
        return sha1($url . $uniqueSuffix);
    }
}
