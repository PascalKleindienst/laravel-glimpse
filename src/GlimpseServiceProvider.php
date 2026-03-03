<?php

declare(strict_types=1);

namespace LaravelGlimpse;

use Composer\InstalledVersions;
use hisorange\BrowserDetect\Contracts\ParserInterface;
use hisorange\BrowserDetect\Parser;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;
use LaravelGlimpse\Console\Commands\InstallGlimpseCommand;
use Override;

final class GlimpseServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/glimpse.php',
            'glimpse'
        );

        $this->app->bind(ParserInterface::class, Parser::class);
    }

    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerCommands();
        $this->registerRoutes();
        $this->registerMigrations();
    }

    private function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/glimpse.php' => config_path('glimpse.php'),
        ], 'glimpse-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'glimpse-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/glimpse'),
        ], 'glimpse-views');
    }

    private function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            InstallGlimpseCommand::class,
        ]);

        AboutCommand::add('Glimpse Analytics', fn () => [
            'Version' => InstalledVersions::getPrettyVersion('pascalkleindienst/laravel-glimpse'),
            'Enabled' => AboutCommand::format(config('glimpse.enabled'), console: fn ($value) => $value ? '<fg=green;options=bold>ENABLED</>' : 'OFF'),
            'Queue' => AboutCommand::format(config('queue.default', 'sync'), console: fn ($value) => $value !== 'sync' ? $value : '<fg=yellow;options=bold>⚠ sync</>'),
        ]);
    }

    private function registerRoutes(): void
    {
        // TODO
    }

    private function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
