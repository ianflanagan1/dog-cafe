<?php

declare(strict_types=1);

namespace App\Routing;

use App\Container;
use App\Http\DTOs\ViewParameters;

/**
 * Class name, method name, parameters
 * @phpstan-type Actionable array{
 *      0: class-string,
 *      1: non-empty-string,
 *      2: array<non-empty-string, string>
 * }
 */
class ActionExecutor
{
    public function __construct(protected Container $container)
    {
    }

    /**
     * @param Actionable $actionable
     * @return ViewParameters|string
     */
    public function handle(array $actionable): ViewParameters|string
    {
        /** @var ViewParameters|string $result */
        $result = $this->container->call($actionable[0], $actionable[1], $actionable[2]);

        return $result;
    }
}
