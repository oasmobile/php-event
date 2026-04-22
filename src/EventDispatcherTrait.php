<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-09-28
 * Time: 18:33
 */

namespace Oasis\Mlib\Event;

/**
 * Default implementation of EventDispatcherInterface.
 *
 * @phpstan-require-implements EventDispatcherInterface
 */
trait EventDispatcherTrait
{
    protected ?EventDispatcherInterface $eventParent = null;
    /** @var array<string, array<int, array<int, callable>>> */
    protected array $eventListeners = [];
    protected ?EventDispatcherInterface $delegateDispatcher = null;

    public function getParentEventDispatcher(): ?EventDispatcherInterface
    {
        return $this->eventParent;
    }

    public function setParentEventDispatcher(EventDispatcherInterface $parent): void
    {
        $this->eventParent = $parent;
    }

    public function dispatch(Event|string $event, mixed $context = null): void
    {
        if (!$this instanceof EventDispatcherInterface) {
            throw new \LogicException(
                sprintf(
                    'Class %s uses EventDispatcherTrait but does not implement EventDispatcherInterface.',
                    static::class
                )
            );
        }

        if (!$event instanceof Event) {
            $event = new Event(strval($event), $context);
        }
        if ($context) {
            $event->setContext($context);
        }

        if ($this->delegateDispatcher instanceof EventDispatcherInterface) {
            $this->delegateDispatcher->dispatch($event);

            return;
        }

        $event->setTarget($this);

        $chain = [];
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this;
        do {
            $chain[] = $dispatcher;
        } while ($dispatcher = $dispatcher->getParentEventDispatcher());

        if (!$event->doesBubble()) { // this event uses capturing method
            $chain = array_reverse($chain);
        }

        foreach ($chain as $dispatcher) {
            /** @noinspection PhpUndefinedMethodInspection */
            $dispatcher->doDispatchEvent($event);

            if ($event->isPropagationStopped()) {
                break;
            }
        }
    }

    public function addEventListener(string $name, callable $listener, int $priority = 0): void
    {
        if (!isset($this->eventListeners[$name]) || !is_array($this->eventListeners[$name])) {
            $this->eventListeners[$name] = [];
        }
        if (!isset($this->eventListeners[$name][$priority])
            || !is_array($this->eventListeners[$name][$priority])
        ) {
            $this->eventListeners[$name][$priority] = [];
            ksort($this->eventListeners[$name]);
        }

        $this->eventListeners[$name][$priority][] = $listener;
    }

    public function removeEventListener(string $name, callable $listener): void
    {
        $comp = function ($a, $b) {
            if (is_string($a) && is_string($b) && $a == $b) {
                return true;
            }

            if (is_array($a) && is_array($b) && count($a) == 2 && count($b) == 2) {
                if ($a[0] == $b[0] && $a[1] == $b[1]) {
                    return true;
                }
            }

            return $a === $b;
        };

        if (isset($this->eventListeners[$name]) && is_array($this->eventListeners[$name])) {
            foreach ($this->eventListeners[$name] as $priority => &$list) {
                $new_list = [];
                foreach ($list as $callback) {
                    if (!$comp($callback, $listener)) {
                        $new_list[] = $callback;
                    }
                }
                $list = $new_list;
            }
        }
    }

    public function removeAllEventListeners(string $name = ''): void
    {
        foreach ($this->eventListeners as $eventName => &$list) {
            if ($name == '' || $eventName == $name) {
                $list = [];
            }
        }
    }

    public function setDelegateDispatcher(?EventDispatcherInterface $delegate): void
    {
        $this->delegateDispatcher = $delegate;
    }

    protected function doDispatchEvent(Event $event): void
    {
        $event->setCurrentTarget($this);

        if (isset($this->eventListeners[$event->getName()])) {
            foreach ($this->eventListeners[$event->getName()] as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    call_user_func($callback, $event);

                    if ($event->isPropagationStoppedImmediately()) {
                        return;
                    }
                }
            }
        }
    }
}
