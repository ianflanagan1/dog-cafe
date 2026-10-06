<?php

declare(strict_types=1);

namespace App\Framework\Http\Response;

use App\Framework\Support\StandardTypes;
use App\Framework\View\ViewParameters;

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
