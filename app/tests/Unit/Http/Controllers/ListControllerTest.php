<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Controllers;

use App\Framework\Database\Database;
use App\Framework\Database\GenericRepository;
use App\Framework\Errors\NotFoundException;
use App\Framework\Http\HttpMethod;
use App\Framework\Http\Request;
use App\Http\Controllers\ListController;
use App\Http\FormRequests\ListRequest;
use App\Repositories\TownRepository;
use App\Repositories\VenueRepository;
use App\Services\ListVenuePageService;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

final class ListControllerTest extends TestCase
{
    public function test_the_json_list_for_an_unknown_town_is_not_found(): void
    {
        $cache = self::createStub(CacheInterface::class);
        $cache->method('get')->willReturnMap([
            ['townsList', null, json_encode([['name_url_lower' => 'bath']])],
        ]);

        $database = new Database(
            ['driver' => 'pgsql', 'host' => 'localhost', 'port' => 0, 'database' => 'test', 'user' => 'user', 'password' => 'pass'],
            static fn (): PDO => throw new RuntimeException('The database should not be queried'),
        );
        $genericRepository = new GenericRepository($database);

        $this->expectException(NotFoundException::class);

        new ListController()->listJson(
            new ListRequest(new Request(HttpMethod::GET, '/api/v1/search-list/nowhere')),
            new TownRepository($genericRepository, $cache),
            new ListVenuePageService($cache, new VenueRepository($genericRepository)),
            'nowhere',
        );
    }
}
