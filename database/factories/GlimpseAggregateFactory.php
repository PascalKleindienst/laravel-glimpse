<?php

declare(strict_types=1);

namespace LaravelGlimpse\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use LaravelGlimpse\Enums\Period;
use LaravelGlimpse\Models\GlimpseAggregate;

/**
 * @extends Factory<GlimpseAggregate>
 */
final class GlimpseAggregateFactory extends Factory
{
    protected $model = GlimpseAggregate::class;

    public function definition(): array
    {
        return [
            'period' => $this->faker->randomElement(Period::cases()),
            'date' => $this->faker->date(),
            'hour' => $this->faker->randomElement(range(0, 23)),
            'metric' => $this->faker->randomElement(['visitors', 'page_views', 'bounce_rate', 'avg_duration', 'avg_time_on_page']),
            'dimension' => $this->faker->word(),
            'value' => $this->faker->randomFloat(max: 100_000),
            'count' => (int) $this->faker->randomFloat(0, max: 5_000),
            'aggregated_at' => Date::now(),
        ];
    }
}
