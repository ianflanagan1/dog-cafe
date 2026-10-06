<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VenueType;
use App\Types\StandardTypes;

/**
 * @phpstan-import-type PosInt from StandardTypes
 * @phpstan-import-type VenueTypeCacheArray from VenueType
 *
 * @phpstan-type VenueMapRaw array{
 *      ext_id: non-empty-string,
 *      lat: float,
 *      lng: float,
 * }
 *
 * @phpstan-type VenueMinimalFromDatabase array{
 *      ext_id: non-empty-string,
 *      name: non-empty-string,
 *      image: ?non-empty-string,
 *      is_cafe: bool,
 *      is_restaurant: bool,
 *      is_bar: bool,
 *      open_0: ?non-empty-string,
 *      close_0: ?non-empty-string,
 *      open_1: ?non-empty-string,
 *      close_1: ?non-empty-string,
 *      open_2: ?non-empty-string,
 *      close_2: ?non-empty-string,
 *      open_3: ?non-empty-string,
 *      close_3: ?non-empty-string,
 *      open_4: ?non-empty-string,
 *      close_4: ?non-empty-string,
 *      open_5: ?non-empty-string,
 *      close_5: ?non-empty-string,
 *      open_6: ?non-empty-string,
 *      close_6: ?non-empty-string,
 * }
 *
 * @phpstan-type VenueMinimalForCache array{
 *      ext_id: non-empty-string,
 *      name: non-empty-string,
 *      image: ?non-empty-string,
 *      types: VenueTypeCacheArray,
 *      open_0: ?non-empty-string,
 *      close_0: ?non-empty-string,
 *      open_1: ?non-empty-string,
 *      close_1: ?non-empty-string,
 *      open_2: ?non-empty-string,
 *      close_2: ?non-empty-string,
 *      open_3: ?non-empty-string,
 *      close_3: ?non-empty-string,
 *      open_4: ?non-empty-string,
 *      close_4: ?non-empty-string,
 *      open_5: ?non-empty-string,
 *      close_5: ?non-empty-string,
 *      open_6: ?non-empty-string,
 *      close_6: ?non-empty-string,
 * }
 *
 * @phpstan-type VenueMinimalForHtml array{
 *      name: non-empty-string,
 *      ext_id: non-empty-string,
 *      types: VenueTypeCacheArray,
 *      image: ?non-empty-string,
 *      open: bool,
 *      change_time: non-empty-string,
 * }
 *
 * @phpstan-type VenueMinimalForJson array{
 *      name: non-empty-string,
 *      ext_id: non-empty-string,
 *      types: list<int>,
 *      image: ?non-empty-string,
 *      open: bool,
 *      change_time: non-empty-string,
 * }
 *
 * @phpstan-type VenueFullFromDatabase array{
 *      id: PosInt,
 *      ext_id: non-empty-string,
 *      name: non-empty-string,
 *      human_url: non-empty-string,
 *      town: non-empty-string,
 *      lat: float,
 *      lng: float,
 *      images: ?non-empty-string,
 *      is_cafe: bool,
 *      is_restaurant: bool,
 *      is_bar: bool,
 *      open_0: ?non-empty-string,
 *      close_0: ?non-empty-string,
 *      open_1: ?non-empty-string,
 *      close_1: ?non-empty-string,
 *      open_2: ?non-empty-string,
 *      close_2: ?non-empty-string,
 *      open_3: ?non-empty-string,
 *      close_3: ?non-empty-string,
 *      open_4: ?non-empty-string,
 *      close_4: ?non-empty-string,
 *      open_5: ?non-empty-string,
 *      close_5: ?non-empty-string,
 *      open_6: ?non-empty-string,
 *      close_6: ?non-empty-string,
 *      street: ?non-empty-string,
 *      area: ?non-empty-string,
 *      locality: ?non-empty-string,
 *      region: ?non-empty-string,
 *      postcode: ?non-empty-string,
 *      public_phone: ?non-empty-string,
 *      website: ?non-empty-string,
 * }
 *
 * @phpstan-type VenueFull array{
 *      id: PosInt,
 *      ext_id: non-empty-string,
 *      name: non-empty-string,
 *      human_url: non-empty-string,
 *      town: non-empty-string,
 *      lat: float,
 *      lng: float,
 *      images: list<non-empty-string>,
 *      is_cafe: bool,
 *      is_restaurant: bool,
 *      is_bar: bool,
 *      fav: 0|1,
 *      types: VenueTypeCacheArray,
 *      openClose: OpenCloseArray,
 *      open_0: ?non-empty-string,
 *      close_0: ?non-empty-string,
 *      open_1: ?non-empty-string,
 *      close_1: ?non-empty-string,
 *      open_2: ?non-empty-string,
 *      close_2: ?non-empty-string,
 *      open_3: ?non-empty-string,
 *      close_3: ?non-empty-string,
 *      open_4: ?non-empty-string,
 *      close_4: ?non-empty-string,
 *      open_5: ?non-empty-string,
 *      close_5: ?non-empty-string,
 *      open_6: ?non-empty-string,
 *      close_6: ?non-empty-string,
 *      street: ?non-empty-string,
 *      area: ?non-empty-string,
 *      locality: ?non-empty-string,
 *      region: ?non-empty-string,
 *      postcode: ?non-empty-string,
 *      public_phone: ?non-empty-string,
 *      website: ?non-empty-string,
 * }
 *
 * @phpstan-type OpenCloseArray array{
 *      0: OpenClose,
 *      1: OpenClose,
 *      2: OpenClose,
 *      3: OpenClose,
 *      4: OpenClose,
 *      5: OpenClose,
 *      6: OpenClose,
 * }
 *
 * @phpstan-type OpenClose array{
 *      day: string,
 *      time: string
 * }
 */
class Venue extends Model
{
    public const string TABLE = 'venue';
    public const int EXT_ID_LENGTH = 22;
    public const string COLUMN_PREFIX_OPEN_TIME = 'open_';
    public const string COLUMN_PREFIX_CLOSE_TIME = 'close_';

    public static function stringMightBeExtId(string $value): bool
    {
        return strlen($value) == Venue::EXT_ID_LENGTH && ctype_alnum($value);
    }

    public static function stringMightBeHumanUrl(string $value): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9-]+$/', $value);
        //  ^           Start of string
        //  a-zA-Z0-9   Alpha-numeric character
        //  -           Literal (hyphen)
        //  +           One or more such characters
        //  $           End of string
    }
}
