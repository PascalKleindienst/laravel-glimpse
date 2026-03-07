<?php

declare(strict_types=1);

namespace Workbench\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SpoofIpMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->server->set('REMOTE_ADDR', '9.9.9.9');

        return $next($request);
    }
}
