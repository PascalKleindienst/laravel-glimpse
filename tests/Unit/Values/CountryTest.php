<?php

declare(strict_types=1);

use LaravelGlimpse\Values\Country;

it('creates country from valid ISO code', function (string $iso): void {
    Country::fromIso($iso);
})->throwsNoExceptions()->with([
    'uppercase' => ['US'],
    'lowercase' => ['us'],
    'mixed_case' => ['Us'],
]);

it('stores ISO code in uppercase', function (): void {
    expect(Country::fromIso('us')->iso)->toBe('US');
});

it('returns country name for valid ISO code', function (string $iso, string $expectedName): void {
    expect(Country::fromIso($iso)->name)->toBe($expectedName);
})->with([
    'US' => ['US', 'United States'],
    'GB' => ['GB', 'United Kingdom'],
    'DE' => ['DE', 'Germany'],
    'JP' => ['JP', 'Japan'],
    'FR' => ['FR', 'France'],
]);

it('returns ISO code as name for unknown country', function (): void {
    expect(Country::fromIso('XX')->name)->toBe('XX');
});

it('generates flag emoji for valid country codes', function (): void {
    expect(Country::fromIso('US')->flag)->toBe('🇺🇸')
        ->and(Country::fromIso('DE')->flag)->toBe('🇩🇪')
        ->and(Country::fromIso('JP')->flag)->toBe('🇯🇵');
});

it('returns country code as flag for unknown country', function (): void {
    expect(Country::fromIso('XX')->flag)->toBe('🇽🇽');
});

it('can be cast to string', function (): void {
    expect((string) Country::fromIso('US'))->toBe('US');
});

it('throws exception for empty string', function (): void {
    Country::fromIso('');
})->throws(InvalidArgumentException::class, 'Invalid country code: ');

it('throws exception for single character', function (): void {
    Country::fromIso('U');
})->throws(InvalidArgumentException::class, 'Invalid country code: U');

it('throws exception for three characters', function (): void {
    Country::fromIso('USA');
})->throws(InvalidArgumentException::class, 'Invalid country code: USA');

it('handles special characters in country names', function (string $name, string $iso): void {
    expect(Country::fromIso($iso)->name)->toBe($name);
})->with([
    ["Côte d'Ivoire", 'CI'],
    ['Korea (Democratic People\'s Republic)', 'KP'],
    ['Korea (Republic of)', 'KR'],
    ['Congo (Democratic Republic)', 'CD'],
]);
