<?php

declare(strict_types=1);

namespace LaravelGlimpse\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use LaravelGlimpse\Models\GlimpsePageView;

/**
 * @extends Factory<GlimpsePageView>
 */
final class GlimpsePageViewFactory extends Factory
{
    protected $model = GlimpsePageView::class;

    public function definition(): array
    {
        return [
            'session_hash' => $this->faker->sha256(),
            'url' => $this->faker->url(),
            'path' => $this->faker->filePath(),
            'query_string' => $this->faker->word(),
            'referrer' => $this->faker->word(),
            'time_on_page_seconds' => $this->faker->randomNumber(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }
}
