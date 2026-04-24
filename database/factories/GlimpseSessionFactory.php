<?php

declare(strict_types=1);

namespace LaravelGlimpse\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use LaravelGlimpse\Enums\Platform;
use LaravelGlimpse\Enums\ReferrerChannel;
use LaravelGlimpse\Models\GlimpseSession;

/**
 * @extends Factory<GlimpseSession>
 */
final class GlimpseSessionFactory extends Factory
{
    protected $model = GlimpseSession::class;

    public function definition(): array
    {
        $regional = random_int(0, 4);

        return [
            'session_hash' => $this->faker->sha256(),
            'ip_hash' => $this->faker->sha256(),
            'country_code' => ['DE', 'US', 'GB', 'AT', 'NL'][$regional],
            'region' => null,
            'city' => ['Berlin', 'New York', 'London', 'Vienna', 'Amsterdam'][$regional],
            'language' => ['de', 'en', 'en', 'de', 'nl'][$regional],
            'browser' => $this->faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
            'browser_version' => $this->faker->semver(),
            'os' => $this->faker->randomElement(['Windows', 'macOS', 'Linux', 'Android', 'iOS']),
            'os_version' => $this->faker->semver(),
            'platform' => $this->faker->randomElement(Platform::cases()),
            'referrer_url' => $this->faker->randomElement(['https://google.com', 'https://facebook.com', 'https://twitter.com', 'https://instagram.com', 'https://youtube.com']),
            'referrer_domain' => $this->faker->randomElement(['google.com', 'facebook.com', 'twitter.com', 'instagram.com', 'youtube.com']),
            'referrer_channel' => $this->faker->randomElement(ReferrerChannel::cases()),
            'entry_page' => $this->faker->url(),
            'exit_page' => $this->faker->url(),
            'page_view_count' => $this->faker->randomNumber(),
            'duration_seconds' => $this->faker->randomNumber(),
            'is_bounce' => $this->faker->boolean(75),
            'started_at' => Date::now(),
            'last_seen_at' => Date::now(),
        ];
    }
}
