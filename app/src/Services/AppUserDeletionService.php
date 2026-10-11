<?php

declare(strict_types=1);

namespace App\Services;

use App\Framework\Session\Auth;
use App\Framework\Support\Log;
use App\Repositories\AppUserRepository;

/**
 * Service responsible for handling self-service user account deletion and anonymisation.
 */
final readonly class AppUserDeletionService
{
    public function __construct(
        private Auth $auth,
        private AppUserRepository $appUserRepository,
    ) {
    }

    public function handle(): bool
    {
        $appUserId = $this->auth->id();

        if ($appUserId === null) {
            Log::error('$appUserId is null');
            return false;
        }

        $appUser = $this->appUserRepository->findById($appUserId);

        if ($appUser === null) {
            Log::error('$appUser is null');
            return false;
        }

        if ($appUser->picture !== null) {
            AppUserPictureService::deleteFile($appUser->picture);
        }

        $this->appUserRepository->anonymise($appUserId);
        $this->auth->logout();

        return true;
    }
}
