<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Framework\Http\Controller;
use App\Framework\Http\Response;
use App\Framework\Routing\Attributes\Get;
use App\Http\FormRequests\SearchTownRequest;
use App\Services\TownSearchService;

/**
 * @phpstan-import-type SearchTownValidated from SearchTownRequest
 */
final readonly class TownController extends Controller
{
    #[Get('/api/v1/search-town')]
    /**
     * Return a list of towns matching a text search query.
     */
    public function searchTown(SearchTownRequest $request, TownSearchService $townSearchService): Response
    {
        /** @var SearchTownValidated $validated */
        $validated = $request->validate();

        return Response::json(200, [
            'results' => $townSearchService->search($validated['search']),
        ]);
    }
}
