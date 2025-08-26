<?php

declare(strict_types=1);

namespace App\Http\DTOs;

use App\Types\StandardTypes;

/**
 * @phpstan-import-type PosInt from StandardTypes
 */
class ViewParameters
{
    /**
     * @param non-empty-string $layout
     * @param non-empty-string $head
     * @param non-empty-string $body
     * @param array<string, mixed> $layoutParameters
     * @param array<string, mixed> $headParameters
     * @param array<string, mixed> $bodyParameters
     * @param PosInt $status
     */
    public function __construct(
        public string $layout,
        public string $head,
        public string $body,
        public array $layoutParameters = [],
        public array $headParameters = [],
        public array $bodyParameters = [],
        public int $status = 200,
    ) {
    }
}
