<?php

declare(strict_types=1);

namespace App\Http\Response;

use App\Http\DTOs\ViewParameters;
use App\Types\StandardTypes;

/**
 * @phpstan-import-type PosInt from StandardTypes
 */
class HtmlResponseBuilder
{
    /**
     * @param PosInt $status
     * @return ViewParameters
     */
    public static function error(int $status = 404): ViewParameters
    {
        $canonical = '/' . $status;

        return new ViewParameters(
            'main',
            'error',
            'error',
            [
                'search'    => false,
                'canonical' => $canonical,
            ],
            [
                'canonical' => $canonical,
                'status' => $status,
            ],
            [
                'status' => $status,
            ],
            $status,
        );
    }
}
