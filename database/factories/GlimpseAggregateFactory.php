<?php

declare(strict_types=1);

namespace LaravelGlimpse\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
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
            'metric' => $this->faker->word(),
            'dimension' => $this->faker->word(),
            'value' => $this->faker->randomFloat(),
            'count' => $this->faker->randomNumber(),
            'aggregated_at' => Carbon::now(),
        ];
    }
}
