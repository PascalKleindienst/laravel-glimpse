<?php

declare(strict_types=1);

use LaravelGlimpse\Exceptions\InvalidEventNameException;
use LaravelGlimpse\Glimpse;

if (! function_exists('glimpse')) {
    /**
     * Access the Glimpse analytics facade conveniently.
     *
     * Returns the Glimpse singleton when called with no arguments,
     * or dispatches an event immediately when called with a name:
     *
     * ```
     *   glimpse();                            // → \LaravelGlimpse\Glimpse instance
     *   glimpse('signup');                    // dispatch event
     *   glimpse('checkout', ['plan'=>'pro']); // dispatch event with payload
     *  ```
     *
     * @param  array<array-key, mixed>  $payload
     *
     * @throws InvalidEventNameException
     */
    function glimpse(?string $event = null, array $payload = []): mixed
    {
        $glimpse = resolve(Glimpse::class);

        if ($event !== null) {
            $glimpse->event($event, $payload);

            return null;
        }

        return $glimpse;
    }
}
