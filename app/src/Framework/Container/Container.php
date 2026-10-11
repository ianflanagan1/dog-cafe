<?php

declare(strict_types=1);

namespace App\Framework\Container;

use Override;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

final class Container implements ContainerInterface
{
    /**
     * @param array<string, callable|string|object> $instructions
     */
    public function __construct(private array $instructions = [])
    {
    }

    /**
     * @return object
     */
    #[Override]
    public function get(string $id): object
    {
        return $this->resolve($id);
    }

    public function set(string $id, callable|string|object $instruction): void
    {
        $this->instructions[$id] = $instruction;
    }

    #[Override]
    public function has(string $id): bool
    {
        return isset($this->instructions[$id]);
    }

    /**
     * @param class-string $class
     * @param string $method
     * @param array<string, mixed> $provided
     * @return mixed
     */
    public function call(string $class, string $method, array $provided = []): mixed
    {
        $instance = $this->get($class);

        $reflection = new ReflectionMethod($instance, $method);
        $parameters = $reflection->getParameters();
        $args = $this->buildDependencies(
            $parameters,
            [$class],
            $provided
        );

        return $reflection->invokeArgs($instance, $args);
    }

    /**
     * Resolve a string identifier to an object.
     *
     * If the identifier maps to an instantiated object, it returns the object (singleton).
     * If the identifier maps to a closure, it is invoked with its dependencies resolved,
     * and the result is stored and returned.
     * If the identifier maps to a string (or is absent from the instructions), the class is
     * instantiated by name with dependencies resolved and the result is stored and returned.
     *
     * @param string $id
     * @param list<string> $loopTracker Tracks already-resolving IDs to detect circular dependencies.
     * @return object
     */
    private function resolve(string $id, array $loopTracker = []): object
    {
        if (in_array($id, $loopTracker, true)) {
            throw new ContainerException('Circular dependency: ' . implode(' -> ', $loopTracker) . ' -> ' . $id);
        }

        $loopTracker[] = $id;

        if (! $this->has($id)) {
            return $this->resolveClassName($id, $loopTracker);
        }

        $instruction = $this->instructions[$id];

        if (is_callable($instruction)) {
            return $this->resolveCallable($id, $loopTracker);
        }

        if (is_object($instruction)) {
            return $instruction;
        }

        // It's a string, so resolve it
        return $this->resolve($instruction, $loopTracker);
    }

    /**
     * @param string $id
     * @param list<string> $loopTracker Tracks already-resolving IDs to detect circular dependencies.
     * @return object
     */
    private function resolveCallable(string $id, array $loopTracker): object
    {
        /** @var callable */
        $instruction = $this->instructions[$id];

        $reflection = is_array($instruction)
                ? new ReflectionMethod($instruction[0], $instruction[1])
                : new ReflectionFunction($instruction(...));

        $args = $this->buildDependencies($reflection->getParameters(), $loopTracker);
        $instance = $instruction(...$args);

        if (!is_object($instance)) {
            throw new ContainerException("Cannot resolve: the closure for `$id` did not return an object");
        }

        $this->instructions[$id] = $instance;

        return $instance;
    }

    /**
     * Instantiate a class by name, resolving and injecting its dependencies recursively.
     *
     * @param string $id
     * @param list<string> $loopTracker Tracks already-resolving IDs to detect circular dependencies.
     * @return object
     */
    private function resolveClassName(string $id, array $loopTracker): object
    {
        if (!class_exists($id)) {
            throw new NotFoundException("Cannot reflect: `$id` is not a class");
        }

        $reflectionClass = new ReflectionClass($id);

        if (!$reflectionClass->isInstantiable()) {
            throw new ContainerException("Cannot reflect: `$id` is not instantiable");
        }

        $constructor = $reflectionClass->getConstructor();

        if ($constructor === null) {
            $this->instructions[$id] = new $id();
            return $this->instructions[$id];
        }

        $parameters = $constructor->getParameters();

        if ($parameters === []) {
            $this->instructions[$id] = new $id();
            return $this->instructions[$id];
        }

        $this->instructions[$id] = $reflectionClass->newInstanceArgs($this->buildDependencies($parameters, $loopTracker));
        return $this->instructions[$id];
    }

    /**
     * @param list<ReflectionParameter> $parameters
     * @param list<string> $loopTracker Tracks already-resolving IDs to detect circular dependencies.
     * @param array<string, mixed> $provided
     * @return list<mixed>
     */
    private function buildDependencies(array $parameters, array $loopTracker, array $provided = []): array
    {
        if ($parameters === []) {
            return [];
        }

        if (array_last($parameters)->isVariadic()) {
            array_pop($parameters);
        }

        /** @var list<mixed> */
        return array_map(
            function (ReflectionParameter $parameter) use ($loopTracker, $provided): mixed {
                $name = $parameter->getName();

                if (array_key_exists($name, $provided)) {
                    return $provided[$name];
                }

                $type = $parameter->getType();

                if ($type === null) {
                    throw new ContainerException('Cannot resolve parameter: no type hint provided');
                }

                if (in_array((string) $type, [ContainerInterface::class, Container::class], true)) {
                    return $this;
                }

                if ($type instanceof ReflectionUnionType) {
                    throw new ContainerException('Cannot resolve parameter: union types are not supported yet');
                }

                if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                    return $this->resolve($type->getName(), $loopTracker);
                }

                if ($parameter->isDefaultValueAvailable()) {
                    return $parameter->getDefaultValue();
                }

                throw new ContainerException('Cannot resolve parameter: ' . $name);
            },
            $parameters,
        );
    }
}
