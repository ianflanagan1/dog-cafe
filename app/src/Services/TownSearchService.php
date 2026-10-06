<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Town;
use App\Repositories\TownRepository;
use RuntimeException;

/**
 * Service for searching towns by name prefix and formatting search results.
 *
 * @phpstan-import-type TownForJson from Town
 */
readonly class TownSearchService
{
    protected const int SEARCH_MAX_RESULTS = 5;

    public function __construct(protected TownRepository $townRepository)
    {
    }

    /**
     * @param string $query
     * @return list<TownForJson>
     */
    public function search(string $query): array
    {
        $query = preg_replace('/[^a-zA-Z]+/', '', $query);
        //  ^       Not
        //  a-zA-Z  Letter
        //  +       One or more such characters

        if ($query === null) {
            throw new RuntimeException();
        }

        $query = strtolower($query);
        if ($query === '') {
            return [];
        }

        $results = [];

        foreach ($this->townRepository->getAll() as $town) {
            if (str_starts_with($town->nameSearch, $query)) {
                $results[] = $town;
                if (count($results) >= self::SEARCH_MAX_RESULTS) {
                    break;
                }
            }
        }

        return self::formatSearchResults($results);
    }

    /**
     * @param list<Town> $towns
     * @return list<TownForJson>
     */
    protected static function formatSearchResults(array $towns): array
    {
        return array_map(fn (Town $town): array => [
            $town->nameUrl,
            $town->name,
            $town->county,
            $town->venueCount,
        ], $towns);
    }
}
