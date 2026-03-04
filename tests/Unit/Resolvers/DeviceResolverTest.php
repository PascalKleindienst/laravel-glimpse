<?php

declare(strict_types=1);

use hisorange\BrowserDetect\Contracts\ParserInterface;
use hisorange\BrowserDetect\Contracts\ResultInterface;
use Illuminate\Http\Request;
use LaravelGlimpse\Enums\Platform;
use LaravelGlimpse\Resolvers\DeviceResolver;

function mockResult(array $overrides = []): ResultInterface
{
    $default = [
        'userAgent' => 'Test',
        'isMobile' => false,
        'isTablet' => false,
        'isDesktop' => true,
        'isBot' => false,
        'isChrome' => false,
        'isFirefox' => false,
        'isOpera' => false,
        'isSafari' => false,
        'isEdge' => false,
        'isInApp' => false,
        'isIE' => false,
        'browserName' => 'Chrome',
        'browserFamily' => 'Chrome',
        'browserVersion' => '120.0',
        'browserVersionMajor' => 120,
        'browserVersionMinor' => 0,
        'browserVersionPatch' => 0,
        'browserEngine' => 'Blink',
        'platformName' => 'Windows 11',
        'platformFamily' => 'Windows',
        'platformVersion' => '11',
        'platformVersionMajor' => 11,
        'platformVersionMinor' => 0,
        'platformVersionPatch' => 0,
        'isWindows' => true,
        'isLinux' => false,
        'isMac' => false,
        'isAndroid' => false,
        'deviceFamily' => 'Unknown',
        'deviceModel' => '',
    ];

    return mock(ResultInterface::class, array_merge($default, $overrides));
}

it('returns unknown values for empty user agent', function (): void {
    // Arrange
    $request = Request::create('/');
    $request->headers->set('User-Agent', null);
    $parser = mock(ParserInterface::class)
        ->shouldReceive('parse')
        ->andReturnUsing(static fn ($ua): ResultInterface => mockResult(['userAgent' => $ua]))
        ->getMock();

    // Act
    $resolver = new DeviceResolver($parser);
    $result = $resolver->resolve($request);

    // Assert
    expect($result)->toBe([
        'browser' => null,
        'browser_version' => null,
        'os' => null,
        'os_version' => null,
        'platform' => Platform::Desktop,
        'is_bot' => false,
    ]);
});

it('returns desktop platform for desktop user agent', function (): void {
    // Arrange
    $request = Request::create('/');
    $request->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    $parser = mock(ParserInterface::class)
        ->shouldReceive('parse')
        ->andReturn(mockResult([
            'isDesktop' => true,
            'isMobile' => false,
            'isTablet' => false,
            'browserFamily' => 'Chrome',
            'browserVersion' => '120.0.0',
            'platformName' => 'Windows 11',
            'platformVersion' => '11',
        ]))
        ->getMock();

    // Act
    $resolver = new DeviceResolver($parser);
    $resolved = $resolver->resolve($request);

    // Assert
    expect($resolved['platform'])->toBe(Platform::Desktop)
        ->and($resolved['browser'])->toBe('Chrome')
        ->and($resolved['browser_version'])->toBe('120.0.0')
        ->and($resolved['os'])->toBe('Windows 11')
        ->and($resolved['os_version'])->toBe('11')
        ->and($resolved['is_bot'])->toBeFalse();
});

it('returns mobile platform for mobile user agent', function (): void {
    // Arrange
    $request = Request::create('/');
    $request->headers->set('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)');
    $parser = mock(ParserInterface::class)
        ->shouldReceive('parse')
        ->andReturn(mockResult([
            'isDesktop' => false,
            'isMobile' => true,
            'isTablet' => false,
            'browserFamily' => 'Safari',
            'browserVersion' => '16.0',
            'platformName' => 'iOS',
            'platformVersion' => '16.0',
        ]))
        ->getMock();

    // Act
    $resolver = new DeviceResolver($parser);
    $resolved = $resolver->resolve($request);

    // Assert
    expect($resolved['platform'])->toBe(Platform::Mobile)
        ->and($resolved['browser'])->toBe('Safari');
});

it('returns tablet platform for tablet user agent', function (): void {
    // Arrange
    $request = Request::create('/');
    $request->headers->set('User-Agent', 'Mozilla/5.0 (iPad; CPU OS 16_0 like Mac OS X)');
    $parser = mock(ParserInterface::class)
        ->shouldReceive('parse')
        ->andReturn(mockResult([
            'isDesktop' => false,
            'isMobile' => false,
            'isTablet' => true,
            'browserFamily' => 'Safari',
            'browserVersion' => '16.0',
            'platformName' => 'iPadOS',
            'platformVersion' => '16.0',
        ]))
        ->getMock();

    // Act
    $resolver = new DeviceResolver($parser);
    $resolved = $resolver->resolve($request);

    // Assert
    expect($resolved['platform'])->toBe(Platform::Tablet)
        ->and($resolved['browser'])->toBe('Safari');
});

it('returns bot platform for bot user agent', function (): void {
    // Arrange
    $request = Request::create('/');
    $request->headers->set('User-Agent', 'Googlebot/2.1 (+http://www.google.com/bot.html)');
    $parser = mock(ParserInterface::class)
        ->shouldReceive('parse')
        ->andReturn(mockResult([
            'isDesktop' => false,
            'isMobile' => false,
            'isTablet' => false,
            'isBot' => true,
            'browserFamily' => 'Bot',
            'browserVersion' => '',
            'platformName' => 'Unknown',
            'platformVersion' => '',
        ]))
        ->getMock();

    // Act
    $resolver = new DeviceResolver($parser);
    $resolved = $resolver->resolve($request);

    // Assert
    expect($resolved['platform'])->toBe(Platform::Bot)
        ->and($resolved['is_bot'])->toBeTrue()
        ->and($resolved['browser'])->toBe('Bot');
});

it('returns null for missing browser family', function (): void {
    // Arrange
    $request = Request::create('/');
    $request->headers->set('User-Agent', 'Test UA');
    $parser = mock(ParserInterface::class)
        ->shouldReceive('parse')
        ->andReturn(mockResult([
            'browserFamily' => '',
            'browserVersion' => '',
        ]))
        ->getMock();

    // Act
    $resolver = new DeviceResolver($parser);
    $resolved = $resolver->resolve($request);

    // Assert
    expect($resolved['browser'])->toBeNull()
        ->and($resolved['browser_version'])->toBeNull();
});

it('returns null for missing platform name', function (): void {
    // Arrange
    $request = Request::create('/');
    $request->headers->set('User-Agent', 'Test UA');
    $parser = mock(ParserInterface::class)
        ->shouldReceive('parse')
        ->andReturn(mockResult([
            'platformName' => '',
            'platformVersion' => '',
        ]))
        ->getMock();

    // Act
    $resolver = new DeviceResolver($parser);
    $resolved = $resolver->resolve($request);

    // Assert
    expect($resolved['os'])->toBeNull()
        ->and($resolved['os_version'])->toBeNull();
});

it('isBot returns false for empty user agent', function (): void {
    // Arrange
    $request = Request::create('/');
    $parser = mock(ParserInterface::class)
        ->shouldReceive('parse')
        ->andReturnUsing(static fn ($ua): ResultInterface => mockResult(['userAgent' => null]))
        ->getMock();

    // Act
    $resolver = new DeviceResolver($parser);
    $result = $resolver->isBot($request);

    // Assert
    expect($result)->toBeFalse();
});

it('isBot returns true for bot user agent', function (): void {
    // Arrange
    $request = Request::create('/');
    $request->headers->set('User-Agent', 'Googlebot/2.1');
    $parser = mock(ParserInterface::class)
        ->shouldReceive('parse')
        ->andReturn(mockResult(['isBot' => true]))
        ->getMock();

    // Act
    $resolver = new DeviceResolver($parser);
    $isBot = $resolver->isBot($request);

    expect($isBot)->toBeTrue();
});

it('isBot returns false for regular user agent', function (): void {
    // Arrange
    $request = Request::create('/');
    $request->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0');
    $parser = mock(ParserInterface::class)
        ->shouldReceive('parse')
        ->andReturn(mockResult(['isBot' => false]))
        ->getMock();

    // Act
    $resolver = new DeviceResolver($parser);
    $isBot = $resolver->isBot($request);

    expect($isBot)->toBeFalse();
});
