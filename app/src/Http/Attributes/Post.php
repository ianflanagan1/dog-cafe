<?php

declare(strict_types=1);

namespace App\Http\Attributes;

use App\Http\Enums\HttpMethod;
use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
readonly class Post extends Route
{
    /**
     * @param non-empty-string $path
     */
    public function __construct(string $path)
    {
        parent::__construct($path, HttpMethod::POST);
    }
}
