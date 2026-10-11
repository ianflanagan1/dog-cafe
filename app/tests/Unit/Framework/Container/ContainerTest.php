<?php

declare(strict_types=1);

namespace Tests\Unit\Framework\Container;

use App\Framework\Container\Container;
use App\Framework\Container\ContainerException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;

class ContainerTest extends TestCase
{
    public function test_container_injects_itself(): void
    {
        $container = new Container([
            'id' => function (ContainerInterface $containerFromInterface, Container $containerFromClass): ClassWithContainer {
                $this->assertInstanceOf(Container::class, $containerFromInterface);

                return new ClassWithContainer($containerFromInterface, $containerFromClass);
            },
        ]);

        $a = $container->get('id');

        $this->assertTrue(isset($a->containerFromInterface));
        $this->assertTrue(isset($a->containerFromClass));

        $this->assertSame($container, $a->containerFromInterface);
        $this->assertSame($container, $a->containerFromClass);
    }

    public function test_get_caches_result_for_closure(): void
    {
        $callCount = 0;
        $container = new Container([
            'id' => function () use (&$callCount): stdClass {
                $callCount++;

                return new stdClass();
            },
        ]);

        $a = $container->get('id');
        $b = $container->get('id');

        $this->assertSame($a, $b);
        $this->assertEquals(1, $callCount, 'Should only be invoked once');
    }

    public function test_get_caches_result_for_class(): void
    {
        $container = new Container();

        $a = $container->get(stdClass::class);
        $b = $container->get(stdClass::class);

        $this->assertSame($a, $b);
    }

    public function test_get_throws_exception_for_closure_parameter_without_type_hint(): void
    {
        $container = new Container([
            'id' => function ($noType): stdClass { // @phpstan-ignore shipmonk.unknownClosureParamType, shipmonk.unusedParameter (the missing type is what's under test)
                return new stdClass();
            },
        ]);

        $this->expectException(ContainerException::class);
        $container->get('id');
    }

    public function test_get_resolves_class_with_no_constructor(): void
    {
        $container = new Container();
        $a = $container->get(NoConstructor::class);
        $this->assertInstanceOf(NoConstructor::class, $a);
    }

    public function test_get_resolves_class_with_no_parameters(): void
    {
        $container = new Container();
        $a = $container->get(NoParameters::class);
        $this->assertInstanceOf(NoParameters::class, $a);
    }

    public function test_get_resolves_class_with_parameters(): void
    {
        $container = new Container();
        $a = $container->get(NoParameters::class);
        $this->assertInstanceOf(NoParameters::class, $a);
    }

    public function test_get_resolves_autowiring_all_parameters(): void
    {
        $container = new Container();
        $a = $container->get(AutowiringD::class);

        $this->assertInstanceOf(AutowiringD::class, $a);
        $this->assertSame(4, $a->int);
        $this->assertSame('d', $a->string);
    }

    public function test_get_resolves_autowiring_some_parameters(): void
    {
        $nonDefault = -1;
        $container = new Container([
            AutowiringD::class => function () use ($nonDefault): AutowiringD {
                return new AutowiringD($nonDefault);
            },
        ]);
        $a = $container->get(AutowiringD::class);

        $this->assertInstanceOf(AutowiringD::class, $a);
        $this->assertSame($nonDefault, $a->int);
        $this->assertSame('d', $a->string);
    }

    public function test_get_resolves_nested_autowiring(): void
    {
        $container = new Container();
        $a = $container->get(AutowiringA::class);

        $this->assertInstanceOf(AutowiringA::class, $a);
        $this->assertSame(1, $a->int);
        $this->assertSame('a', $a->string);

        $this->assertSame(2, $a->b->int);
        $this->assertSame('b', $a->b->string);

        $this->assertSame(4, $a->d->int);
        $this->assertSame('d', $a->d->string);

        $this->assertSame(3, $a->b->c1->int);
        $this->assertSame('c1', $a->b->c1->string);

        $this->assertSame(3, $a->b->c2->int);
        $this->assertSame('c2', $a->b->c2->string);

        $this->assertSame(4, $a->b->d->int);
        $this->assertSame('d', $a->b->d->string);

        $this->assertSame(4, $a->b->c1->d->int);
        $this->assertSame('d', $a->b->c1->d->string);

        $this->assertSame($a->d, $a->b->d);
        $this->assertSame($a->d, $a->b->c1->d);
    }

    public function test_get_resolves_nested_autowiring_with_separate_instances(): void
    {
        $container = new Container([
            AutowiringA::class => function (ContainerInterface $container): AutowiringA {

                /** @var AutowiringB */
                $b = $container->get(AutowiringB::class);

                return new AutowiringA(
                    $b,
                    new AutowiringD(11, 'd-1'),
                );
            },
            AutowiringB::class => function (ContainerInterface $container, AutowiringC2 $injectedC2): AutowiringB {

                /** @var AutowiringC1 */
                $c1 = $container->get(AutowiringC1::class);

                return new AutowiringB(
                    $c1,
                    $injectedC2,
                    new AutowiringD(12, 'd-2'),
                );
            },
            AutowiringC1::class => function (): AutowiringC1 {
                return new AutowiringC1(
                    new AutowiringD(13, 'd-3'),
                );
            },
        ]);

        $a = $container->get(AutowiringA::class);

        $this->assertInstanceOf(AutowiringA::class, $a);
        $this->assertSame(1, $a->int);
        $this->assertSame('a', $a->string);

        $this->assertSame(2, $a->b->int);
        $this->assertSame('b', $a->b->string);

        $this->assertSame(11, $a->d->int);
        $this->assertSame('d-1', $a->d->string);

        $this->assertSame(3, $a->b->c1->int);
        $this->assertSame('c1', $a->b->c1->string);

        $this->assertSame(3, $a->b->c2->int);
        $this->assertSame('c2', $a->b->c2->string);

        $this->assertSame(12, $a->b->d->int);
        $this->assertSame('d-2', $a->b->d->string);

        $this->assertSame(13, $a->b->c1->d->int);
        $this->assertSame('d-3', $a->b->c1->d->string);

        $this->assertNotSame($a->d, $a->b->d);
        $this->assertNotSame($a->d, $a->b->c1->d);
    }

    public function test_get_resolves_closure_with_dependencies(): void
    {
        $container = new Container([
            stdClass::class => function (AutowiringD $d): stdClass {
                $obj = new stdClass();
                $obj->d = $d;

                return $obj;
            },
        ]);

        $result = $container->get(stdClass::class);
        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertInstanceOf(AutowiringD::class, $result->d);
        $this->assertSame(4, $result->d->int);
        $this->assertSame('d', $result->d->string);
    }

    public function test_get_throws_exception_for_autowiring_without_defaults(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);
        $container->get(NoDefaults::class);
    }

    public function test_get_throws_exception_for_autowiring_with_circular_reference(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);
        $container->get(CircularA::class);
    }

    public function test_get_throws_exception_for_nonexistent_class(): void
    {
        $container = new Container();
        $this->expectException(NotFoundExceptionInterface::class);
        $container->get('nonexistent');
    }

    public function test_get_throws_exception_for_non_instantiable_class(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);
        $container->get(AbstractClass::class);
    }

    public function test_has(): void
    {
        $container = new Container([
            'id' => new stdClass(),
        ]);
        $this->assertTrue($container->has('id'));
        $this->assertFalse($container->has('nonexistent'));
    }

    /**
     * @return array<
     *      non-empty-string,
     *      array{
     *          instructions: array<
     *              non-empty-string,
     *              callable|string|object,
     *          >,
     *          idToTest: non-empty-string,
     *          expectedClass: class-string
     *      }
     * >
     */
    public static function cases_to_test_set_and_get(): array
    {
        return [
            'object' => [
                'instructions' => [
                    'id' => new stdClass(),
                ],
                'idToTest' => 'id',
                'expectedClass' => stdClass::class,
            ],
            'string' => [
                'instructions' => [
                    'id' => stdClass::class,
                ],
                'idToTest' => 'id',
                'expectedClass' => stdClass::class,
            ],
            'closure' => [
                'instructions' => [
                    'id' => function (): stdClass {
                        return new stdClass();
                    },
                ],
                'idToTest' => 'id',
                'expectedClass' => stdClass::class,
            ],
            'closure_arrow_function' => [
                'instructions' => [
                    'id' => static fn (): stdClass => new stdClass(),
                ],
                'idToTest' => 'id',
                'expectedClass' => stdClass::class,
            ],
            'callable_array_static' => [
                'instructions' => [
                    'id' => [TestClass::class, 'createInstance'],
                ],
                'idToTest' => 'id',
                'expectedClass' => stdClass::class,
            ],
            'callable_array_instance' => [
                'instructions' => [
                    'id' => [
                        new class () {
                            public function make(): stdClass
                            {
                                return new stdClass();
                            }
                        },
                        'make',
                    ],
                ],
                'idToTest' => 'id',
                'expectedClass' => stdClass::class,
            ],
            'callable_invokable_object' => [
                'instructions' => [
                    'id' => new class () {
                        public function __invoke(): stdClass
                        {
                            return new stdClass();
                        }
                    },
                ],
                'idToTest' => 'id',
                'expectedClass' => stdClass::class,
            ],
        ];
    }

    /**
     * @param array<
     *      non-empty-string,
     *      callable|string|object,
     * > $instructions
     * @param  non-empty-string  $idToTest
     * @param  class-string  $expectedClass
     */
    #[DataProvider('cases_to_test_set_and_get')]
    public function test_set_and_get(array $instructions, string $idToTest, string $expectedClass): void
    {
        $container = new Container();

        foreach ($instructions as $id => $instruction) {
            $container->set($id, $instruction);
        }

        $a = $container->get($idToTest);
        $b = $container->get($idToTest);
        $this->assertInstanceOf($expectedClass, $a);
        $this->assertSame($a, $b);
    }

    public function test_set_overwrites_existing_entry(): void
    {
        $container = new Container([
            'id' => new stdClass(),
        ]);

        $a = $container->get('id');

        $this->assertInstanceOf(stdClass::class, $a);

        $b = new stdClass();
        $container->set('id', $b);

        $this->assertNotSame($a, $container->get('id'));
    }
}

class ClassWithContainer
{
    public function __construct(
        public readonly Container $containerFromInterface,
        public readonly Container $containerFromClass,
    ) {
    }
}

class TestClass
{
    public static function createInstance(): stdClass
    {
        return new stdClass();
    }
}

class NoConstructor
{
}

class NoParameters
{
    public function __construct()
    {
    }
}

class AutowiringA
{
    public function __construct(
        public readonly AutowiringB $b,
        public readonly AutowiringD $d,
        public readonly int $int = 1,
        public readonly string $string = 'a',
    ) {
    }
}

class AutowiringB
{
    public function __construct(
        public readonly AutowiringC1 $c1,
        public readonly AutowiringC2 $c2,
        public readonly AutowiringD $d,
        public readonly int $int = 2,
        public readonly string $string = 'b',
    ) {
    }
}

class AutowiringC1
{
    public function __construct(
        public readonly AutowiringD $d,
        public readonly int $int = 3,
        public readonly string $string = 'c1',
    ) {
    }
}

class AutowiringC2
{
    public function __construct(
        public readonly int $int = 3,
        public readonly string $string = 'c2',
    ) {
    }
}

class AutowiringD
{
    public function __construct(
        public readonly int $int = 4,
        public readonly string $string = 'd',
    ) {
    }
}

class NoDefaults
{
    public function __construct(public readonly int $a)
    {
    }
}

class CircularA
{
    public function __construct(protected CircularB $b)
    {
    }
}

class CircularB
{
    public function __construct(protected CircularC $c)
    {
    }
}

class CircularC
{
    public function __construct(protected CircularA $a)
    {
    }
}

abstract class AbstractClass
{
}
