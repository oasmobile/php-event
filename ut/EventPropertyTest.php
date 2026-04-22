<?php
/**
 * Property-based tests for oasis/event 2.0.0.
 *
 * Uses Eris (giorgiosironi/eris) for property-based testing.
 * Each property runs 100 iterations (Eris default) with random inputs.
 */

namespace Oasis\Mlib\UnitTesting;

use Eris\TestTrait;
use Eris\Generators;
use Oasis\Mlib\Event\Event;
use Oasis\Mlib\Event\EventDispatcherInterface;
use Oasis\Mlib\Event\EventDispatcherTrait;
use PHPUnit\Framework\TestCase;

class PBTEventDispatcher implements EventDispatcherInterface
{
    use EventDispatcherTrait;
}

class EventPropertyTest extends TestCase
{
    use TestTrait;

    // =========================================================================
    // Feature: release-2.0.0, Property 1: Event 构造保持值完整性
    //
    // For any valid combination of (name, context, bubbles, cancellable),
    // constructing an Event preserves all values and isCancelled() is false.
    // Validates: Requirement 2, AC 5; Requirement 8, AC 1
    // =========================================================================

    public function testEventConstructionPreservesValues(): void
    {
        $this->forAll(
            Generators::suchThat(
                fn(string $s) => $s !== '',
                Generators::string()
            ),
            Generators::oneOf(
                Generators::constant(null),
                Generators::int(),
                Generators::string(),
                Generators::bool(),
                Generators::constant([1, 'two', true])
            ),
            Generators::bool(),
            Generators::bool()
        )->then(function (string $name, mixed $context, bool $bubbles, bool $cancellable) {
            $event = new Event($name, $context, $bubbles, $cancellable);

            $this->assertSame($name, $event->getName());
            $this->assertSame($context, $event->getContext());
            $this->assertSame($bubbles, $event->doesBubble());
            $this->assertFalse($event->isCancelled());

            // Verify cancellable via behavior: cancel() should succeed or throw
            if ($cancellable) {
                $event->cancel();
                $this->assertTrue($event->isCancelled());
            } else {
                try {
                    $event->cancel();
                    $this->fail('Expected LogicException for non-cancellable event');
                } catch (\LogicException) {
                    $this->assertFalse($event->isCancelled());
                }
            }
        });
    }

    // =========================================================================
    // Feature: release-2.0.0, Property 2: Context 读写一致性
    //
    // For any mixed value v, setContext(v) then getContext() returns v;
    // dispatch($event, $context) sets context correctly.
    // Validates: Requirement 8, AC 5, 13
    // =========================================================================

    public function testContextReadWriteConsistency(): void
    {
        $this->forAll(
            Generators::oneOf(
                Generators::constant(null),
                Generators::int(),
                Generators::string(),
                Generators::bool(),
                Generators::float(),
                Generators::constant([1, 'a', null])
            )
        )->then(function (mixed $value) {
            $event = new Event('test');
            $event->setContext($value);
            $this->assertSame($value, $event->getContext());
        });
    }

    public function testDispatchContextSetsEventContext(): void
    {
        $this->forAll(
            Generators::oneOf(
                Generators::constant(null),
                Generators::int(),
                Generators::string(),
                Generators::bool()
            )
        )->then(function (mixed $context) {
            // Skip null context — dispatch only sets context when truthy
            if (!$context) {
                return;
            }

            $dispatcher = new PBTEventDispatcher();
            $capturedContext = null;
            $dispatcher->addEventListener('evt', function (Event $e) use (&$capturedContext) {
                $capturedContext = $e->getContext();
            });
            $dispatcher->dispatch('evt', $context);
            $this->assertSame($context, $capturedContext);
        });
    }

    // =========================================================================
    // Feature: release-2.0.0, Property 3: 冒泡模式下 target/currentTarget 正确性
    //
    // For any chain depth 2-5, dispatching a bubbling event from child:
    // (a) getTarget() always returns child
    // (b) getCurrentTarget() follows child → parent order
    // Validates: Requirement 8, AC 7
    // =========================================================================

    public function testBubblingModeTargetAndCurrentTarget(): void
    {
        $this->forAll(
            Generators::choose(2, 5)
        )->then(function (int $depth) {
            // Build dispatcher chain: dispatchers[0] = child, dispatchers[N-1] = root
            $dispatchers = [];
            for ($i = 0; $i < $depth; $i++) {
                $dispatchers[] = new PBTEventDispatcher();
            }
            // Set parent relationships: child → parent → ... → root
            for ($i = 0; $i < $depth - 1; $i++) {
                $dispatchers[$i]->setParentEventDispatcher($dispatchers[$i + 1]);
            }

            $targets = [];
            $currentTargets = [];
            $listener = function (Event $e) use (&$targets, &$currentTargets) {
                $targets[] = $e->getTarget();
                $currentTargets[] = $e->getCurrentTarget();
            };

            foreach ($dispatchers as $d) {
                $d->addEventListener('ping', $listener);
            }

            $child = $dispatchers[0];
            $child->dispatch(new Event('ping', null, true)); // bubbles = true

            // (a) getTarget() always returns child
            for ($i = 0; $i < $depth; $i++) {
                $this->assertSame($child, $targets[$i], "getTarget() should always return child at handler $i");
            }

            // (b) getCurrentTarget() follows child → parent → ... → root order
            for ($i = 0; $i < $depth; $i++) {
                $this->assertSame($dispatchers[$i], $currentTargets[$i], "getCurrentTarget() mismatch at handler $i");
            }
        });
    }

    // =========================================================================
    // Feature: release-2.0.0, Property 4: 捕获模式下 target/currentTarget 与执行顺序
    //
    // For any chain depth 2-5, dispatching a capturing event from child:
    // (a) getTarget() always returns child
    // (b) getCurrentTarget() follows root → ... → parent → child order
    // Validates: Requirement 8, AC 8, 14
    // =========================================================================

    public function testCapturingModeTargetAndCurrentTargetAndOrder(): void
    {
        $this->forAll(
            Generators::choose(2, 5)
        )->then(function (int $depth) {
            $dispatchers = [];
            for ($i = 0; $i < $depth; $i++) {
                $dispatchers[] = new PBTEventDispatcher();
            }
            for ($i = 0; $i < $depth - 1; $i++) {
                $dispatchers[$i]->setParentEventDispatcher($dispatchers[$i + 1]);
            }

            $targets = [];
            $currentTargets = [];
            $listener = function (Event $e) use (&$targets, &$currentTargets) {
                $targets[] = $e->getTarget();
                $currentTargets[] = $e->getCurrentTarget();
            };

            foreach ($dispatchers as $d) {
                $d->addEventListener('ping', $listener);
            }

            $child = $dispatchers[0];
            $child->dispatch(new Event('ping', null, false)); // bubbles = false → capturing

            // (a) getTarget() always returns child
            for ($i = 0; $i < $depth; $i++) {
                $this->assertSame($child, $targets[$i], "getTarget() should always return child at handler $i");
            }

            // (b) getCurrentTarget() follows root → ... → parent → child (reversed chain)
            $reversed = array_reverse($dispatchers);
            for ($i = 0; $i < $depth; $i++) {
                $this->assertSame($reversed[$i], $currentTargets[$i], "getCurrentTarget() mismatch at handler $i (capturing)");
            }
        });
    }

    // =========================================================================
    // Feature: release-2.0.0, Property 5: removeAllEventListeners 选择性与全局移除
    //
    // (a) removeAllEventListeners($name) removes only that name's listeners
    // (b) removeAllEventListeners('') removes all listeners
    // Validates: Requirement 8, AC 9-10
    // =========================================================================

    public function testRemoveAllEventListenersSelectiveRemoval(): void
    {
        $this->forAll(
            Generators::choose(2, 6)
        )->then(function (int $numEvents) {
            $dispatcher = new PBTEventDispatcher();
            $eventNames = [];
            for ($i = 0; $i < $numEvents; $i++) {
                $eventNames[] = "event_$i";
            }

            // Register a listener for each event name
            $called = [];
            foreach ($eventNames as $name) {
                $dispatcher->addEventListener($name, function () use (&$called, $name) {
                    $called[] = $name;
                });
            }

            // Pick a random event name to remove
            $removeIndex = random_int(0, $numEvents - 1);
            $removedName = $eventNames[$removeIndex];
            $dispatcher->removeAllEventListeners($removedName);

            // Dispatch all events
            foreach ($eventNames as $name) {
                $dispatcher->dispatch(new Event($name));
            }

            // Removed name should not appear; all others should
            $this->assertNotContains($removedName, $called);
            foreach ($eventNames as $name) {
                if ($name !== $removedName) {
                    $this->assertContains($name, $called);
                }
            }
        });
    }

    public function testRemoveAllEventListenersGlobalRemoval(): void
    {
        $this->forAll(
            Generators::choose(1, 6)
        )->then(function (int $numEvents) {
            $dispatcher = new PBTEventDispatcher();
            $called = [];

            for ($i = 0; $i < $numEvents; $i++) {
                $name = "event_$i";
                $dispatcher->addEventListener($name, function () use (&$called, $name) {
                    $called[] = $name;
                });
            }

            $dispatcher->removeAllEventListeners('');

            for ($i = 0; $i < $numEvents; $i++) {
                $dispatcher->dispatch(new Event("event_$i"));
            }

            $this->assertSame([], $called);
        });
    }

    // =========================================================================
    // Feature: release-2.0.0, Property 6: 监听器优先级排序
    //
    // For any set of listeners with distinct integer priorities,
    // execution order matches ascending priority (lower first).
    // Validates: Requirement 8, AC 11
    // =========================================================================

    public function testListenerPriorityOrdering(): void
    {
        $this->forAll(
            Generators::choose(2, 10)
        )->then(function (int $numListeners) {
            $dispatcher = new PBTEventDispatcher();

            // Generate distinct random priorities
            $priorities = [];
            while (count($priorities) < $numListeners) {
                $p = random_int(-100, 100);
                if (!in_array($p, $priorities, true)) {
                    $priorities[] = $p;
                }
            }

            // Shuffle to register in random order
            $shuffled = $priorities;
            shuffle($shuffled);

            $executionOrder = [];
            foreach ($shuffled as $priority) {
                $dispatcher->addEventListener('ping', function () use (&$executionOrder, $priority) {
                    $executionOrder[] = $priority;
                }, $priority);
            }

            $dispatcher->dispatch(new Event('ping'));

            // Expected: ascending priority order
            $expected = $priorities;
            sort($expected);
            $this->assertSame($expected, $executionOrder);
        });
    }
}
