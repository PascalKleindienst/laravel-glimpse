<?php

declare(strict_types=1);

namespace LaravelGlimpse\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use LaravelGlimpse\Models\GlimpseEvent;

/**
 * @extends Factory<GlimpseEvent>
 */
final class GlimpseEventFactory extends Factory
{
    protected $model = GlimpseEvent::class;

    public function definition(): array
    {
        return [
            'session_hash' => $this->faker->sha256(),
            'name' => $this->faker->name(),
            'properties' => ['foo' => 'bar'],
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }
}
