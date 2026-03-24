<?php

declare(strict_types=1);

use LaravelGlimpse\Values\Os;

it('creates OS from name', function (string $name): void {
    $os = Os::from($name);

    expect((string) $os)->toBe($name)
        ->and($os->name)->toBe($name);
})->with([
    'windows' => ['Windows'],
    'macos' => ['macOS'],
    'ios' => ['iOS'],
    'android' => ['Android'],
    'linux' => ['Linux'],
    'ubuntu' => ['Ubuntu'],
]);

it('normalizes icons', function (string $os, string $icon): void {
    expect(Os::from($os)->icon)->toBe($icon);
})->with([
    ['Windows', 'windows'],
    ['windows 10', 'windows'],
    ['Windows Server 2019', 'windows'],
    ['Mac', 'mac'],
    ['MAC', 'mac'],
    ['macos Sonoma', 'mac'],
    ['macOS', 'mac'],
    ['Mac OS X', 'mac'],
    ['Android', 'android'],
    ['ANDROID', 'android'],
    ['android 14', 'android'],
    ['Android TV', 'android'],
    ['Linux', 'linux'],
    ['LINUX', 'linux'],
    ['linux', 'linux'],
]);

it('uses lowercase name as icon for unknown OS', function (string $name): void {
    expect(Os::from($name)->icon)->toBe(mb_strtolower($name));
})->with([
    'Chrome OS',
    'FreeBSD',
    'PlayStation',
    'Nintendo',
    'Ubuntu',
    'Debian',
    'Fedora',
]);

it('can be cast to string', function (): void {
    expect((string) Os::from('Windows 11'))->toBe('Windows 11');
});
