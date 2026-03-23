<?php

declare(strict_types=1);

namespace LaravelGlimpse\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use LaravelGlimpse\GlimpseGate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applied automatically to all dashboard routes.
 * Delegates the authorization decision to the `GlimpseGate`, which defaults to any authenticated user
 * but can be overriden per-project:
 *
 * ```
 * GlimpseGate::using(fn (Request $request) => $request->user()->is_admin);
 * ```
 */
final readonly class AuthorizeGlimpseAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(GlimpseGate::check($request), 403, 'Unauthorized access to glimpse analytics dashboard');

        return $next($request);
    }
}
