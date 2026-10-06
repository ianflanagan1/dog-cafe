<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Request;
use App\Utils\Curl;
use App\Utils\Log;

/**
 * @phpstan-type IpLocation array{
 *      countryCode: non-empty-string,
 *      lat: float,
 *      lng: float,
 * }
 */
class IpLocatorService
{
    protected const string FIELDS = 'countryCode,lat,lon'; // Note: lon, not lng

    /**
     * @return ?IpLocation
     */
    public static function find(): ?array
    {
        $ip = Request::ip();

        if ($ip === null) {
            return null;
        }

        $result = Curl::arrayResponse('http://ip-api.com/json/' . $ip . '?fields=' . self::FIELDS);

        if ($result === false) {
            return null;
        }

        // On error, log (if not dev environment) and return null
        if (
            !isset($result['countryCode']) || !isset($result['lat']) || !isset($result['lon'])
            || !is_string($result['countryCode']) || empty($result['countryCode'])
            || !is_float($result['lat']) || !is_float($result['lon'])
        ) {
            if (!self::isDevRequestIp($ip)) {
                Log::warning('IP location failed', $result);
            }

            return null;
        }

        return [
            'countryCode' => $result['countryCode'],
            'lat' => $result['lat'],
            'lng' => $result['lon'], // Note: renaming from lon to lng, for the rest of the application
        ];
    }

    /**
     * @param non-empty-string $countryCode
     * @return bool
     */
    public static function isInServedArea(string $countryCode): bool
    {
        return $countryCode == 'GB'     // England, Scotland, Wales, Northern Ireland
            || $countryCode == 'IM'     // Isle of Man
            || $countryCode == 'JE'     // Jersey
            || $countryCode == 'GG'     // Guernsey
            // || $countryCode == 'IE'    // Ireland
        ;

        // NOTE: When expanding the served area, consider if usage of Geo::getDistanceForOrdinalRanking() is still practical
    }

    /**
     * @param non-empty-string $ip
     * @return bool
     */
    protected static function isDevRequestIp(string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '::1']) ||               // Localhost
            preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $ip) ||  // Docker bridge
            preg_match('/^192\.168\./', $ip) ||                     // Home router network
            preg_match('/^10\./', $ip);                             // Private class A
    }
}




// $ip = '192.41.114.226'; // Edinburgh
// $ip = '194.80.80.148'; // Belfast
// $ip = '195.194.8.235'; // Shetland
// $ip = '92.251.255.11'; // Dublin
// $ip = '178.217.93.1'; // Isle of Man
// $ip = '5.62.84.1'; // Guernsey
// $ip = '109.68.192.1'; // Jersey
// $ip = '2001:0000:130F:0000:0000:09C0:876A:130B'; // up to 45 characters
// $ip = '24.48.0.1';


// Success response (depending on `fields`)

/**
 * array{
 *      status: 'success',
 *      country: string,
 *      countryCode: string,
 *      region: string,
 *      regionName: string,
 *      city: string,
 *      zip: string,
 *      lat: float,
 *      lon: float, // Note: lon, not lng
 *      timezone: string,
 *      isp: string,
 *      org: string,
 *      as: string,
 *      query: string,
 * } $result
 */


// Failure response (depending on `field`)

/**
 * array{
 *      status: 'fail',
 *      message: string,
 *      query: string,
 * } $result
 */


/*
'status' => string 'fail' (length=4)
'message' => string 'private range' (length=13)
'query' => string '172.19.0.1' (length=10)

'status' => string 'success' (length=7)
'country' => string 'United Kingdom' (length=14)
'countryCode' => string 'GB' (length=2)
'region' => string 'SCT' (length=3)
'regionName' => string 'Scotland' (length=8)
'city' => string 'Edinburgh' (length=9)
'zip' => string 'EH8' (length=3)
'lat' => float 55.9477
'lon' => float -3.1624
'timezone' => string 'Europe/London' (length=13)
'isp' => string 'University of Edinburgh' (length=23)
'org' => string 'Edinburgh University' (length=20)
'as' => string 'AS786 Jisc Services Limited' (length=27)
'query' => string '192.41.114.226' (length=14)

'status' => string 'success' (length=7)
'country' => string 'Austria' (length=7)
'countryCode' => string 'AT' (length=2)
'region' => string '9' (length=1)
'regionName' => string 'Vienna' (length=6)
'city' => string 'Vienna' (length=6)
'zip' => string '1010' (length=4)
'lat' => float 48.2049
'lon' => float 16.3662
'timezone' => string 'Europe/Vienna' (length=13)
'isp' => string 'Tefincom S.A.' (length=13)
'org' => string 'Packethub S.A' (length=13)
'as' => string 'AS136787 TEFINCOM S.A.' (length=22)
'query' => string '212.103.61.145' (length=14)

'status' => string 'success' (length=7)
'country' => string 'United States' (length=13)
'countryCode' => string 'US' (length=2)
'region' => string 'MI' (length=2)
'regionName' => string 'Michigan' (length=8)
'city' => string 'Dearborn' (length=8)
'zip' => string '48121' (length=5)
'lat' => float 42.3223
'lon' => float -83.1763
'timezone' => string 'America/Detroit' (length=15)
'isp' => string 'Ford Motor Company' (length=18)
'org' => string 'Ford Motor Company' (length=18)
'as' => string '' (length=0)
'query' => string '2001:0:130f::9c0:876a:130b' (length=26)

'status' => string 'success' (length=7)
'country' => string 'Ireland' (length=7)
'countryCode' => string 'IE' (length=2)
'region' => string 'L' (length=1)
'regionName' => string 'Leinster' (length=8)
'city' => string 'Dublin' (length=6)
'zip' => string 'K36' (length=3)
'lat' => float 53.454
'lon' => float -6.1585
'timezone' => string 'Europe/Dublin' (length=13)
'isp' => string 'Three Ireland (Hutchison) - Infrastructure Services' (length=51)
'org' => string 'Three Ireland (Hutchison) - Infrastructure Services' (length=51)
'as' => string 'AS13280 Three Ireland (Hutchison) limited' (length=41)
'query' => string '92.251.255.11' (length=13)
*/
