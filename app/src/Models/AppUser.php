<?php

declare(strict_types=1);

namespace App\Models;

use App\Framework\Database\Model;
use App\Framework\Support\StandardTypes;

/**
 * @phpstan-import-type PosInt from StandardTypes
 *
 * @phpstan-type AppUserArray array{
 *     id: PosInt,
 *     email: non-empty-string,
 *     name: ?non-empty-string,
 *     picture: ?non-empty-string
 * }
 */
final class AppUser extends Model
{
    public const string TABLE = 'app_user';

    /**
     * @param PosInt $id
     * @param non-empty-string $email
     * @param ?non-empty-string $name
     * @param ?non-empty-string $picture
     */
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly ?string $name,
        public readonly ?string $picture,
    ) {
    }

    /**
     * @param AppUserArray $array
     * @return self
     */
    public static function fromArray(array $array): self
    {
        return new self(
            id:         $array['id'],
            email:      $array['email'],
            name:       $array['name'],
            picture:    $array['picture'],
        );
    }
}
