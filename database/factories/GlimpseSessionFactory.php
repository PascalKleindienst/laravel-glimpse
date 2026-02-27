<?php

declare(strict_types=1);

namespace LaravelGlimpse\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
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
        return [
            'session_hash' => $this->faker->sha256(),
            'ip_hash' => $this->faker->sha256(),
            'country_code' => $this->faker->countryCode(),
            'region' => $this->faker->word(),
            'city' => $this->faker->city(),
            'language' => $this->faker->languageCode(),
            'browser' => $this->faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
            'browser_version' => $this->faker->semver(),
            'os' => $this->faker->randomElement(['Windows', 'macOS', 'Linux', 'Android', 'iOS']),
            'os_version' => $this->faker->semver(),
            'platform' => $this->faker->randomElement(Platform::cases()),
            'referrer_url' => $this->faker->url(),
            'referrer_domain' => $this->faker->domainName(),
            'referrer_channel' => $this->faker->randomElement(ReferrerChannel::cases()),
            'entry_page' => $this->faker->url(),
            'exit_page' => $this->faker->url(),
            'page_view_count' => $this->faker->randomNumber(),
            'duration_seconds' => $this->faker->randomNumber(),
            'is_bounce' => false,
            'started_at' => Carbon::now(),
            'last_seen_at' => Carbon::now(),
        ];
    }
}
