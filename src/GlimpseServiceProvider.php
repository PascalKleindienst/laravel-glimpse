<?php

declare(strict_types=1);

namespace LaravelGlimpse;

use Composer\InstalledVersions;
use hisorange\BrowserDetect\Contracts\ParserInterface;
use hisorange\BrowserDetect\Parser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Routing\Router;
use Illuminate\Support\Number;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use LaravelGlimpse\Console\Commands\AggregateMetricsCommand;
use LaravelGlimpse\Console\Commands\BackfillDataCommand;
use LaravelGlimpse\Console\Commands\InstallGlimpseCommand;
use LaravelGlimpse\Console\Commands\PruneDataCommand;
use LaravelGlimpse\Contracts\AggregationServiceContract;
use LaravelGlimpse\Contracts\PruneServiceContract;
use LaravelGlimpse\Contracts\QueryServiceContract;
use LaravelGlimpse\Http\Middleware\AuthorizeGlimpseAccessMiddleware;
use LaravelGlimpse\Livewire\Dashboard;
use LaravelGlimpse\Livewire\Metrics\DevicesBreakdown;
use LaravelGlimpse\Livewire\Metrics\EventsTable;
use LaravelGlimpse\Livewire\Metrics\GeoBreakdown;
use LaravelGlimpse\Livewire\Metrics\PagesTable;
use LaravelGlimpse\Livewire\Metrics\ReferrersTable;
use LaravelGlimpse\Livewire\Metrics\VisitorsChart;
use LaravelGlimpse\Services\AggregationService;
use LaravelGlimpse\Services\QueryService;
use Livewire\Livewire;
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
        $this->app->bind(PruneServiceContract::class, PruneServiceContract::class);
        $this->app->bind(AggregationServiceContract::class, AggregationService::class);
        $this->app->bind(QueryServiceContract::class, QueryService::class);
    }

    public function boot(): void
    {
        Number::useLocale(app()->getLocale());

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'glimpse');

        $this->registerPublishing();
        $this->registerCommands();
        $this->registerRoutes();
        $this->registerMigrations();
        $this->registerComponents();
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

        $this->publishes([
            __DIR__.'/../public/vendor/glimpse' => public_path('vendor/glimpse'),
        ], 'glimpse-assets');

        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/glimpse'),
        ], 'glimpse-lang');
    }

    private function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            InstallGlimpseCommand::class,
            AggregateMetricsCommand::class,
            PruneDataCommand::class,
            BackfillDataCommand::class,
        ]);

        AboutCommand::add('Glimpse Analytics', fn (): array => [
            'Version' => InstalledVersions::getPrettyVersion('pascalkleindienst/laravel-glimpse'),
            'Enabled' => AboutCommand::format(config('glimpse.enabled'), console: static fn (bool $value): string => $value ? '<fg=green;options=bold>ENABLED</>' : 'OFF'),
            'Queue' => AboutCommand::format(config('queue.default', 'sync'), console: static fn (string $value): string => $value !== 'sync' ? $value : '<fg=yellow;options=bold>⚠ sync</>'),
        ]);
    }

    private function registerRoutes(): void
    {
        $this->callAfterResolving('router', function (Router $router, Application $app): void {
            $middleware = config('glimpse.middleware', ['web', 'auth']);

            $router->group([
                'prefix' => config('glimpse.path', 'glimpse'),
                'middleware' => [...$middleware, AuthorizeGlimpseAccessMiddleware::class],
                'name' => 'glimpse.',
            ], function (Router $router): void {
                $router->get('/', Dashboard::class)->name('dashboard');
            });
        });
    }

    private function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    private function registerComponents(): void
    {
        // Register view namespace so blade can resolve glimpse:: views.
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'glimpse');

        $this->callAfterResolving('blade.compiler', function (BladeCompiler $blade): void {
            $blade->anonymousComponentPath(__DIR__.'/../resources/views/components', 'glimpse');
        });

        // $this->callAfterResolving('livewire', function (LivewireManager $livewire, Application $app) {
        //     // Register all Livewire components under the glimpse. prefix.
        //     $livewire->component('dashboard', Dashboard::class);
        // });

        Livewire::component('glimpse.dashboard', Dashboard::class);
        Livewire::component('glimpse.devices-breakdown', DevicesBreakdown::class);
        Livewire::component('glimpse.events-table', EventsTable::class);
        Livewire::component('glimpse.geo-breakdown', GeoBreakdown::class);
        Livewire::component('glimpse.pages-table', PagesTable::class);
        Livewire::component('glimpse.referrers-table', ReferrersTable::class);
        Livewire::component('glimpse.visitors-chart', VisitorsChart::class);
    }
}
