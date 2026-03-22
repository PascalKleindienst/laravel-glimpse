<?php

declare(strict_types=1);

namespace LaravelGlimpse\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LaravelGlimpse\Models\GlimpseEvent;

/**
 * Fired by the EventDispatcher after every successfull GlimpseEvent write.
 *
 * Developer can listen to this event to trigger side-effects such as
 * sending a Slack or Discord notification:
 *
 * ```
 * Event::listen(GlimpseEventDispatchedEvent::class, function(GlimpseEventDispatchedEvent $event) {
 *     if ($event->name === 'enterprise_signup') {
 *         // notifiy sales team
 *     }
 * }
 * ```
 */
final readonly class GlimpseEventDispatchedEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public function __construct(
        public string $name,
        public array $payload,
        public ?string $sessionHash,
        public GlimpseEvent $event,
    ) {}
}
