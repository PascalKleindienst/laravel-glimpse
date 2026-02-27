<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use LaravelGlimpse\Database\Factories\GlimpseAggregateFactory;
use LaravelGlimpse\Enums\Period;
use LaravelGlimpse\Models\GlimpseAggregate;

beforeEach(function (): void {
    $this->aggregate = GlimpseAggregate::factory()->create();
});

it('has correct table name', function (): void {
    expect($this->aggregate->getTable())->toBe('glimpse_aggregates');
});

it('has no timestamps', function (): void {
    expect($this->aggregate->timestamps)->toBeFalse();
});

it('casts period to Period enum', function (): void {
    $aggregate = GlimpseAggregate::factory()->create(['period' => 'daily']);

    expect($aggregate->period)->toBeInstanceOf(Period::class)
        ->and($aggregate->period)->toBe(Period::Daily);
});

it('casts date to date', function (): void {
    $date = CarbonImmutable::parse('2024-01-15');
    $aggregate = GlimpseAggregate::factory()->create(['date' => $date]);

    expect($aggregate->date)->toBeInstanceOf(Carbon::class);
});

it('casts hour to integer', function (): void {
    $aggregate = GlimpseAggregate::factory()->create(['hour' => '14']);

    expect($aggregate->hour)->toBeInt()
        ->and($aggregate->hour)->toBe(14);
});

it('casts value to float', function (): void {
    $aggregate = GlimpseAggregate::factory()->create(['value' => '12.5']);

    expect($aggregate->value)->toBeFloat()
        ->and($aggregate->value)->toBe(12.5);
});

it('casts count to integer', function (): void {
    $aggregate = GlimpseAggregate::factory()->create(['count' => '100']);

    expect($aggregate->count)->toBeInt()
        ->and($aggregate->count)->toBe(100);
});

it('casts aggregated_at to timestamp', function (): void {
    $now = CarbonImmutable::now();
    $aggregate = GlimpseAggregate::factory()->create(['aggregated_at' => $now]);

    expect($aggregate->aggregated_at)->toBeInt();
});

it('uses GlimpseAggregateFactory', function (): void {
    expect(GlimpseAggregate::factory())->toBeInstanceOf(GlimpseAggregateFactory::class);
});

it('can mass assign fillable attributes', function (): void {
    $aggregate = new GlimpseAggregate([
        'period' => 'weekly',
        'date' => '2024-01-01',
        'hour' => 10,
        'metric' => 'page_views',
        'dimension' => 'country',
        'value' => 50.5,
        'count' => 100,
    ]);

    expect($aggregate->period)->toBe(Period::Weekly)
        ->and($aggregate->metric)->toBe('page_views')
        ->and($aggregate->dimension)->toBe('country')
        ->and($aggregate->value)->toBe(50.5)
        ->and($aggregate->count)->toBe(100);
});
