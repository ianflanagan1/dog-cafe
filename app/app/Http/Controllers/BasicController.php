<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Attributes\Get;
use App\Http\Response\Response;

readonly class BasicController extends Controller
{
    #[Get('/docs')]
    /**
     * Redirect to external documentation.
     */
    public function docs(): void
    {
        Response::redirect(
            'https://guiltless-pheasant-a99.notion.site/URLS-5bb81775fcbd4fbdac1910142f785f9f',
            301,
        );
    }
}
