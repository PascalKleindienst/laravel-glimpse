<?php

declare(strict_types=1);

namespace LaravelGlimpse;

use LaravelGlimpse\Contracts\QueryServiceContract;
use LaravelGlimpse\Exceptions\InvalidEventNameException;
use LaravelGlimpse\Services\EventDispatcher;
use LaravelGlimpse\Services\SessionTrackerService;
use Throwable;

final readonly class Glimpse
{
    public function __construct(
        private EventDispatcher $events,
        private SessionTrackerService $tracker,
        private QueryServiceContract $query,
    ) {}

    /**
     * Dispatch a custom named event, optionally with a payload.
     *
     * Examples:
     * ```
     *   Glimpse::event('signup');
     *   Glimpse::event('checkout', ['plan' => 'pro', 'value' => 49]);
     *   Glimpse::event('export', ['format' => 'csv'], $sessionHash);
     *```
     *
     * @param  array<array-key, mixed>  $properties
     *
     * @throws InvalidEventNameException
     */
    public function event(string $name, array $properties = [], ?string $sessionHash = null): void
    {
        $this->events->dispatch($name, $properties, $sessionHash);
    }

    /**
     * Like event() but silently discards invalid event names instead of
     * throwing. Safe to call in high-volume or untrusted-name contexts.
     *
     * @param  array<string, mixed>  $properties
     */
    public function dispatchSilently(string $name, array $properties = [], ?string $sessionHash = null): void
    {
        $this->events->dispatchSilently($name, $properties, $sessionHash);
    }

    /**
     * Convenience wrapper for tracking a virtual page view as an event.
     * Useful for SPAs or AJAX-heavy apps that want to log "page_view" events
     * without a full HTTP request.
     *
     * ```
     *   Glimpse::page('/checkout/step-2', 'Checkout — Step 2');
     * ```
     *
     * @throws InvalidEventNameException
     */
    public function page(string $path, ?string $title = null): void
    {
        $this->events->dispatch('page_view', array_filter([
            'path' => $path,
            'title' => $title,
        ]));
    }

    /**
     * Expose the QueryService directly for ergonomic access in controllers/views:
     *   Glimpse::query()->summary($range)
     */
    public function query(): QueryServiceContract
    {
        return $this->query;
    }

    /**
     * Retrieve the current visitor's anonymous session hash.
     * Useful when you want to associate server-side events dispatched
     * outside of the request lifecycle (e.g. from queued jobs).
     */
    public function currentSessionHash(): ?string
    {
        try {
            $request = request();

            if (! $request->session()->isStarted()) {
                return null;
            }

            return $this->tracker->resolveSessionHash($request);
        } catch (Throwable) {
            return null;
        }
    }
}
