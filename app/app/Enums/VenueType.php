<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Traits\QueryStringConversionsTrait;
use JsonSerializable;

/**
 * @phpstan-type VenueTypeCacheArray list<array{
 *      value: int,
 *      name: non-empty-string,
 *      css_class: non-empty-string,
 * }>
 */
enum VenueType: int implements JsonSerializable
{
    use QueryStringConversionsTrait;

    case Cafe       = 1;
    case Restaurant = 2;
    case Bar        = 3;

    /**
     * Return the CSS class associated with this value
     *
     * @return non-empty-string
     */
    public function cssClass(): string
    {
        return match ($this) {
            self::Cafe          => 'cafe',
            self::Restaurant    => 'restaurant',
            self::Bar           => 'bar',
        };
    }

    /**
     * Return the database column associated with this value
     *
     * @return non-empty-string
     */
    public function dbColumn(): string
    {
        return match ($this) {
            self::Cafe          => 'is_cafe',
            self::Restaurant    => 'is_restaurant',
            self::Bar           => 'is_bar',
        };
    }

    public function jsonSerialize(): int
    {
        return $this->value;
    }

    /**
     * Return an array of values needed by the View class to rended HTML, or to craft the Json response.
     *
     * Using an array instead of an Object allows caching with `json_decode()` which is more efficient than
     * `unserialize()`
     *
     * @param bool $isCafe
     * @param bool $isRestaurant
     * @param bool $isBar
     * @return VenueTypeCacheArray
     */
    public static function getCacheArray(bool $isCafe, bool $isRestaurant, bool $isBar): array
    {
        $array = [];

        if ($isCafe) {
            $array[] = [
                'value' => VenueType::Cafe->value,
                'name' => VenueType::Cafe->name,
                'css_class' => VenueType::Cafe->cssClass(),
            ];
        }

        if ($isRestaurant) {
            $array[] = [
                'value' => VenueType::Restaurant->value,
                'name' => VenueType::Restaurant->name,
                'css_class' => VenueType::Restaurant->cssClass(),
            ];
        }

        if ($isBar) {
            $array[] = [
                'value' => VenueType::Bar->value,
                'name' => VenueType::Bar->name,
                'css_class' => VenueType::Bar->cssClass(),
            ];
        }

        return $array;
    }

    /**
     * Generate a string of value from an array of `VenueTypes`
     * 
     * An empty array results in a string of all `VenueTypes` since this is usually the desired search behaviour.
     * 
     * @param list<VenueType> $types
     * @return non-empty-string
     */
    public static function typesToString(array $types): string
    {
        if (empty($types)) {
            return '123';
        }

        $output = '';

        foreach ($types as $type) {
            $output .= $type->value;
        }

        return $output;
    }
}
