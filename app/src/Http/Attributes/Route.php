<?php

declare(strict_types=1);

namespace App\Http\Attributes;

use App\Http\Enums\HttpMethod;
use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
readonly class Route
{
    /**
     * @param non-empty-string $path
     * @param HttpMethod $method
     */
    public function __construct(
        public string $path,
        public HttpMethod $method = HttpMethod::GET
    ) {
    }
}
