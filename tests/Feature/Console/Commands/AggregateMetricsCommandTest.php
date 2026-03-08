<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use LaravelGlimpse\Contracts\AggregationServiceContract;
use LaravelGlimpse\Data\AggregationResult;
use Mockery\MockInterface;

use function Pest\Laravel\artisan;
use function Pest\Laravel\mock;

beforeEach(function (): void {
    $this->mock = mock(AggregationServiceContract::class);
    app()->bind(AggregationServiceContract::class, fn (): MockInterface => $this->mock);
    config(['glimpse.enabled' => true]);
});

it('skips aggregation when glimpse is disabled', function (): void {
    config(['glimpse.enabled' => false]);

    artisan('glimpse:aggregate')
        ->assertSuccessful()
        ->expectsOutputToContain('Glimpse is disabled');
});

it('skips aggregation when time window is empty', function (): void {
    $this->mock
        ->shouldReceive('aggregate')
        ->never();

    artisan('glimpse:aggregate', [
        '--from' => '2024-01-01 10:00:00',
        '--to' => '2024-01-01 10:00:00',
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('Nothing to aggregate');
});

it('displays aggregation information message', function (): void {
    $this->mock
        ->shouldReceive('aggregate')
        ->once()
        ->andReturn(new AggregationResult(
            CarbonImmutable::parse('2024-01-01 00:00:00'),
            CarbonImmutable::parse('2024-01-01 12:00:00'),
        ));

    artisan('glimpse:aggregate', [
        '--from' => '2024-01-01 00:00:00',
        '--to' => '2024-01-01 12:00:00',
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('Aggregating Glimpse data');
});

it('uses cached last run time as default --from', function (): void {
    Cache::put('glimpse:aggregate:last_run', '2024-01-01 06:00:00');

    $this->mock
        ->shouldReceive('aggregate')
        ->once()
        ->withArgs(fn ($from, $to): bool => $from->toDateTimeString() === '2024-01-01 06:00:00')
        ->andReturn(new AggregationResult(
            CarbonImmutable::parse('2024-01-01 06:00:00'),
            CarbonImmutable::now(),
        ));

    artisan('glimpse:aggregate')
        ->assertSuccessful();
});

it('falls back to 6 hours ago when no cached last run', function (): void {
    $this->mock
        ->shouldReceive('aggregate')
        ->once()
        ->withArgs(fn ($from, $to) => $from->gte(CarbonImmutable::now()->subHours(7)))
        ->andReturn(new AggregationResult(
            CarbonImmutable::now()->subHours(6),
            CarbonImmutable::now(),
        ));

    artisan('glimpse:aggregate')
        ->assertSuccessful();
});

it('respects --from option', function (): void {
    $this->mock
        ->shouldReceive('aggregate')
        ->once()
        ->withArgs(fn ($from, $to): bool => $from->toDateTimeString() === '2024-01-15 08:00:00')
        ->andReturn(new AggregationResult(
            CarbonImmutable::parse('2024-01-15 08:00:00'),
            CarbonImmutable::parse('2024-01-15 14:00:00'),
        ));

    artisan('glimpse:aggregate', [
        '--from' => '2024-01-15 08:00:00',
        '--to' => '2024-01-15 14:00:00',
    ])
        ->assertSuccessful();
});

it('respects --to option', function (): void {
    $this->mock
        ->shouldReceive('aggregate')
        ->once()
        ->withArgs(fn ($from, $to): bool => $to->toDateTimeString() === '2024-01-20 23:59:59')
        ->andReturn(new AggregationResult(
            CarbonImmutable::parse('2024-01-20 12:00:00'),
            CarbonImmutable::parse('2024-01-20 23:59:59'),
        ));

    artisan('glimpse:aggregate', [
        '--from' => '2024-01-20 12:00:00',
        '--to' => '2024-01-20 23:59:59',
    ])
        ->assertSuccessful();
});

it('updates last run cache after successful aggregation', function (): void {
    $this->mock
        ->shouldReceive('aggregate')
        ->once()
        ->andReturn(new AggregationResult(
            CarbonImmutable::parse('2024-01-01 00:00:00'),
            CarbonImmutable::parse('2024-01-01 12:00:00'),
        ));

    artisan('glimpse:aggregate', [
        '--from' => '2024-01-01 00:00:00',
        '--to' => '2024-01-01 12:00:00',
    ])
        ->assertSuccessful();

    expect(Cache::get('glimpse:aggregate:last_run'))->toBe('2024-01-01 12:00:00');
});

it('displays processed counts in output', function (): void {
    $this->mock
        ->shouldReceive('aggregate')
        ->once()
        ->andReturn(new AggregationResult(
            CarbonImmutable::parse('2024-01-01 00:00:00'),
            CarbonImmutable::parse('2024-01-01 12:00:00'),
            sessionsProcessed: 150,
            pageViewsProcessed: 450,
            eventsProcessed: 75,
            duration: 123.45,
        ));

    artisan('glimpse:aggregate', [
        '--from' => '2024-01-01 00:00:00',
        '--to' => '2024-01-01 12:00:00',
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('Sessions processed')
        ->expectsOutputToContain('Page views processed')
        ->expectsOutputToContain('Events processed')
        ->expectsOutputToContain('Duration');
});

it('displays completion message', function (): void {
    $this->mock
        ->shouldReceive('aggregate')
        ->once()
        ->andReturn(new AggregationResult(
            CarbonImmutable::parse('2024-01-01 00:00:00'),
            CarbonImmutable::parse('2024-01-01 12:00:00'),
        ));

    artisan('glimpse:aggregate', [
        '--from' => '2024-01-01 00:00:00',
        '--to' => '2024-01-01 12:00:00',
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('Aggregation complete');
});

it('returns success exit code', function (): void {
    $this->mock
        ->shouldReceive('aggregate')
        ->once()
        ->andReturn(new AggregationResult(
            CarbonImmutable::now()->subHour(),
            CarbonImmutable::now(),
        ));

    artisan('glimpse:aggregate')
        ->assertSuccessful();
});

it('respects --force option to re-aggregate', function (): void {
    Cache::put('glimpse:aggregate:last_run', '2024-01-01 00:00:00');

    $this->mock
        ->shouldReceive('aggregate')
        ->once()
        ->withArgs(fn ($from, $to): bool => $from->toDateTimeString() === '2024-01-01 00:00:00')
        ->andReturn(new AggregationResult(
            CarbonImmutable::parse('2024-01-01 00:00:00'),
            CarbonImmutable::parse('2024-01-01 12:00:00'),
        ));

    artisan('glimpse:aggregate', [
        '--from' => '2024-01-01 00:00:00',
        '--to' => '2024-01-01 12:00:00',
        '--force' => true,
    ])
        ->assertSuccessful();
});
