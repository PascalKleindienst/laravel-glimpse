<?php

declare(strict_types=1);

namespace LaravelGlimpse\Services;

use Illuminate\Support\Facades\Log;
use LaravelGlimpse\Models\GlimpseEvent;
use Throwable;

final readonly class EventDispatcher
{
    public function __construct(private SessionTrackerService $tracker) {}

    /**
     * Dispatch a named custom event, optionally with a key/value payload.
     *
     * Usage:
     *   Glimpse::event('checkout_completed', ['plan' => 'pro', 'value' => 99]);
     *
     * @param  array<string, mixed>  $properties
     */
    public function dispatch(string $name, array $properties = [], ?string $sessionHash = null): void
    {
        if (! config('glimpse.enabled', true)) {
            return;
        }

        $sessionHash ??= $this->resolveCurrentHash();

        GlimpseEvent::query()->create([
            'name' => $name,
            'properties' => $properties === [] ? null : $properties,
            'session_hash' => $sessionHash,
        ]);
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
