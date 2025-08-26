<?php

declare(strict_types=1);

namespace App;

use App\Exceptions\Container\ContainerException;
use App\Exceptions\Container\NotFoundException;
use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

class Container implements ContainerInterface
{
    /**
     * @param array<string, callable|string|object> $instructions
     */
    public function __construct(protected array $instructions = [])
    {
    }

    /**
     * Retrieve an object by its container identifier.
     *
     * @param string $id
     * @return object
     * @throws ContainerException
     */
    public function get(string $id): object
    {
        return $this->resolve($id);
    }

    public function set(string $id, callable|string|object $instruction): void
    {
        $this->instructions[$id] = $instruction;
    }

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
     * @throws ContainerException
     */
    protected function resolve(string $id, array $loopTracker = []): object
    {
        if (array_search($id, $loopTracker) !== false) {
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
     * @throws ContainerException
     */
    protected function resolveCallable(string $id, array $loopTracker): object
    {
        /** @var callable $instruction */
        $instruction = $this->instructions[$id];

        $reflection = is_array($instruction)
                ? new ReflectionMethod($instruction[0], $instruction[1])
                : new ReflectionFunction(Closure::fromCallable($instruction));

        $args = $this->buildDependencies($reflection->getParameters(), $loopTracker);
        $instance = $instruction(...$args);

        $this->instructions[$id] = $instance;

        return $instance;
    }

    /**
     * Instantiate a class by name, resolving and injecting its dependencies recursively.
     *
     * @param string $id
     * @param list<string> $loopTracker Tracks already-resolving IDs to detect circular dependencies.
     * @return object
     * @throws NotFoundException
     * @throws ContainerException
     */
    protected function resolveClassName(string $id, array $loopTracker): object
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

        if (empty($parameters)) {
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
     * @throws ContainerException
     */
    protected function buildDependencies(array $parameters, array $loopTracker, array $provided = []): array
    {
        if (empty($parameters)) {
            return [];
        }

        if ($parameters[array_key_last($parameters)]->isVariadic()) {
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

                if ($type == ContainerInterface::class || $type == Container::class) {
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
