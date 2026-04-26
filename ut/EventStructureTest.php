<?php
/**
 * Smoke Tests — Reflection API-based structure verification for Event 3.0.
 *
 * These tests verify that Event uses constructor promotion, readonly properties,
 * and preserves the 2.0 constructor contract. They are intended to FAIL on
 * pre-promotion code and PASS after the 3.0 refactor.
 *
 * Requirements: 1.1-1.6, 2.1-2.5, 6.1-6.5, 7.3, 7.4
 */

namespace Oasis\Mlib\UnitTesting;

use Oasis\Mlib\Event\Event;
use Oasis\Mlib\Event\EventDispatcherInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

class EventStructureTest extends TestCase
{
    private ReflectionClass $eventRef;
    private ReflectionClass $interfaceRef;
    private ReflectionMethod $ctorRef;

    protected function setUp(): void
    {
        $this->eventRef = new ReflectionClass(Event::class);
        $this->interfaceRef = new ReflectionClass(EventDispatcherInterface::class);
        $this->ctorRef = $this->eventRef->getConstructor();
    }

    // =========================================================================
    // 1. Constructor parameters are promoted (Req 1.1-1.4, 1.6)
    // =========================================================================

    #[DataProvider('promotedParameterProvider')]
    public function testConstructorParameterIsPromoted(string $paramName): void
    {
        $param = $this->getConstructorParameter($paramName);
        $this->assertTrue(
            $param->isPromoted(),
            "Constructor parameter \${$paramName} should be a promoted parameter"
        );
    }

    public static function promotedParameterProvider(): array
    {
        return [
            '$name' => ['name'],
            '$context' => ['context'],
            '$bubbles' => ['bubbles'],
            '$cancellable' => ['cancellable'],
        ];
    }

    // =========================================================================
    // 2. $name, $bubbles, $cancellable are readonly (Req 2.1-2.3)
    // =========================================================================

    #[DataProvider('readonlyPropertyProvider')]
    public function testPropertyIsReadonly(string $propName): void
    {
        $prop = $this->eventRef->getProperty($propName);
        $this->assertTrue(
            $prop->isReadOnly(),
            "Property \${$propName} should be readonly"
        );
    }

    public static function readonlyPropertyProvider(): array
    {
        return [
            '$name' => ['name'],
            '$bubbles' => ['bubbles'],
            '$cancellable' => ['cancellable'],
        ];
    }

    // =========================================================================
    // 3. $context is NOT readonly (Req 2.4)
    // =========================================================================

    public function testContextIsNotReadonly(): void
    {
        $prop = $this->eventRef->getProperty('context');
        $this->assertFalse(
            $prop->isReadOnly(),
            'Property $context should NOT be readonly (setContext() mutates it)'
        );
    }

    // =========================================================================
    // 4. Lifecycle properties are NOT readonly (Req 2.5)
    // =========================================================================

    #[DataProvider('lifecyclePropertyProvider')]
    public function testLifecyclePropertyIsNotReadonly(string $propName): void
    {
        $prop = $this->eventRef->getProperty($propName);
        $this->assertFalse(
            $prop->isReadOnly(),
            "Lifecycle property \${$propName} should NOT be readonly"
        );
    }

    public static function lifecyclePropertyProvider(): array
    {
        return [
            '$cancelled' => ['cancelled'],
            '$propagationStopped' => ['propagationStopped'],
            '$propagationStoppedImmediately' => ['propagationStoppedImmediately'],
            '$target' => ['target'],
            '$currentTarget' => ['currentTarget'],
        ];
    }

    // =========================================================================
    // 5. Constructor parameter order, types, and defaults match 2.0 (Req 1.5, 7.3)
    // =========================================================================

    public function testConstructorParameterOrder(): void
    {
        $params = $this->ctorRef->getParameters();
        $names = array_map(fn(ReflectionParameter $p) => $p->getName(), $params);

        $this->assertSame(
            ['name', 'context', 'bubbles', 'cancellable'],
            $names,
            'Constructor parameter order must be: $name, $context, $bubbles, $cancellable'
        );
    }

    public function testConstructorParameterTypes(): void
    {
        $expected = [
            'name' => 'string',
            'context' => 'mixed',
            'bubbles' => 'bool',
            'cancellable' => 'bool',
        ];

        foreach ($expected as $paramName => $expectedType) {
            $param = $this->getConstructorParameter($paramName);
            $type = $param->getType();
            $this->assertInstanceOf(
                ReflectionNamedType::class,
                $type,
                "Parameter \${$paramName} should have a named type"
            );
            $this->assertSame(
                $expectedType,
                $type->getName(),
                "Parameter \${$paramName} type should be {$expectedType}"
            );
        }
    }

    public function testConstructorParameterDefaults(): void
    {
        // $name has no default
        $nameParam = $this->getConstructorParameter('name');
        $this->assertFalse(
            $nameParam->isDefaultValueAvailable(),
            '$name should have no default value'
        );

        // $context defaults to null
        $contextParam = $this->getConstructorParameter('context');
        $this->assertTrue($contextParam->isDefaultValueAvailable());
        $this->assertNull($contextParam->getDefaultValue(), '$context default should be null');

        // $bubbles defaults to true
        $bubblesParam = $this->getConstructorParameter('bubbles');
        $this->assertTrue($bubblesParam->isDefaultValueAvailable());
        $this->assertTrue($bubblesParam->getDefaultValue(), '$bubbles default should be true');

        // $cancellable defaults to true
        $cancellableParam = $this->getConstructorParameter('cancellable');
        $this->assertTrue($cancellableParam->isDefaultValueAvailable());
        $this->assertTrue($cancellableParam->getDefaultValue(), '$cancellable default should be true');
    }

    // =========================================================================
    // 6. EventDispatcherInterface method signatures unchanged (Req 7.4)
    // =========================================================================

    public function testEventDispatcherInterfaceMethodSignaturesUnchanged(): void
    {
        $fqcn = EventDispatcherInterface::class;
        $eventFqcn = Event::class;

        $expectedMethods = [
            'getParentEventDispatcher' => [
                'params' => [],
                'returnType' => "?{$fqcn}",
            ],
            'setParentEventDispatcher' => [
                'params' => [
                    ['name' => 'parent', 'type' => $fqcn],
                ],
                'returnType' => 'void',
            ],
            'dispatch' => [
                'params' => [
                    ['name' => 'event', 'type' => "{$eventFqcn}|string"],
                    ['name' => 'context', 'type' => 'mixed', 'default' => null],
                ],
                'returnType' => 'void',
            ],
            'addEventListener' => [
                'params' => [
                    ['name' => 'name', 'type' => 'string'],
                    ['name' => 'listener', 'type' => 'callable'],
                    ['name' => 'priority', 'type' => 'int', 'default' => 0],
                ],
                'returnType' => 'void',
            ],
            'removeEventListener' => [
                'params' => [
                    ['name' => 'name', 'type' => 'string'],
                    ['name' => 'listener', 'type' => 'callable'],
                ],
                'returnType' => 'void',
            ],
            'removeAllEventListeners' => [
                'params' => [
                    ['name' => 'name', 'type' => 'string', 'default' => ''],
                ],
                'returnType' => 'void',
            ],
            'setDelegateDispatcher' => [
                'params' => [
                    ['name' => 'delegate', 'type' => "?{$fqcn}"],
                ],
                'returnType' => 'void',
            ],
        ];

        // Verify method count matches
        $actualMethods = $this->interfaceRef->getMethods();
        $this->assertCount(
            count($expectedMethods),
            $actualMethods,
            'EventDispatcherInterface should have exactly ' . count($expectedMethods) . ' methods'
        );

        foreach ($expectedMethods as $methodName => $spec) {
            $this->assertTrue(
                $this->interfaceRef->hasMethod($methodName),
                "Interface should have method {$methodName}"
            );

            $method = $this->interfaceRef->getMethod($methodName);
            $params = $method->getParameters();

            $this->assertCount(
                count($spec['params']),
                $params,
                "Method {$methodName} should have " . count($spec['params']) . ' parameters'
            );

            foreach ($spec['params'] as $i => $expectedParam) {
                $param = $params[$i];
                $this->assertSame(
                    $expectedParam['name'],
                    $param->getName(),
                    "Parameter {$i} of {$methodName} should be named {$expectedParam['name']}"
                );

                $type = $param->getType();
                $this->assertSame(
                    $expectedParam['type'],
                    (string) $type,
                    "Parameter {$expectedParam['name']} of {$methodName} should have type {$expectedParam['type']}"
                );

                if (array_key_exists('default', $expectedParam)) {
                    $this->assertTrue(
                        $param->isDefaultValueAvailable(),
                        "Parameter {$expectedParam['name']} of {$methodName} should have a default value"
                    );
                    $this->assertSame(
                        $expectedParam['default'],
                        $param->getDefaultValue(),
                        "Parameter {$expectedParam['name']} of {$methodName} default value mismatch"
                    );
                }
            }

            // Verify return type
            $returnType = $method->getReturnType();
            $this->assertSame(
                $spec['returnType'],
                (string) $returnType,
                "Method {$methodName} return type should be {$spec['returnType']}"
            );
        }
    }

    // =========================================================================
    // 7. Exclusion confirmation: no enum, intersection/DNF types, Fiber (Req 6.1-6.3)
    // =========================================================================

    public function testNoEnumTypesInSrcNamespace(): void
    {
        // Event, EventDispatcherInterface, EventDispatcherTrait — none should be enums
        $classes = [
            Event::class,
            EventDispatcherInterface::class,
            \Oasis\Mlib\Event\EventDispatcherTrait::class,
        ];

        foreach ($classes as $className) {
            $ref = new ReflectionClass($className);
            $this->assertFalse(
                $ref->isEnum(),
                "{$className} should not be an enum"
            );
        }
    }

    public function testNoFiberUsageInEventConstructor(): void
    {
        // Verify constructor body does not reference Fiber by checking
        // that Event does not implement/extend any Fiber-related class
        $ref = new ReflectionClass(Event::class);

        $this->assertFalse(
            is_subclass_of(Event::class, \Fiber::class),
            'Event should not extend Fiber'
        );

        // Verify no Fiber-typed properties
        foreach ($ref->getProperties() as $prop) {
            $type = $prop->getType();
            if ($type instanceof ReflectionNamedType) {
                $this->assertNotSame(
                    'Fiber',
                    $type->getName(),
                    "Property \${$prop->getName()} should not be Fiber-typed"
                );
            }
        }
    }

    public function testNoIntersectionOrDnfTypesOnEventProperties(): void
    {
        $ref = new ReflectionClass(Event::class);

        foreach ($ref->getProperties() as $prop) {
            $type = $prop->getType();
            $this->assertFalse(
                $type instanceof \ReflectionIntersectionType,
                "Property \${$prop->getName()} should not use intersection types"
            );
            // DNF types would appear as ReflectionUnionType containing ReflectionIntersectionType
            if ($type instanceof \ReflectionUnionType) {
                foreach ($type->getTypes() as $inner) {
                    $this->assertFalse(
                        $inner instanceof \ReflectionIntersectionType,
                        "Property \${$prop->getName()} should not use DNF types"
                    );
                }
            }
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function getConstructorParameter(string $name): ReflectionParameter
    {
        foreach ($this->ctorRef->getParameters() as $param) {
            if ($param->getName() === $name) {
                return $param;
            }
        }
        $this->fail("Constructor parameter \${$name} not found");
    }
}
