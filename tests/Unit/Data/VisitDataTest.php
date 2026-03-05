<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use LaravelGlimpse\Data\VisitData;
use LaravelGlimpse\Facades\SessionTrackerService;

it('creates VisitData with all properties', function (): void {
    $hitAt = CarbonImmutable::now();
    $data = new VisitData(
        sessionHash: 'abc123',
        ipHash: 'def456',
        fullUrl: 'https://example.com/page',
        path: '/page',
        queryString: 'foo=bar',
        userAgent: 'Mozilla/5.0',
        referer: 'https://google.com',
        acceptLanguage: 'en-US',
        ip: '192.168.1.1',
        hitAt: $hitAt,
        isNewSession: true,
    );

    expect($data->sessionHash)->toBe('abc123')
        ->and($data->ipHash)->toBe('def456')
        ->and($data->fullUrl)->toBe('https://example.com/page')
        ->and($data->path)->toBe('/page')
        ->and($data->queryString)->toBe('foo=bar')
        ->and($data->userAgent)->toBe('Mozilla/5.0')
        ->and($data->referer)->toBe('https://google.com')
        ->and($data->acceptLanguage)->toBe('en-US')
        ->and($data->ip)->toBe('192.168.1.1')
        ->and($data->hitAt)->toBe($hitAt)
        ->and($data->isNewSession)->toBeTrue();
});

it('creates VisitData from request', function (): void {
    $request = Request::create('/test?foo=bar', 'GET');
    $request->headers->set('User-Agent', 'TestAgent');
    $request->headers->set('Referer', 'https://referrer.com');
    $request->headers->set('Accept-Language', 'en-US');
    $request->setLaravelSession(session()->driver('array'));

    $sessionHash = SessionTrackerService::resolveSessionHash($request);
    $ipHash = SessionTrackerService::hashIp($request->ip());
    $data = VisitData::from($request);

    expect($data->sessionHash)->toBe($sessionHash)
        ->and($data->ipHash)->toBe($ipHash)
        ->and($data->fullUrl)->toContain('/test')
        ->and($data->path)->toBe('/test')
        ->and($data->queryString)->toBe('foo=bar')
        ->and($data->userAgent)->toBe('TestAgent')
        ->and($data->referer)->toBe('https://referrer.com')
        ->and($data->acceptLanguage)->toBe('en-US')
        ->and($data->ip)->toBe('127.0.0.1');
});

it('converts VisitData to request', function (): void {
    $hitAt = CarbonImmutable::now();
    $data = new VisitData(
        sessionHash: 'abc123',
        ipHash: 'def456',
        fullUrl: 'https://example.com/page?foo=bar',
        path: '/page',
        queryString: 'foo=bar',
        userAgent: 'Mozilla/5.0',
        referer: 'https://google.com',
        acceptLanguage: 'en-US',
        ip: '192.168.1.1',
        hitAt: $hitAt,
        isNewSession: true,
    );

    $request = $data->toRequest();

    expect($request->fullUrl())->toContain('/page')
        ->and($request->headers->get('User-Agent'))->toBe('Mozilla/5.0')
        ->and($request->headers->get('Referer'))->toBe('https://google.com')
        ->and($request->headers->get('Accept-Language'))->toBe('en-US')
        ->and($request->ip())->toBe('192.168.1.1');
});

it('converts VisitData to array', function (): void {
    $data = new VisitData(
        sessionHash: 'abc123',
        ipHash: 'def456',
        fullUrl: 'https://example.com/page',
        path: '/page',
        queryString: 'foo=bar',
        userAgent: 'Mozilla/5.0',
        referer: 'https://google.com',
        acceptLanguage: 'en-US',
        ip: '192.168.1.1',
        hitAt: CarbonImmutable::createFromFormat('Y-m-d H:i:s', '2024-01-15 10:30:45'),
        isNewSession: true,
    );

    $array = $data->toArray();

    expect($array)->toBe([
        'sessionHash' => 'abc123',
        'ipHash' => 'def456',
        'fullUrl' => 'https://example.com/page',
        'path' => '/page',
        'queryString' => 'foo=bar',
        'userAgent' => 'Mozilla/5.0',
        'referer' => 'https://google.com',
        'acceptLanguage' => 'en-US',
        'ip' => '192.168.1.1',
        'hitAt' => '2024-01-15 10:30:45.000000',
        'isNewSession' => true,
    ]);
});

it('implements Arrayable', function (): void {
    $data = new VisitData(
        sessionHash: 'hash',
        ipHash: 'ip-hash',
        fullUrl: 'https://example.com',
        path: '/',
        queryString: null,
        userAgent: null,
        referer: null,
        acceptLanguage: null,
        ip: '127.0.0.1',
        hitAt: CarbonImmutable::now(),
        isNewSession: false,
    );

    expect($data)->toBeInstanceOf(Arrayable::class);
});
