<?php

namespace Oasis\Mlib\Event;

interface EventDispatcherInterface
{
    public function getParentEventDispatcher(): ?EventDispatcherInterface;

    public function setParentEventDispatcher(EventDispatcherInterface $parent): void;

    public function dispatch(Event|string $event, mixed $context = null): void;

    public function addEventListener(string $name, callable $listener, int $priority = 0): void;

    public function removeEventListener(string $name, callable $listener): void;

    public function removeAllEventListeners(string $name = ''): void;

    public function setDelegateDispatcher(?EventDispatcherInterface $delegate): void;
}
