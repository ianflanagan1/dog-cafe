<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Attributes\Get;
use App\Http\FormRequests\SearchTownRequest;
use App\Http\Response\Response;
use App\Services\TownSearchService;

/**
 * @phpstan-import-type SearchTownValidated from SearchTownRequest
 */
readonly class TownController extends Controller
{
    #[Get('/api/v1/search-town')]
    /**
     * Return a list of towns matching a text search query.
     */
    public function searchTown(TownSearchService $townSearchService): string
    {
        /** @var SearchTownValidated $validated */
        $validated = SearchTownRequest::validate();

        return Response::json([
            'results' => $townSearchService->search($validated['search']),
        ], 200);
    }
}
