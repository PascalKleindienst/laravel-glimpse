<?php

declare(strict_types=1);

namespace LaravelGlimpse;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

/**
 * Controls who may access the analytics dashboard
 *
 * By default, only authenticated users can see it (see 'glimpse.middleware' config)
 *
 * For finer-grained control, you can use the `GlimpseGate::using` method, to register a custom authorization callback.
 * For example:
 *
 * ```
 * GlimpseGate::using(fn (Request $request) => $request->user()->is_admin);
 * ```
 */
final class GlimpseGate
{
    /**
     * @var (callable(Request): bool)|null
     */
    private static $callback;

    /**
     * Register a custom authorization callback.
     *
     * @param  callable(Request): bool  $callback
     */
    public static function using(callable $callback): void
    {
        self::$callback = $callback;
    }

    /**
     * Determine whether the current request is authorized to view glimpse.
     */
    public static function check(Request $request): bool
    {
        if (self::$callback !== null) {
            return (bool) (self::$callback)($request);
        }

        // Default: any authenticated user.
        return $request->user() instanceof Authenticatable;
    }

    /**
     * Reset the gate to the default behavior (useful in tests).
     */
    public static function reset(): void
    {
        self::$callback = null;
    }
}
