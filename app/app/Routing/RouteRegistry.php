<?php

declare(strict_types=1);

namespace App\Routing;

use ReflectionClass;
use ReflectionAttribute;
use App\Types\StandardTypes;
use App\Http\Attributes\Route;
use App\Http\Enums\HttpMethod;
use Psr\SimpleCache\CacheInterface;
use App\Exceptions\RouteConflictException;
use App\Exceptions\RouteNotFoundException;
use App\Exceptions\RouteNotValidException;

/**
 * Handles HTTP routing by resolving paths to controller methods using attributes.
 *
 * Builds a `routes` tree from scanning controller classes for #[Route] attributes, and directs
 * incoming requests to the correct `action` (controller and method).
 *
 * Currently the `routes` tree is an array without objects. This increases the development complexity
 * (especially concerning recursive type checking), but reduces the time to read from cache on each
 * request. If the complexity becomes unmanageable, we can introduce a Segment class.
 *
 * @phpstan-import-type NonNegInt from StandardTypes
 * @phpstan-import-type Actionable from ActionExecutor
 *
 * @phpstan-type RouteSegment array{
 *      seg: array<string, array{
 *          seg?: array<string, mixed>,
 *          par?: mixed,
 *          get?: NonNegInt,
 *          post?: NonNegInt,
 *      }>,
 *      par?: RouteParameter,
 *      get?: NonNegInt,
 *      post?: NonNegInt,
 * }
 *
 * @phpstan-type RouteParameter array{
 *      name: string,
 *      seg: array<string, array{
 *          seg?: array<string, mixed>,
 *          par?: mixed,
 *          get?: NonNegInt,
 *          post?: NonNegInt,
 *      }>,
 *      par?: array{
 *          name: string,
 *          seg?: array<string, mixed>,
 *          par?: mixed,
 *          get?: NonNegInt,
 *          post?: NonNegInt,
 *      },
 *      get?: NonNegInt,
 *      post?: NonNegInt,
 * }
 *
 * @phpstan-type ControllerAction array{
 *      0: class-string,
 *      1: non-empty-string
 * }
 */

class RouteRegistry
{
    protected const string CACHE_KEY_ROUTES  = 'routerRoutes';
    protected const string CACHE_KEY_ACTIONS = 'routerActions';

    /**
     * Parameter name must be a colon followed by a valid PHP variable name
     * 
     * :            Colon
     * [A-Za-z_]    One alphabetic or underscore
     * [A-Za-z0-9_] Zero or more alpha-number or underscore
     */
    protected const string PARAMETER_ALLOWED = ':[A-Za-z_][A-Za-z0-9_]*';

    /**
     * Segment name must be alpha-numeric or . _ ~ -
     * 
     * A-Za-z0-9    Alpha-numeric
     * ._~-         Literals
     * +            One or more such characters
     */
    protected const string SEGMENT_ALLOWED = '[A-Za-z0-9._~-]+';

    /** @var RouteSegment $routes */
    protected array $routes;

    /** @var list<ControllerAction> */
    protected array $actions;

    public function __construct(
        protected CacheInterface $cache,
    ) {
    }

    /**
     * @param non-empty-string $requestUri
     * @param HttpMethod $httpMethod
     * @return Actionable
     */
    public function resolve(string $requestUri, HttpMethod $httpMethod): array
    {
        $route = explode('?', $requestUri)[0];
        $segments = explode('/', $route);

        if ($segments[0] !== '') {
            throw new RouteNotFoundException();
        }

        array_shift($segments);

        if (empty($segments)) {
            throw new RouteNotFoundException();
        }

        $parameters = [];

        $actionIndex = $this->resolveRecursive(
            $segments,
            $this->routes,
            $httpMethod,
            $parameters
        );

        if ($actionIndex === null) {
            throw new RouteNotFoundException();
        }

        [$class, $method] = $this->actions[$actionIndex];

        return [$class, $method, $parameters];
    }

    /**
     * @param list<class-string> $controllers
     */
    public function start(array $controllers = []): void
    {
        $routesCache = $this->cache->get(self::CACHE_KEY_ROUTES);
        $actionsCache = $this->cache->get(self::CACHE_KEY_ACTIONS);

        if (is_string($routesCache) && is_string($actionsCache)) {
            $routesCache = json_decode($routesCache, true);
            $actionsCache = json_decode($actionsCache, true);

            if (is_array($routesCache) && is_array($actionsCache)) {
                /** @var RouteSegment $routesCache */
                /** @var list<ControllerAction> $actionsCache */

                $this->routes = $routesCache;
                $this->actions = $actionsCache;
                return;
            }
        }

        // Failed to retrieve `routes` and `actions` from cache
        $this->registerFromAttributes($controllers);
    }

    /**
     * @param list<class-string> $controllers
     */
    protected function registerFromAttributes(array $controllers): void
    {
        $routes = [];
        $actions = [];

        foreach ($controllers as $controller) {
            $reflectionClass = new ReflectionClass($controller);

            foreach ($reflectionClass->getMethods() as $method) {
                $routeAttributes = $method->getAttributes(Route::class, ReflectionAttribute::IS_INSTANCEOF);

                if (!empty($routeAttributes)) {
                    $actions[] = [$controller, $method->getName()];
                    $actionIndex = count($actions) - 1;

                    foreach ($routeAttributes as $routeAttribute) {
                        $route = $routeAttribute->newInstance();

                        $this->registerRoute(
                            $route->path,
                            $route->method,
                            $actionIndex,
                            $routes
                        );
                    }
                }
            }
        }

        $this->routes = $routes;
        $this->actions = $actions;

        $this->cache->set(self::CACHE_KEY_ROUTES, json_encode($routes));
        $this->cache->set(self::CACHE_KEY_ACTIONS, json_encode($actions));
    }

    /**
     * @param non-empty-string $route
     * @param HttpMethod $httpMethod
     * @param NonNegInt $actionIndex
     * @param RouteSegment|RouteParameter $routes
     * @return void
     */
    protected function registerRoute(string $route, HttpMethod $httpMethod, int $actionIndex, array &$routes): void
    {
        $segments = explode('/', $route);

        // Must start with a forward slash
        if ($segments[0] !== '') {
            throw new RouteNotValidException();
        }

        array_shift($segments);

        if (empty($segments)) {
            throw new RouteNotValidException();
        }

        foreach($segments as $segment) {
            if (!preg_match('/^(' . self::PARAMETER_ALLOWED . '|' . self::SEGMENT_ALLOWED . ')$/', $segment) && $segments !== ['']) {
                    // Empty string fails       -> Can't have double slashes in route; can't end with a slash
                    //                          -> But allow a single empty-string segment
                    // Single colon ":" fails   -> Parameters must have names

                throw new RouteNotValidException();
            }
        }

        self::registerRouteRecursive(
            $segments,
            $httpMethod,
            $actionIndex,
            $routes,
        );
    }

    /**
     * @param non-empty-list<string> $segments
     * @param HttpMethod $httpMethod
     * @param NonNegInt $actionIndex
     * @param RouteSegment|RouteParameter $routes
     * @return void
     */
    protected static function registerRouteRecursive(array $segments, HttpMethod $httpMethod, int $actionIndex, array &$routes): void
    {
        $segment = $segments[0];

        if (self::isSegmentParameter($segment)) {
            self::registerParameter(
                $segments,
                $httpMethod,
                $actionIndex,
                $routes,
            );
            return;
        }

        self::registerSegment(
            $segments,
            $httpMethod,
            $actionIndex,
            $routes,
        );
        return;
    }

    /**
     * @param non-empty-list<string> $segments
     * @param HttpMethod $httpMethod
     * @param NonNegInt $actionIndex
     * @param RouteSegment|RouteParameter $routes
     * @return void
     */
    protected static function registerParameter(array $segments, HttpMethod $httpMethod, int $actionIndex, array &$routes): void
    {
        $segment = $segments[0];
        $parameterName = substr($segment, 1);

        // If there are more segments, continue processing recursively
        if (count($segments) > 1) {

            // If multiple routes reference the same parameter, they must must use the same parameter name
            if(isset($routes['par']) && $routes['par']['name'] !== $parameterName) {
                throw new RouteConflictException();
            }

            $routes['par']['name'] = $parameterName;

            array_shift($segments);

            /** @var non-empty-list<string> $segments */

            self::registerRouteRecursive(
                $segments,
                $httpMethod,
                $actionIndex,
                $routes['par']
            );

            return;
        }

        // Otherwise, this is the last segment/parameter, so link to the action

        // If multiple routes reference the same parameter, they must must use the same parameter name
        // And don't allow multiple routes to end at the same route and method
        if(
            isset($routes['par'])
            && ($routes['par']['name'] !== $parameterName || isset($routes['par'][$httpMethod->value]))
        ) {
            throw new RouteConflictException();
        }

        $routes['par']['name'] = $parameterName;
        $routes['par'][$httpMethod->value] = $actionIndex;
    }

    /**
     * @param non-empty-list<string> $segments
     * @param HttpMethod $httpMethod
     * @param NonNegInt $actionIndex
     * @param RouteSegment|RouteParameter $routes
     * @return void
     */
    protected static function registerSegment(array $segments, HttpMethod $httpMethod, int $actionIndex, array &$routes): void
    {
        $segment = $segments[0];

        // If there are more segments, continue processing recursively
        if (count($segments) > 1) {
            if (!isset($routes['seg'][$segment])) {
                $routes['seg'][$segment] = [];
            }

            array_shift($segments);

            /** @var non-empty-list<string> $segments */

            self::registerRouteRecursive(
                $segments,
                $httpMethod,
                $actionIndex,
                $routes['seg'][$segment],
            );
            return;
        }

        // Otherwise, this is the last segment/parameter, so link to the action

        if(isset($routes['seg'][$segment][$httpMethod->value])) {
            throw new RouteConflictException();
        }

        $routes['seg'][$segment][$httpMethod->value] = $actionIndex;
    }

    /**
     * @param non-empty-list<string> $segments
     * @param RouteSegment|RouteParameter $routes
     * @param HttpMethod $httpMethod
     * @param array<string, ?string> $parameters
     * @return ?NonNegInt
     */
    protected static function resolveRecursive(array $segments, array $routes, HttpMethod $httpMethod, array &$parameters): ?int
    {
        $segment = $segments[0];

        // If it's a valid segment name, resolve it
        if (isset($routes['seg'][$segment])) {
            return self::resolveSegment(
                $segments,
                $routes,
                $httpMethod,
                $parameters
            );
        }

        // Otherwise, if there is a parameter, set it and resolve it
        if (isset($routes['par'])) {
            return self::resolveParameter(
                $segments,
                $routes,
                $httpMethod,
                $parameters
            );
        }

        // Otherwise, respond 404
        return null;
    }

    /**
     * @param non-empty-list<string> $segments
     * @param RouteSegment|RouteParameter $routes
     * @param HttpMethod $httpMethod
     * @param array<string, ?string> $parameters
     * @return ?NonNegInt
     */
    protected static function resolveSegment(array $segments, array $routes, HttpMethod $httpMethod, array &$parameters): ?int
    {
        $segment = $segments[0];

        /** @var RouteSegment $routes */
        $routes = $routes['seg'][$segment];

        // If there are more segments, continue processing recursively
        if (count($segments) > 1) {
            array_shift($segments);

            /** @var non-empty-list<string> $segments */

            return self::resolveRecursive(
                $segments,
                $routes,
                $httpMethod,
                $parameters
            );
        }

        // Otherwise, if the method maps to an action, return it
        if (isset($routes[$httpMethod->value])) {
            return $routes[$httpMethod->value];
        }

        // Otherwise, if there is a parameter and the method maps to an action, treat it as though the user left the parameter blank
        if (isset($routes['par'][$httpMethod->value])) {
            $key = $routes['par']['name'];
            $parameters[$key] = '';

            return $routes['par'][$httpMethod->value];
        }

        // Failed to find a matching action, so respond 404
        return null;
    }

    /**
     * @param non-empty-list<string> $segments
     * @param RouteSegment|RouteParameter $routes
     * @param HttpMethod $httpMethod
     * @param array<string, ?string> $parameters
     * @return ?NonNegInt
     */
    protected static function resolveParameter(array $segments, array $routes, HttpMethod $httpMethod, array &$parameters): ?int
    {
        $segment = $segments[0];

        assert(isset($routes['par']));

        /** @var RouteParameter $par */
        $par = $routes['par'];

        $parameterName = $par['name'];
        $parameters[$parameterName] = $segment;

        // If there are more segments, continue processing recursively
        if (count($segments) > 1) {
            array_shift($segments);

            /** @var non-empty-list<string> $segments */

            return self::resolveRecursive(
                $segments,
                $par,
                $httpMethod,
                $parameters
            );
        }

        // Otherwise, if the method maps to an action, return it
        if (isset($par[$httpMethod->value])) {
            return $par[$httpMethod->value];
        }

        // Failed to find a matching action, so respond 404
        return null;
    }

    protected static function isSegmentParameter(string $segment): bool
    {
        return !empty($segment) && $segment[0] === ':';
    }
}
