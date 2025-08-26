<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use App\Database\SqlWhere;
use App\Models\AppUser;
use App\Types\StandardTypes;

/**
 * @phpstan-import-type PosInt from StandardTypes
 *
 * @phpstan-import-type AppUserArray from AppUser
 */

readonly class AppUserRepository
{
    public function __construct(
        protected Database $database,
        protected GenericRepository $genericRepository,
    ) {
    }

    /**
     * @param non-empty-string $email
     * @param ?non-empty-string $name
     * @param ?non-empty-string $picture
     * @return PosInt
     */
    public function save(string $email, ?string $name = null, ?string $picture = null): int
    {
        return $this->database->insert(
            'INSERT INTO ' . AppUser::TABLE . ' (email, name, picture, created_at) VALUES
            (?, ?, ?, NOW())',
            [
                $email,
                $name,
                $picture,
            ],
        );
    }

    /**
     * @param PosInt $id
     * @return ?AppUser
     */
    public function findById(int $id): ?AppUser
    {
        /** @var ?AppUserArray $res */

        $res = $this->genericRepository->findRowWithSqlWhere(
            'id, email, name, picture',
            AppUser::TABLE,
            new SqlWhere(
                ['active = true', 'id = ?'],
                [$id]
            ),
        );

        return $res === null
            ? null
            : AppUser::fromArray($res);
    }

    /**
     * @param non-empty-string $email
     * @return AppUser|null
     */
    public function findByEmail(string $email): ?AppUser
    {
        /** @var ?AppUserArray $res */

        $res = $this->genericRepository->findRowWithSqlWhere(
            'id, email, name, picture',
            AppUser::TABLE,
            new SqlWhere(
                ['active = true', 'email = ?'],
                [$email]
            ),
        );

        return $res === null
            ? null
            : AppUser::fromArray($res);
    }

    /**
     * @param PosInt $id
     * @param ?non-empty-string $picture
     * @return bool
     */
    public function updatePicture(int $id, ?string $picture): bool
    {
        $rows = $this->database->action(
            'UPDATE ' . AppUser::TABLE . ' SET
            picture = ?
            WHERE id = ?',
            [
                $picture,
                $id,
            ],
        );

        return $rows > 0;
    }

    /**
     * @param PosInt $id
     * @return bool
     */
    public function anonymise(int $id): bool
    {
        $rows = $this->database->action(
            'UPDATE ' . AppUser::TABLE . ' SET
            email = ?,
            name = ?,
            picture = ?,
            active = false
            WHERE id = ?',
            [
                null,
                null,
                null,
                $id,
            ],
        );

        return $rows > 0;
    }
}
