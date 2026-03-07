<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use LaravelGlimpse\Resolvers\GeoResolver;

use function Pest\testDirectory;

it('accepts driver via constructor', function (string $driver): void {
    $resolver = new GeoResolver($driver);
    $request = Request::create('/');

    $result = $resolver->resolve($request);

    expect($result['country_code'])->toBeNull();
})->with(['null', 'unknown']);

it('returns array with correct keys', function (): void {
    $resolver = new GeoResolver('null');
    $request = Request::create('/');

    $result = $resolver->resolve($request);

    expect(array_keys($result))->toBe(['country_code', 'region', 'city']);
});

it('handles request with multiple forwarded IPs', function (): void {
    $resolver = new GeoResolver('null');
    $request = Request::create('/');
    $request->headers->set('X-Forwarded-For', '203.0.113.1, 198.51.100.1');

    $result = $resolver->resolve($request);

    expect($result['country_code'])->toBeNull();
});

describe('max mind driver', function (): void {
    beforeEach(function (): void {
        config([
            'glimpse.geo.driver' => 'maxmind',
            'glimpse.geo.maxmind_db' => testDirectory('fixtures/maxmind.mmdb'),
        ]);

        File::shouldReceive('exists')
            ->with(testDirectory('fixtures/maxmind.mmdb'))
            ->andReturn(true);
    });

    it('returns empty values when IP is missing', function (): void {
        $resolver = resolve(GeoResolver::class);
        $request = Request::create('/');
        $request->server->set('REMOTE_ADDR', '');
        $request->headers->set('X-Forwarded-For', '');

        $result = $resolver->resolve($request);

        expect($result['country_code'])->toBeNull()
            ->and($result['region'])->toBeNull()
            ->and($result['city'])->toBeNull();
    });

    it('returns empty values when maxmind database does not exist', function (): void {
        config(['glimpse.geo.maxmind_db' => storage_path('app/does-not-exist.mmdb')]);
        File::shouldReceive('exists')
            ->with(storage_path('app/does-not-exist.mmdb'))
            ->andReturn(false);

        $resolver = resolve(GeoResolver::class);
        $request = Request::create('/');
        $result = $resolver->resolve($request);
        expect($result['country_code'])->toBeNull();
    });
});

describe('sxgeo driver', function (): void {
    beforeEach(function (): void {
        config([
            'glimpse.geo.driver' => 'sxgeo',
        ]);

        File::shouldReceive('exists')
            ->with(testDirectory('fixtures/sxgeo.dat'))
            ->andReturn(true);
    });

    it('returns empty values when sxgeo database does not exist', function (): void {
        config(['glimpse.geo.sxgeo_db' => storage_path('app/does-not-exist.dat')]);
        File::shouldReceive('exists')
            ->with(storage_path('app/does-not-exist.dat'))
            ->andReturn(false);

        $resolver = new GeoResolver('sxgeo');
        $request = Request::create('/');

        $result = $resolver->resolve($request);

        expect($result['country_code'])->toBeNull();
    });
});
