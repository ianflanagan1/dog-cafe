<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\VenueType;
use App\Types\StandardTypes;

/**
 * Constructs canonical and paginated venue list URLs with appropriate query string parameters.
 *
 * @phpstan-import-type NonNegInt from StandardTypes
 */
class VenueRouteBuilderService
{
    /**
     * @param non-empty-string $base
     * @param NonNegInt $totalPages
     * @param NonNegInt $page Can be 0, when building for "last page" from Page 1.
     * @param list<VenueType> $types
     * @param bool $openNow
     * @param ?non-empty-string $town
     * @return ?non-empty-string
     */
    public static function build(string $base, int $totalPages, int $page = 1, array $types = [], bool $openNow = false, ?string $town = null): ?string
    {
        $args = [];

        // Validate page
        if (
            $page < 1                                       // Page is not positive; Occurs when building for "last page" from Page 1
            || ($totalPages > 0 && $page > $totalPages)     // Results exist and Page exceeds total pages
            || ($totalPages < 1 && $page != 1)              // No results and Page is not 1
        ) {
            return null;
        }

        // For URL brevity, Page should not be expressed for its default value (1)
        if ($page != 1) {
            $args['page'] = $page;
        }

        if (!empty($types)) {
            $args['types'] = [];

            foreach ($types as $type) {
                $args['types'][] = strtolower($type->name);
            }
        }

        if ($openNow) {
            $args['open_now'] = '';
        }

        $query = !empty($args)
            ? '?' . urldecode(http_build_query($args))
            : '';

        if ($town) {
            $base .= '/' . $town;
        }

        return $base . $query;
    }
}
