<?php

declare(strict_types=1);

use hisorange\BrowserDetect\Contracts\ParserInterface;
use hisorange\BrowserDetect\Contracts\ResultInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use LaravelGlimpse\Http\Middleware\TrackVisitorMiddleware;
use LaravelGlimpse\Jobs\ProcessVisitJob;
use Mockery\MockInterface;

function createRequest(array $overrides = [], string $url = '/', string $method = 'GET'): Request
{
    $request = Request::create($url, $method, [], [], [], [
        'HTTP_HOST' => 'example.com',
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0',
    ]);

    if (isset($overrides['session'])) {
        $request->setLaravelSession($overrides['session']);
    }

    if (isset($overrides['expectsJson'])) {
        $request->headers->set('Accept', $overrides['expectsJson'] ? 'application/json' : 'text/html');
    }

    if (isset($overrides['ip'])) {
        $request->server->set('REMOTE_ADDR', $overrides['ip']);
    }

    return $request;
}

function createMockSession(bool $started = true): MockInterface
{
    $session = mock(Store::class);
    $session->shouldReceive('getId')->andReturn('test-session-id');
    $session->shouldReceive('isStarted')->andReturn($started);

    return $session;
}

beforeEach(function (): void {
    config(['glimpse.enabled' => true]);
});

it('does not track when glimpse is disabled', function (): void {
    config(['glimpse.enabled' => false]);

    $middleware = resolve(TrackVisitorMiddleware::class);
    $request = createRequest(['session' => createMockSession()]);

    $response = $middleware->handle($request, fn (Request $req): Response => new Response('OK'));

    expect($response->getContent())->toBe('OK');
    Bus::assertNotDispatched(ProcessVisitJob::class);
});

it('does not track non-GET requests', function (): void {
    $middleware = resolve(TrackVisitorMiddleware::class);
    $request = createRequest(['session' => createMockSession()], method: 'POST');

    $response = $middleware->handle($request, fn (Request $req): Response => new Response('OK'));

    expect($response->getContent())->toBe('OK');
    Bus::assertNotDispatched(ProcessVisitJob::class);
});

it('does not track requests expecting JSON', function (): void {
    $middleware = resolve(TrackVisitorMiddleware::class);
    $request = createRequest([
        'session' => createMockSession(),
        'expectsJson' => true,
    ]);

    $response = $middleware->handle($request, fn (Request $req): Response => new Response('OK'));

    expect($response->getContent())->toBe('OK');
    Bus::assertNotDispatched(ProcessVisitJob::class);
});

it('does not track excluded paths', function (): void {
    config(['glimpse.exclude.paths' => ['admin/*', 'api/*']]);

    $middleware = resolve(TrackVisitorMiddleware::class);
    $request = createRequest(['session' => createMockSession()], url: '/admin/dashboard');

    $response = $middleware->handle($request, fn (Request $req): Response => new Response('OK'));

    expect($response->getContent())->toBe('OK');
    Bus::assertNotDispatched(ProcessVisitJob::class);
});

it('does not track excluded IPs', function (): void {
    config(['glimpse.exclude.ips' => ['192.168.1.1', '10.0.0.1']]);

    $middleware = resolve(TrackVisitorMiddleware::class);

    $request = createRequest([
        'session' => createMockSession(),
        'ip' => '192.168.1.1',
    ]);

    $response = $middleware->handle($request, fn (Request $req): Response => new Response('OK'));

    expect($response->getContent())->toBe('OK');
    Bus::assertNotDispatched(ProcessVisitJob::class);
});

it('does not track bots when bot exclusion is enabled', function (): void {
    config(['glimpse.exclude.bots' => true]);
    app()->bind(ParserInterface::class, fn () => mock(ParserInterface::class)
        ->shouldReceive('parse')
        ->andReturnUsing(static fn ($ua): ResultInterface => mockParserResult(['userAgent' => $ua, 'isBot' => true]))
        ->getMock());

    $middleware = resolve(TrackVisitorMiddleware::class);

    $request = createRequest(['session' => createMockSession()]);

    $response = $middleware->handle($request, fn (Request $req): Response => new Response('OK'));

    expect($response->getContent())->toBe('OK');
    Bus::assertNotDispatched(ProcessVisitJob::class);
});

it('tracks bots when bot exclusion is disabled', function (): void {
    config(['glimpse.exclude.bots' => false]);
    app()->bind(ParserInterface::class, fn () => mock(ParserInterface::class)
        ->shouldReceive('parse')
        ->andReturnUsing(static fn ($ua): ResultInterface => mockParserResult(['userAgent' => $ua, 'isBot' => true]))
        ->getMock());

    $middleware = resolve(TrackVisitorMiddleware::class);

    $request = createRequest(['session' => createMockSession()]);

    $middleware->handle($request, fn (Request $req): Response => new Response('OK'));

    Bus::assertDispatched(ProcessVisitJob::class);
});

it('does not track requests without session', function (): void {
    config(['glimpse.enabled' => true]);

    $middleware = resolve(TrackVisitorMiddleware::class);

    $request = createRequest();

    $response = $middleware->handle($request, fn (Request $req): Response => new Response('OK'));

    expect($response->getContent())->toBe('OK');
    Bus::assertNotDispatched(ProcessVisitJob::class);
});

it('does not track requests with unstarted session', function (): void {

    $middleware = resolve(TrackVisitorMiddleware::class);

    $request = createRequest(['session' => createMockSession(false)]);

    $response = $middleware->handle($request, fn (Request $req): Response => new Response('OK'));

    expect($response->getContent())->toBe('OK');
    Bus::assertNotDispatched(ProcessVisitJob::class);
});

it('dispatches job and caches new session for valid GET requests', function (): void {
    $middleware = resolve(TrackVisitorMiddleware::class);

    $request = createRequest(['session' => createMockSession()]);
    $hash = hash('sha256', $request->session()->getId());
    $middleware->handle($request, fn (Request $req): Response => new Response('OK'));

    expect(Cache::get('glimpse:session:'.$hash))->toBe('pending');
});

it('handles null IP correctly in exclusion check', function (): void {
    config(['glimpse.exclude.ips' => ['192.168.1.1']]);

    $middleware = resolve(TrackVisitorMiddleware::class);

    $request = createRequest(['session' => createMockSession(), 'ip' => '']);

    $middleware->handle($request, fn (Request $req): Response => new Response('OK'));

    Bus::assertDispatched(ProcessVisitJob::class);
});
