<?php

declare(strict_types=1);

namespace LaravelGlimpse\Facades;

use Illuminate\Support\Facades\Facade;
use LaravelGlimpse\Contracts\QueryServiceContract;

/**
 * @method static void event(string $event, array<string, mixed> $properties = [], string|null $sessionHash = null)
 * @method static void dispatchSilently(string $name, array<string, mixed> $properties = [], string|null $sessionHash = null)
 * @method static void page(string $path, string|null $title = null)
 * @method static QueryServiceContract query()
 * @method static string|null currentSessionHash()
 *
 * @see \LaravelGlimpse\Glimpse
 */
final class Glimpse extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \LaravelGlimpse\Glimpse::class;
    }
}
