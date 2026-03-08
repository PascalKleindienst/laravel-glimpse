<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use LaravelGlimpse\Contracts\AggregationServiceContract;
use LaravelGlimpse\Data\AggregationResult;
use Mockery\MockInterface;

use function Pest\Laravel\artisan;
use function Pest\Laravel\mock;

beforeEach(function (): void {
    $this->mock = mock(AggregationServiceContract::class);
    app()->bind(AggregationServiceContract::class, fn (): MockInterface => $this->mock);
});

it('displays backfill information message', function (): void {
    $this->mock
        ->shouldReceive('aggregate')
        ->once()
        ->andReturn(new AggregationResult(
            CarbonImmutable::now()->subDay()->startOfDay(),
            CarbonImmutable::now()->endOfDay(),
        ));

    artisan('glimpse:backfill', ['--from' => '2026-01-01', '--to' => '2026-01-01'])
        ->assertSuccessful()
        ->expectsOutputToContain('Backfilling 1 day(s)');
});

it('uses default 90-day range when no options provided', function (): void {
    $this->mock
        ->shouldReceive('aggregate')
        ->times(13) // 90 day/7 day chunks => 12.85
        ->andReturn(new AggregationResult(
            CarbonImmutable::now()->subDays(89)->startOfDay(),
            CarbonImmutable::now()->endOfDay(),
        ));

    artisan('glimpse:backfill')
        ->assertSuccessful();
});

it('respects --from option', function (): void {
    $this->mock
        ->shouldReceive('aggregate')
        ->once()
        ->withArgs(fn ($from, $to): bool => $from->toDateString() === '2023-06-15')
        ->andReturn(new AggregationResult(
            CarbonImmutable::parse('2023-06-15')->startOfDay(),
            CarbonImmutable::parse('2023-06-20')->endOfDay(),
        ));

    artisan('glimpse:backfill', ['--from' => '2023-06-15', '--to' => '2023-06-20'])
        ->assertSuccessful()
        ->expectsOutputToContain('2023-06-15 to 2023-06-20');
});

it('respects --days shorthand option', function (): void {
    $this->mock
        ->shouldReceive('aggregate')
        ->once()
        ->andReturn(new AggregationResult(
            CarbonImmutable::now()->subDays(4)->startOfDay(),
            CarbonImmutable::now()->endOfDay(),
        ));

    artisan('glimpse:backfill', ['--days' => 5])
        ->assertSuccessful()
        ->expectsOutputToContain('5 day(s)');
});

it('processes in chunks based on --chunk option', function (): void {
    $this->mock
        ->shouldReceive('aggregate')
        ->times(3)
        ->andReturn(
            new AggregationResult(
                CarbonImmutable::parse('2026-01-01')->startOfDay(),
                CarbonImmutable::parse('2026-01-03')->endOfDay(),
            ),
            new AggregationResult(
                CarbonImmutable::parse('2026-01-04')->startOfDay(),
                CarbonImmutable::parse('2026-01-07')->endOfDay(),
            )
        );

    artisan('glimpse:backfill', [
        '--from' => '2026-01-01',
        '--to' => '2026-01-07',
        '--chunk' => 3,
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('in 3-day chunks');
});

it('handles single-day chunk correctly', function (): void {
    $this->mock
        ->shouldReceive('aggregate')
        ->times(3)
        ->andReturn(
            new AggregationResult(
                CarbonImmutable::parse('2026-01-01')->startOfDay(),
                CarbonImmutable::parse('2026-01-01')->endOfDay(),
            ),
            new AggregationResult(
                CarbonImmutable::parse('2026-01-02')->startOfDay(),
                CarbonImmutable::parse('2026-01-02')->endOfDay(),
            ),
            new AggregationResult(
                CarbonImmutable::parse('2026-01-03')->startOfDay(),
                CarbonImmutable::parse('2026-01-03')->endOfDay(),
            )
        );

    artisan('glimpse:backfill', [
        '--from' => '2026-01-01',
        '--to' => '2026-01-03',
        '--chunk' => 1,
    ])
        ->assertSuccessful();
});

it('displays completion message', function (): void {
    $this->mock
        ->shouldReceive('aggregate')
        ->once()
        ->andReturn(new AggregationResult(
            CarbonImmutable::now()->subDay()->startOfDay(),
            CarbonImmutable::now()->endOfDay(),
        ));

    artisan('glimpse:backfill', ['--from' => '2026-01-01', '--to' => '2026-01-01'])
        ->assertSuccessful()
        ->expectsOutputToContain('Backfill complete');
});

it('returns success exit code', function (): void {
    $this->mock
        ->shouldReceive('aggregate')
        ->once()
        ->andReturn(new AggregationResult(
            CarbonImmutable::now()->subDay()->startOfDay(),
            CarbonImmutable::now()->endOfDay(),
        ));

    artisan('glimpse:backfill', ['--from' => '2026-01-01', '--to' => '2026-01-01'])
        ->assertSuccessful();
});

it('shows task output for each chunk', function (): void {
    $this->mock
        ->shouldReceive('aggregate')
        ->twice()
        ->andReturn(
            new AggregationResult(
                CarbonImmutable::parse('2026-01-01')->startOfDay(),
                CarbonImmutable::parse('2026-01-02')->endOfDay(),
            ),
            new AggregationResult(
                CarbonImmutable::parse('2026-01-03')->startOfDay(),
                CarbonImmutable::parse('2026-01-04')->endOfDay(),
            )
        );

    artisan('glimpse:backfill', [
        '--from' => '2026-01-01',
        '--to' => '2026-01-04',
        '--chunk' => 2,
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('Process 2026-01-01')
        ->expectsOutputToContain('Process 2026-01-03');
});

it('ignores --from and --to when --days is provided', function (): void {
    $this->mock
        ->shouldReceive('aggregate')
        ->once()
        ->andReturn(new AggregationResult(
            CarbonImmutable::now()->subDays(6)->startOfDay(),
            CarbonImmutable::now()->endOfDay(),
        ));

    artisan('glimpse:backfill', [
        '--days' => 7,
        '--from' => '2023-01-01',
        '--to' => '2023-01-31',
    ])
        ->assertSuccessful();
});
