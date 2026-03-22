<?php

declare(strict_types=1);

namespace LaravelGlimpse\Services;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use LaravelGlimpse\Events\GlimpseEventDispatchedEvent;
use LaravelGlimpse\Exceptions\InvalidEventNameException;
use LaravelGlimpse\Models\GlimpseEvent;
use LaravelGlimpse\Values\EventName;
use Throwable;

final readonly class EventDispatcher
{
    public function __construct(private SessionTrackerService $tracker) {}

    /**
     * Dispatch a named custom event, optionally with a key/value payload.
     *
     * The event name is validated: only letters, numbers, underscores,
     * hyphens, and dots are allowed, max 100 characters.
     *
     * Usage:
     *   Glimpse::event('checkout_completed', ['plan' => 'pro', 'value' => 99]);
     *
     * @param  array<string, mixed>  $properties
     *
     * @throws InvalidEventNameException if name is invalid
     */
    public function dispatch(string $name, array $properties = [], ?string $sessionHash = null): void
    {
        if (! config('glimpse.enabled', true)) {
            return;
        }

        // Validate and normalize the event name — throws on invalid input.
        $eventName = EventName::from($name);
        $sessionHash ??= $this->resolveCurrentHash();

        $model = GlimpseEvent::query()->create([
            'name' => $name,
            'properties' => $properties === [] ? null : $properties,
            'session_hash' => $sessionHash,
            'created_at' => now(),
        ]);

        // Fire a Laravel event so developers can hook in listeners.
        Event::dispatch(new GlimpseEventDispatchedEvent(
            name: (string) $eventName,
            payload: $properties,
            sessionHash: $sessionHash,
            event: $model,
        ));
    }

    /**
     * Like dispatch(), but silently discards invalid event names instead of
     * throwing. Useful in high-volume paths where you'd rather lose a
     * tracking event than generate an exception.
     *
     * @param  array<string, mixed>  $properties
     */
    public function dispatchSilently(string $name, array $properties = [], ?string $sessionHash = null): void
    {
        try {
            $this->dispatch($name, $properties, $sessionHash);
        } catch (InvalidEventNameException $e) {
            Log::debug('Glimpse: invalid event name silently discarded.', [
                'name' => $name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolveCurrentHash(): ?string
    {
        try {
            $request = request();

            if (! $request->hasSession()) {
                return null;
            }

            return $this->tracker->resolveSessionHash($request);
        } catch (Throwable $e) {
            Log::debug('Glimpse: could not resolve session hash for event.', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
