<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2015-09-28
 * Time: 18:25
 */

namespace Oasis\Mlib\Event;

class Event
{
    protected EventDispatcherInterface $target;
    protected EventDispatcherInterface $currentTarget;

    protected bool $cancelled = false;

    protected bool $propagationStopped = false;
    protected bool $propagationStoppedImmediately = false;

    public function __construct(
        protected readonly string $name,
        protected mixed $context = null,
        protected readonly bool $bubbles = true,
        protected readonly bool $cancellable = true,
    ) {}

    public function stopImmediatePropagation(): void
    {
        $this->propagationStopped =
        $this->propagationStoppedImmediately = true;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    public function cancel(): void
    {
        if (!$this->cancellable) {
            throw new \LogicException("Cancelling an event which is not cancellable!");
        }

        if (!$this->cancelled) {
            $this->cancelled = true;
        }
    }

    /**
     * alias of Event::cancel()
     */
    public function preventDefault(): void
    {
        $this->cancel();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getContext(): mixed
    {
        return $this->context;
    }

    public function doesBubble(): bool
    {
        return $this->bubbles;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function isPropagationStoppedImmediately(): bool
    {
        return $this->propagationStoppedImmediately;
    }

    public function getTarget(): EventDispatcherInterface
    {
        return $this->target;
    }

    public function setTarget(EventDispatcherInterface $target): void
    {
        $this->target = $target;
    }

    public function getCurrentTarget(): EventDispatcherInterface
    {
        return $this->currentTarget;
    }

    public function setCurrentTarget(EventDispatcherInterface $currentTarget): void
    {
        $this->currentTarget = $currentTarget;
    }

    public function isCancelled(): bool
    {
        return $this->cancelled;
    }

    public function setContext(mixed $context): void
    {
        $this->context = $context;
    }
}
