<?php

declare(strict_types=1);

namespace App\Http\DTOs;

use App\Enums\VenueType;
use App\Exceptions\RouteNotFoundException;
use App\Services\VenueRouteBuilderService;
use App\Types\StandardTypes;

/**
 * @phpstan-import-type PosInt from StandardTypes
 * @phpstan-import-type NonNegInt from StandardTypes
 */
readonly class ListPageUrls
{
    /**
     * Immutable value object representing the canonical, previous, and next page URLs for a paginated venue listing.
     *
     * @param non-empty-string $canonical
     * @param ?non-empty-string $prevPageUrl
     * @param ?non-empty-string $nextPageUrl
     */
    public function __construct(
        public string $canonical,
        public ?string $prevPageUrl,
        public ?string $nextPageUrl,
    ) {
    }

    /**
     * @param non-empty-string $base
     * @param NonNegInt $totalPages
     * @param PosInt $page
     * @param list<VenueType> $types
     * @param bool $openNow
     * @param ?non-empty-string $townNameUrl
     * @return ListPageUrls
     */
    public static function from(
        string $base,
        int $totalPages,
        int $page = 1,
        array $types = [],
        bool $openNow = false,
        ?string $townNameUrl = null,
    ): self {
        $canonical = VenueRouteBuilderService::build(
            $base,
            $totalPages,
            $page,
            $types,
            $openNow,
            $townNameUrl
        );

        if ($canonical === null) {
            throw new RouteNotFoundException();
        }

        $prev = VenueRouteBuilderService::build(
            $base,
            $totalPages,
            $page - 1,
            $types,
            $openNow,
            $townNameUrl
        );

        $next = VenueRouteBuilderService::build(
            $base,
            $totalPages,
            $page + 1,
            $types,
            $openNow,
            $townNameUrl
        );

        return new self($canonical, $prev, $next);
    }
}
