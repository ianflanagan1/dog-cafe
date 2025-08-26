<?php

declare(strict_types=1);

namespace Tests\Unit\Routing;

use Exception;
use App\Container;
use App\RedisCache;
use App\Http\Attributes\Get;
use App\Http\Attributes\Post;
use App\Http\Enums\HttpMethod;
use App\Routing\RouteRegistry;
use App\Http\Attributes\Delete;
use App\Routing\ActionExecutor;
use PHPUnit\Framework\TestCase;
use Tests\Traits\ConstantAccessTrait;
use App\Exceptions\RouteConflictException;
use App\Exceptions\RouteNotFoundException;
use App\Exceptions\RouteNotValidException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @phpstan-import-type Actionable from ActionExecutor
 * @phpstan-import-type ControllerAction from RouteRegistry
 */
class RouteRegistryTest extends TestCase
{
    use ConstantAccessTrait;
    
    protected RouteRegistry $routeRegistry;
    /** @var MockObject&Container */
    protected MockObject&Container $containerMock;
    /** @var MockObject&RedisCache */
    protected MockObject&RedisCache $cacheMock;
    protected string $CACHE_KEY_ROUTES;
    protected string $CACHE_KEY_ACTIONS;

    protected function setUp(): void
    {
        $this->cacheMock = $this->createMock(RedisCache::class);
        $this->routeRegistry = new RouteRegistry($this->cacheMock);

        // Get protected class constants
        /** @var array{0: non-empty-string, 1: non-empty-string} $result */
        $result = self::getConstantValue(RouteRegistry::class, ['CACHE_KEY_ROUTES', 'CACHE_KEY_ACTIONS']);

        [$this->CACHE_KEY_ROUTES, $this->CACHE_KEY_ACTIONS] = $result;
    }

    /**
     * @return array<
     *   non-empty-string,
     *   array<
     *     non-empty-string
     *   >
     * >
     */
    public static function cases_to_test_registering_invalid_route_throws(): array
    {
        return [
            'Trailing slash (segment)'          => ['/abc/def/'],
            'Trailing slash (parameter)'        => ['/abc/:def/'],
            'No leading slash (one segment)'    => ['abc'],
            'No leading slash (two segments)'   => ['abc/def'],
            'No leading slash (one parameter)'  => [':abc'],
            'No leading slash (two parameters)' => [':abc/:def'],
            'No leading slash (mixed 1)'        => [':abc/def'],
            'No leading slash (mixed 2)'        => ['abc/:def'],
            'Double slash (only)'               => ['//'],
            'Double slash (at start)'           => ['//abc'],
            'Double slash (midway)'             => ['/abc//def'],
            'Double slash (at end)'             => ['/abc/def//'],
        ];
    }

    /**
     * @param non-empty-string $route
     */
    #[DataProvider('cases_to_test_registering_invalid_route_throws')]
    public function test_registering_invalid_route_throws(string $route): void
    {
        $class = $this->makeDynamicController($route);
        $this->expectException(RouteNotValidException::class);
        $this->routeRegistry->start([$class]);
    }

    /**
     * @return array<
     *   non-empty-string,
     *   array<
     *     non-empty-string
     *   >
     * >
     */
    public static function cases_to_test_registering_route_with_invalid_segment_name_throws(): array
    {
        return [
            'Parameter with no name'            => [':'],
            'Parameter stating with a number'   => [':2foo'],
            'Parameter with a dash'             => [':foo-bar'],
            'Parameter with an asterisk'        => [':foo*bar'],
            'Parameter with a tilde'            => [':foo~bar'],
            'Parameter with a dot'              => [':foo.bar'],
            'Parameter with a bracket'          => [':foo(bar'],
            'Parameter with a comma'            => [':foo,bar'],
            'Parameter with a colon (midway)'   => [':foo:bar'],
            'Parameter with a plus sign'        => [':foo+bar'],
            'Parameter with a percent sign'     => [':foo%bar'],
            'Parameter with a at sign'          => [':foo@bar'],
            'Parameter with a equals sign'      => [':foo=bar'],
            'Parameter with a back slash'       => [':foo\bar'],

            'Segment with a bracket'            => ['foo(bar'],
            'Segment with a comma'              => ['foo,bar'],
            'Segment with a colon (midway)'     => ['foo:bar'],
            'Segment with a plus sign'          => ['foo+bar'],
            'Segment with a percent sign'       => ['foo%bar'],
            'Segment with a at sign'            => ['foo@bar'],
            'Segment with a equals sign'        => ['foo=bar'],
            'Segment with a back slash'         => ['foo\bar'],
        ];
    }

    #[DataProvider('cases_to_test_registering_route_with_invalid_segment_name_throws')]
    public function test_registering_route_with_invalid_segment_name_throws(string $segmentName): void
    {
        foreach ([
            "/{$segmentName}",
            "/abc/{$segmentName}",
            "/{$segmentName}/def",
            "/abc/{$segmentName}/def",
        ] as $route) {
            $class = $this->makeDynamicController($route);
            $this->expectException(RouteNotValidException::class);
            $this->routeRegistry->start([$class]);
        }
    }

    /**
     * @return array<
     *   non-empty-string,
     *   array<
     *     non-empty-string
     *   >
     * >
     */
    public static function cases_to_test_registering_conflicting_routes_throws()
    {
        return [
            'Conflicting parameter (at start)'      => ['/:abc'],
            'Conflicting parameter (midway)'        => ['/abc/:def/ghi'],
            'Conflicting parameter (at end)'        => ['/abc/def/:ghi'],
            'Conflicting segment routes (single)'   => ['/abc'],
            'Conflicting segment routes (double)'   => ['/abc/def'],
        ];
    }

    /**
     * @param non-empty-string $route
     */
    #[DataProvider('cases_to_test_registering_conflicting_routes_throws')]
    public function test_registering_conflicting_routes_throws(string $route): void
    {
        $class1 = $this->makeDynamicController($route);
        $class2 = $this->makeDynamicController($route);

        $this->expectException(RouteConflictException::class);
        $this->routeRegistry->start([$class1, $class2]);
    }

    public function test_resolving_non_existent_route_without_cache_throws(): void
    {
        // Simulate empty cache to force route registration
        $this->cacheMock
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [$this->CACHE_KEY_ROUTES, null, null],      // $key, $default, return value
                [$this->CACHE_KEY_ACTIONS, null, null],
            ]);

        // Assert that routes and actions will be cached
        $routesKey = $this->CACHE_KEY_ROUTES;
        $actionsKey = $this->CACHE_KEY_ACTIONS;

        $this->cacheMock
            ->expects($this->exactly(2))
            ->method('set')
            ->willReturnCallback(function ($key, $value, $ttl) use ($routesKey, $actionsKey): bool {
                $expected = [
                    $routesKey  => json_encode([]),
                    $actionsKey => json_encode([]),
                ];

                if (!array_key_exists($key, $expected)) {
                    self::fail("Cache key not expected: {$key}");
                }

                if ($value !== $expected[$key]) {
                    self::fail("Unexpected value for `{$key}`: " . var_export($value, true) . ' instead of ' . var_export($expected[$key], true));
                }

                return true;
            });

        $this->routeRegistry->start([]);
        $this->expectException(RouteNotFoundException::class);
        $this->routeRegistry->resolve('/non-existent', HttpMethod::GET);
    }

    public function test_resolving_non_existent_route_from_cache_throws(): void
    {
        $this->cacheMock
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [$this->CACHE_KEY_ROUTES, null, json_encode([])],
                [$this->CACHE_KEY_ACTIONS, null, json_encode([])],
            ]);

        $this->routeRegistry->start([]);
        $this->expectException(RouteNotFoundException::class);
        $this->routeRegistry->resolve('/non-existent', HttpMethod::GET);
    }

    public function test_resolve_existing_route_with_wrong_method_without_cache_throws(): void
    {
        // Simulate empty cache to force route registration
        $this->cacheMock
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [$this->CACHE_KEY_ROUTES, null, null],      // $key, $default, return value
                [$this->CACHE_KEY_ACTIONS, null, null],
            ]);

        $this->routeRegistry->start([SingleMethodWithMultipleMethodsController::class]);
        $this->expectException(RouteNotFoundException::class);
        $this->routeRegistry->resolve('/abc', HttpMethod::PUT);
    }

    public function test_resolve_existing_route_with_wrong_method_from_cache_throws(): void
    {
        $routes = [
            'seg' => [
                'abc' => [
                    'get' => 0,
                ],
            ],
        ];

        $actions = [[SingleMethodWithMultipleMethodsController::class, 'get']];

        $this->cacheMock
            ->method('get')
            ->willReturnMap([
                [$this->CACHE_KEY_ROUTES, null, json_encode($routes)],
                [$this->CACHE_KEY_ACTIONS, null, json_encode($actions)],
            ]);

        $this->routeRegistry->start([]);
        $this->expectException(RouteNotFoundException::class);
        $this->routeRegistry->resolve('/abc', HttpMethod::POST);
    }

    /**
     * @return array<
     *      non-empty-string,
     *      array{
     *          controllers: list<class-string>,
     *          routes: array<mixed>,
     *          actions: list<ControllerAction>,
     *          routesToResolve: list<array{
     *              uri: non-empty-string,
     *              method: HttpMethod,
     *              expected: Actionable|Exception
     *          }>
     *      }
     * >
     */
    public static function cases_to_test_valid_routes(): array
    {
        return [

            // #[Get('/abc')]
            // #[Post('/abc')]
            // #[Delete('/abc')]

            'Same route with different methods' => [
                'controllers' => [SingleMethodWithMultipleMethodsController::class],
                'routes' => [
                    'seg' => [
                        'abc' => [
                            'get' => 0,
                            'post' => 1,
                            'delete' => 2,
                        ],
                    ],
                ],
                'actions' => [
                    [SingleMethodWithMultipleMethodsController::class, 'get'],
                    [SingleMethodWithMultipleMethodsController::class, 'post'],
                    [SingleMethodWithMultipleMethodsController::class, 'delete'],
                ],
                'routesToResolve' => [
                    [
                        'uri'       => '/abc',
                        'method'    => HttpMethod::GET,
                        'expected'  => [
                            SingleMethodWithMultipleMethodsController::class,
                            'get',
                            [],
                        ],
                    ],
                    [
                        'uri'       => '/abc',
                        'method'    => HttpMethod::POST,
                        'expected'  => [
                            SingleMethodWithMultipleMethodsController::class,
                            'post',
                            [],
                        ],
                    ],
                    [
                        'uri'       => '/abc',
                        'method'    => HttpMethod::DELETE,
                        'expected'  => [
                            SingleMethodWithMultipleMethodsController::class,
                            'delete',
                            [],
                        ],
                    ],
                ],
            ],

            ///////////////////////////////////////////////////////////////////////////////////

            // #[Get('/')]
            // #[Get('/abc')]
            // #[Get('/abc/def')]
            // #[Get('/123/456/789')]

            'Same route with multiple methods' => [
                'controllers' => [SegmentRouteMethodWithMultipleRoutesController::class],
                'routes' => [
                    'seg' => [
                        '' => [
                            'get' => 0,
                        ],
                        'abc' => [
                            'get' => 0,
                            'seg' => [
                                'def' => [
                                    'get' => 0,
                                ],
                            ],
                        ],
                        '123' => [
                            'seg' => [
                                '456' => [
                                    'seg' => [
                                        '789' => [
                                            'get' => 0,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'actions' => [
                    [SegmentRouteMethodWithMultipleRoutesController::class, 'get'],
                ],
                'routesToResolve' => [
                    [
                        'uri'       => '/',
                        'method'    => HttpMethod::GET,
                        'expected'  => [
                            SegmentRouteMethodWithMultipleRoutesController::class,
                            'get',
                            [],
                        ],
                    ],
                    [
                        'uri'       => '/abc',
                        'method'    => HttpMethod::GET,
                        'expected'  => [
                            SegmentRouteMethodWithMultipleRoutesController::class,
                            'get',
                            [],
                        ],
                    ],
                    [
                        'uri'       => '/abc/def',
                        'method'    => HttpMethod::GET,
                        'expected'  => [
                            SegmentRouteMethodWithMultipleRoutesController::class,
                            'get',
                            [],
                        ],
                    ],
                    [
                        'uri'       => '/123/456/789',
                        'method'    => HttpMethod::GET,
                        'expected'  => [
                            SegmentRouteMethodWithMultipleRoutesController::class,
                            'get',
                            [],
                        ],
                    ],
                ],
            ],

            ///////////////////////////////////////////////////////////////////////////////////

            // #[Get('/:para1')]
            // #[Get('/abc/:para1')]
            // #[Get('/:para1/def')]
            // #[Get('/ghi/:para1/jkl')]

            'Parameter route with multiple routes' => [
                'controllers' => [ParameterRouteMethodWithMultipleRoutesController::class],
                'routes' => [
                    'par' => [
                        'name' => 'para1',
                        'get' => 0,
                        'seg' => [
                            'def' => [
                                'get' => 0,
                            ],
                        ],
                    ],
                    'seg' => [
                        'abc' => [
                            'par' => [
                                'name' => 'para1',
                                'get' => 0,
                            ],
                        ],
                        'ghi' => [
                            'par' => [
                                'name' => 'para1',
                                'seg' => [
                                    'jkl' => [
                                        'get' => 0,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'actions' => [
                    [ParameterRouteMethodWithMultipleRoutesController::class, 'get'],
                ],
                'routesToResolve' => [
                    [
                        'uri'       => '/',
                        'method'    => HttpMethod::GET,
                        'expected'  => [
                            ParameterRouteMethodWithMultipleRoutesController::class,
                            'get',
                            ['para1' => ''],
                        ],
                    ],
                    [
                        'uri'       => '/123',
                        'method'    => HttpMethod::GET,
                        'expected'  => [
                            ParameterRouteMethodWithMultipleRoutesController::class,
                            'get',
                            ['para1' => '123'],
                        ],
                    ],
                    [
                        'uri'       => '/abc',
                        'method'    => HttpMethod::GET,
                        'expected'  => [
                            ParameterRouteMethodWithMultipleRoutesController::class,
                            'get',
                            ['para1' => ''],
                        ],
                    ],
                    [
                        'uri'       => '/abc/123',
                        'method'    => HttpMethod::GET,
                        'expected'  => [
                            ParameterRouteMethodWithMultipleRoutesController::class,
                            'get',
                            ['para1' => '123'],
                        ],
                    ],
                    [
                        'uri'       => '//def',
                        'method'    => HttpMethod::GET,
                        'expected'  => [
                            ParameterRouteMethodWithMultipleRoutesController::class,
                            'get',
                            ['para1' => ''],
                        ],
                    ],
                    [
                        'uri'       => '/123/def',
                        'method'    => HttpMethod::GET,
                        'expected'  => [
                            ParameterRouteMethodWithMultipleRoutesController::class,
                            'get',
                            ['para1' => '123'],
                        ],
                    ],
                    [
                        'uri'       => '/ghi//jkl',
                        'method'    => HttpMethod::GET,
                        'expected'  => [
                            ParameterRouteMethodWithMultipleRoutesController::class,
                            'get',
                            ['para1' => ''],
                        ],
                    ],
                    [
                        'uri'       => '/ghi/123/jkl',
                        'method'    => HttpMethod::GET,
                        'expected'  => [
                            ParameterRouteMethodWithMultipleRoutesController::class,
                            'get',
                            ['para1' => '123'],
                        ],
                    ],
                ],
            ],

            ///////////////////////////////////////////////////////////////////////////////////

            // #[Get('/abc/def/ghi/jkl/mno/pqr/stu/vwx/:para1')]

            'Long route controller' => [
                'controllers' => [LongRouteController::class],
                'routes' => [
                    'seg' => [
                        'abc' => [
                            'seg' => [
                                'def' => [
                                    'seg' => [
                                        'ghi' => [
                                            'seg' => [
                                                'jkl' => [
                                                    'seg' => [
                                                        'mno' => [
                                                            'seg' => [
                                                                'pqr' => [
                                                                    'seg' => [
                                                                        'stu' => [
                                                                            'seg' => [
                                                                                'vwx' => [
                                                                                    'par' => [
                                                                                        'name' => 'para1',
                                                                                        'get' => 0,
                                                                                    ],
                                                                                ],
                                                                            ],
                                                                        ],
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'actions' => [
                    [LongRouteController::class, 'get'],
                ],
                'routesToResolve' => [
                    [
                        'uri'       => '/abc/def/ghi/jkl/mno/pqr/stu/vwx/123',
                        'method'    => HttpMethod::GET,
                        'expected'  => [
                            LongRouteController::class,
                            'get',
                            ['para1' => '123'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param list<class-string> $controllers
     * @param array<mixed> $routes
     * @param list<ControllerAction> $actions
     * @param list<array{
     *      uri: non-empty-string,
     *      method: HttpMethod,
     *      expected: Actionable|Exception
     * }> $routesToResolve
     */
    #[DataProvider('cases_to_test_valid_routes')]
    public function test_valid_routes_are_registered_and_resolved_without_cache(array $controllers, array $routes, array $actions, array $routesToResolve): void
    {
        // Simulate empty cache to force route registration
        $this->cacheMock
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [$this->CACHE_KEY_ROUTES, null, null],      // $key, $default, return value
                [$this->CACHE_KEY_ACTIONS, null, null],
            ]);

        // Assert that routes and actions will be cached
        $routesKey = $this->CACHE_KEY_ROUTES;
        $actionsKey = $this->CACHE_KEY_ACTIONS;

        $this->cacheMock
            ->expects($this->exactly(2))
            ->method('set')
            ->willReturnCallback(function ($key, $value, $ttl) use ($routesKey, $actionsKey, $routes, $actions): bool {
                $expected = [
                    $routesKey  => json_encode($routes),
                    $actionsKey => json_encode($actions),
                ];

                if (!array_key_exists($key, $expected)) {
                    self::fail("Cache key not expected: {$key}");
                }

                if ($value !== $expected[$key]) {
                    self::fail("Unexpected value for `{$key}`: " . var_export($value, true) . ' instead of ' . var_export($expected[$key], true));
                }

                return true;
            });

        $this->routeRegistry->start($controllers);

        // Test routes
        foreach ($routesToResolve as $route) {

            if ($route['expected'] instanceof Exception) { // todo: remove?
                $this->expectException(get_class($route['expected']));
            }

            $actionable = $this->routeRegistry->resolve($route['uri'], $route['method']);

            if (!$route['expected'] instanceof Exception) {
                $this->assertSame($route['expected'], $actionable);
            }
        }
    }

    /**
     * @param list<class-string> $controllers
     * @param array<mixed> $routes
     * @param list<ControllerAction> $actions
     * @param list<array{
     *      uri: non-empty-string,
     *      method: HttpMethod,
     *      expected: Actionable|Exception
     * }> $routesToResolve
     */
    #[DataProvider('cases_to_test_valid_routes')]
    public function test_valid_routes_are_resolved_from_cache(array $controllers, array $routes, array $actions, array $routesToResolve): void
    {
        // Simulate cache retrieval
        $this->cacheMock
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [$this->CACHE_KEY_ROUTES, null, json_encode($routes)],      // $key, $default, return value
                [$this->CACHE_KEY_ACTIONS, null, json_encode($actions)],
            ]);

        // Pass empty $controllers array to ensure successful cache retrieval overrules passed $controllers array
        $this->routeRegistry->start([]);

        // Test routes
        foreach ($routesToResolve as $route) {

            if ($route['expected'] instanceof Exception) { // todo: remove?
                $this->expectException(get_class($route['expected']));
            }

            $actionable = $this->routeRegistry->resolve($route['uri'], $route['method']);

            if (!$route['expected'] instanceof Exception) {
                $this->assertSame($route['expected'], $actionable);
            }
        }
    }

    /**
     * @param non-empty-string $route
     * @return class-string
     */
    protected function makeDynamicController(string $route): string
    {
        $className = 'TestController_' . md5(uniqid());

        $classCode = <<<PHP
            namespace DynamicTest;
            use App\Http\Attributes\Get;
            class {$className} {
                #[Get('$route')]
                public function get(): void {}
            }
        PHP;

        eval($classCode);

        /** @var class-string $output */
        $output = "DynamicTest\\{$className}";

        return $output;
    }
}

class SingleMethodWithMultipleMethodsController
{
    #[Get('/abc')]
    public function get(): void
    {
    }

    #[Post('/abc')]
    public function post(): void
    {
    }

    #[Delete('/abc')]
    public function delete(): void
    {
    }
}

class SegmentRouteMethodWithMultipleRoutesController
{
    #[Get('/')]
    #[Get('/abc')]
    #[Get('/abc/def')]
    #[Get('/123/456/789')]
    public function get(): void
    {
    }
}

class ParameterRouteMethodWithMultipleRoutesController
{
    #[Get('/:para1')]
    #[Get('/abc/:para1')]
    #[Get('/:para1/def')]
    #[Get('/ghi/:para1/jkl')]
    public function get(string $para1): void
    {
    }
}

class LongRouteController
{
    #[Get('/abc/def/ghi/jkl/mno/pqr/stu/vwx/:para1')]
    public function get(string $para1): void
    {
    }
}
