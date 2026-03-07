<?php

declare(strict_types=1);

use hisorange\BrowserDetect\Contracts\ResultInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use LaravelGlimpse\Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        Sleep::fake();
        Model::unguard();
        Http::preventStrayRequests();
        Bus::fake();
        Date::setTestNow();

        Queue::fake();
    })
    ->afterEach(function (): void {
        Str::createUuidsNormally();
        Sleep::fake(false);
    })
    ->in('Unit', 'Feature');

function mockParserResult(array $overrides = []): ResultInterface
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
