<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Carbon\CarbonPeriod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use LaravelGlimpse\Contracts\AggregationServiceContract;
use LaravelGlimpse\Models\GlimpseEvent;
use LaravelGlimpse\Models\GlimpseSession;
use Workbench\Database\Factories\UserFactory;

final class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        UserFactory::new()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $period = CarbonPeriod::create(Date::now()->subDays(45), Date::now());
        $pages = collect(array_fill(0, 10, ''))->map(fn () => fake()->slug());

        foreach ($period as $date) {
            $sessions = GlimpseSession::factory()->count(random_int(25, 50))->sequence(function () use ($date, $pages) {
                $start = $date->copy()->startOfDay()->addMinutes(random_int(0, 60 * 24));

                return ['started_at' => $start, 'last_seen_at' => $start, 'entry_page' => '/'.$pages->random()];
            })->create([
                'exit_page' => null,
                'region' => null,
            ]);

            GlimpseEvent::factory()
                ->count(random_int(10, 20))
                ->sequence(fn () => ['name' => Arr::shuffle(['signup', 'login', 'logout', 'purchase'])[0]])
                ->create(['created_at' => $date]);

            $sessions
                ->each(static fn (GlimpseSession $session) => $session->pageViews()->create([
                    'url' => $session->entry_page,
                    'path' => $session->entry_page,
                    'time_on_page_seconds' => random_int(30, 300),
                    'created_at' => $date->copy()->startOfDay()->addSeconds(random_int(0, 60 * 60)),
                    'updated_at' => $date->copy()->startOfDay()->addSeconds(random_int(0, 60 * 60)),
                ]))
                ->filter(fn () => random_int(0, 10) % 2 === 0) // some have more page views
                ->each(static fn (GlimpseSession $session) => $session->pageViews()->create([
                    'url' => $session->entry_page,
                    'path' => $session->entry_page,
                    'time_on_page_seconds' => random_int(30, 300),
                    'created_at' => $date->copy()->startOfDay()->addSeconds(random_int(0, 60 * 60)),
                    'updated_at' => $date->copy()->startOfDay()->addSeconds(random_int(0, 60 * 60)),
                ]));

            resolve(AggregationServiceContract::class)->aggregate($date->copy()->subDay(), $date);
        }
    }
}
