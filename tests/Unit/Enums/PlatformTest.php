<?php

declare(strict_types=1);

use LaravelGlimpse\Enums\Platform;

it('has value property matching enum name in lowercase', function (Platform $platform): void {
    expect($platform->value)->toBe(mb_strtolower($platform->name));
})->with([
    'desktop' => [Platform::Desktop],
    'mobile' => [Platform::Mobile],
    'tablet' => [Platform::Tablet],
    'bot' => [Platform::Bot],
]);

it('returns correct icons', function (Platform $platform, string $expectedIcon): void {
    expect($platform->icon())->toBe($expectedIcon);
})->with([
    'desktop' => [Platform::Desktop, '🖥'],
    'mobile' => [Platform::Mobile, '📱'],
    'tablet' => [Platform::Tablet, '⬛'],
    'bot' => [Platform::Bot, '🤖'],
]);
