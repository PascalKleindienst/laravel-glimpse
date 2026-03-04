<?php

declare(strict_types=1);

namespace LaravelGlimpse\Facades;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use LaravelGlimpse\Models\GlimpsePageView;
use LaravelGlimpse\Models\GlimpseSession;

/**
 * @method static string resolveSessionHash(Request $request)
 * @method static string hashIp(string $ip)
 * @method static GlimpseSession|null getCachedSession(string $hash)
 * @method static GlimpseSession createSession(array<string, mixed> $attributes)
 * @method static void updateSession(GlimpseSession $session, string $path, CarbonImmutable $now)
 * @method static GlimpsePageView recordPageView(string $sessionHash, Request $request, DateTimeInterface $now)
 * @method static bool isActive(string $hash)
 *
 * @see \LaravelGlimpse\Services\SessionTrackerService
 */
final class SessionTrackerService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \LaravelGlimpse\Services\SessionTrackerService::class;
    }
}
