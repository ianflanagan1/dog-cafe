<?php

declare(strict_types=1);

namespace App\Framework\Routing;

use App\Framework\Container\Container;
use App\Framework\Http\Response;

/**
 * Class name, method name, parameters
 * @phpstan-type Actionable array{
 *      0: class-string,
 *      1: non-empty-string,
 *      2: array<non-empty-string, string>
 * }
 */
final class ActionExecutor
{
    public function __construct(private Container $container)
    {
    }

    /**
     * @param Actionable $actionable
     * @return Response
     */
    public function handle(array $actionable): Response
    {
        /** @var Response $result */
        $result = $this->container->call($actionable[0], $actionable[1], $actionable[2]);

        return $result;
    }
}
