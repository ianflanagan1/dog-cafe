<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Framework\Database\Database;
use App\Framework\Session\Auth;
use App\Framework\Support\StandardTypes;

/**
 * @phpstan-import-type PosInt from StandardTypes
 */
class SuggestRepository
{
    public function __construct(
        protected Database $database,
        protected Auth $auth,
    ) {
    }

    /**
     * @param string $text
     * @return PosInt
     */
    public function add(string $text): int
    {
        return $this->database->insert(
            'INSERT INTO suggest (app_user_id, text)
            VALUES (?, ?)',
            [
                $this->auth->id(),
                $text
            ],
        );
    }
}
