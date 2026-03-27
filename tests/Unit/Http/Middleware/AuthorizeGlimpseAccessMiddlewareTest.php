<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use LaravelGlimpse\GlimpseGate;
use LaravelGlimpse\Http\Middleware\AuthorizeGlimpseAccessMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    GlimpseGate::reset();
});

it('allows authorized request through', function (): void {
    GlimpseGate::using(fn (): true => true);

    $middleware = resolve(AuthorizeGlimpseAccessMiddleware::class);
    $request = Request::create('/glimpse');

    $response = $middleware->handle($request, fn (Request $req): Response => new Response('OK', 200));

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toBe('OK');
});

it('blocks unauthorized request with 403', function (): void {
    GlimpseGate::using(fn (): false => false);

    $middleware = resolve(AuthorizeGlimpseAccessMiddleware::class);
    $request = Request::create('/glimpse');

    $middleware->handle($request, fn (): Response => new Response('OK'));
})->throws(HttpException::class, 'Unauthorized access to glimpse analytics dashboard');

it('uses default gate behavior when no custom callback', function (): void {
    $middleware = resolve(AuthorizeGlimpseAccessMiddleware::class);
    $request = Request::create('/glimpse');
    $middleware->handle($request, fn (): Response => new Response('OK'));
})->throws(HttpException::class, 'Unauthorized access to glimpse analytics dashboard');

it('passes request to next handler when authorized', function (): void {
    GlimpseGate::using(fn (): true => true);

    $middleware = resolve(AuthorizeGlimpseAccessMiddleware::class);
    $request = Request::create('/glimpse');
    $nextCalled = false;

    $middleware->handle($request, function (Request $req) use (&$nextCalled): Response {
        $nextCalled = true;

        return new Response('Dashboard', 200);
    });

    expect($nextCalled)->toBeTrue();
});
