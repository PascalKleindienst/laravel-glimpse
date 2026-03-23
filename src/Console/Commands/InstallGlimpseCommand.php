<?php

declare(strict_types=1);

namespace LaravelGlimpse\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class InstallGlimpseCommand extends Command
{
    protected $signature = 'glimpse:install
        {--skip-migrate   : Skip running database migrations.}
        {--skip-middleware : Skip automatic middleware registration.}
        {--force          : Force re-publish config/views even if they already exist.}';

    protected $description = 'Install Glimpse Analytics: publish config, migrate, register middleware.';

    public function handle(): int
    {
        $this->renderBanner();

        // Publish Config
        $this->components->task('Publishing configuration', function (): true {
            $this->callSilently('vendor:publish', [
                '--tag' => 'glimpse-config',
                '--force' => $this->option('force'),
            ]);

            return true;
        });

        // Publish migrations
        $this->components->task('Publishing migrations', function (): true {
            $this->callSilently('vendor:publish', [
                '--tag' => 'glimpse-migrations',
                '--force' => $this->option('force'),
            ]);

            return true;
        });

        // run migrations
        if (! $this->option('skip-migrate')) {
            $this->components->task('Running migrations', function (): true {
                $this->callSilently('migrate', ['--force' => true]);

                return true;
            });
        }

        // register middleware
        if (! $this->option('skip-middleware')) {
            $this->components->task('Registering TrackVisitor middleware', fn (): bool => $this->injectMiddleware());
        }

        // health check / info
        $this->printEnvHints();
        $this->runHealthCheck();

        // Done
        /** @var string $url */
        $url = url(config('glimpse.path', 'glimpse'));
        $this->newLine();
        $this->components->info('Glimpse Analytics installed successfully!');
        $this->newLine();
        $this->line('  Dashboard available at: <fg=cyan>'.$url.'</>');
        $this->newLine();

        return self::SUCCESS;
    }

    private function renderBanner(): void
    {
        $this->newLine();
        $this->line('  <fg=green;options=bold>  ╔══════════════════════════════╗</>');
        $this->line('  <fg=green;options=bold>  ║   ⚡  Glimpse Analytics       ║</>');
        $this->line('  <fg=green;options=bold>  ║   Privacy-first · No cookies ║</>');
        $this->line('  <fg=green;options=bold>  ╚══════════════════════════════╝</>');
        $this->newLine();
    }

    private function printEnvHints(): void
    {
        $this->newLine();
        $this->line('  <fg=yellow>Recommended .env settings:</> ');
        $this->newLine();
        $this->line('    <fg=gray>GLIMPSE_ENABLED=true</>');
        $this->line('    <fg=gray>GLIMPSE_QUEUE=default           # queue for async processing</>');
        $this->line('    <fg=gray>GLIMPSE_SESSION_TIMEOUT=30      # minutes before new session</>');
        $this->line('    <fg=gray>GLIMPSE_GEO_DRIVER=null         # null | maxmind | sxgeo</>');
        $this->line('    <fg=gray>GLIMPSE_RETENTION_RAW=90        # days to keep raw rows</>');
        $this->newLine();
        $this->line('  <fg=yellow>Ensure your scheduler is running:</> ');
        $this->newLine();
        $this->line('    <fg=gray>* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1</>');
        $this->newLine();
        $this->line('  <fg=yellow>Optionally customise the dashboard gate:</> ');
        $this->newLine();
        $this->line('    <fg=gray>// In a ServiceProvider boot() method:</>');
        $this->line('    <fg=gray>GlimpseGate::using(fn ($request) => $request->user()?->isAdmin());</>');
        $this->newLine();
    }

    private function runHealthCheck(): void
    {
        $this->line('  <fg=yellow>Health check:</>');
        $this->newLine();

        // DB connection
        $dbOk = $this->checkDatabase();
        $this->line($dbOk
            ? '    <fg=green>✓</> Database connection'
            : '    <fg=red>✗</> Database connection — run <fg=cyan>php artisan migrate</> first');

        // Queue driver
        $queueDriver = config('queue.default', 'sync');
        $queueOk = $queueDriver !== 'sync';
        $this->line($queueOk
            ? "    <fg=green>✓</> Queue driver: <fg=cyan>{$queueDriver}</>"
            : "    <fg=yellow>⚠</> Queue driver is 'sync' — tracking will run in-process; consider switching to 'database' or 'redis' in production");

        // Scheduler (we can't truly check, just remind)
        $this->line('    <fg=yellow>ℹ</> Verify your cron scheduler is running to enable aggregation');

        // Middleware
        $bootstrapPath = base_path('bootstrap/app.php');
        $mwRegistered = File::exists($bootstrapPath) && str_contains(File::get($bootstrapPath), 'TrackVisitorMiddleware');
        $this->line($mwRegistered
            ? '    <fg=green>✓</> TrackVisitorMiddleware middleware registered'
            : '    <fg=yellow>⚠</> TrackVisitorMiddleware middleware not detected — add it manually to bootstrap/app.php');
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            Schema::hasTable('glimpse_sessions');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Attempt to inject the TrackVisitor middleware into bootstrap/app.php
     * by finding the ->withMiddleware() block.
     */
    private function injectMiddleware(): bool
    {
        $bootstrapPath = base_path('bootstrap/app.php');

        if (! File::exists($bootstrapPath)) {
            return false;
        }

        $contents = File::get($bootstrapPath);

        // already registered -> nothing to do
        if (str_contains($contents, 'TrackVisitorMiddleware')) {
            return true;
        }

        // Try to find `->withMiddleware()` and append inside `web()` call.
        $needle = '->withMiddleware(function (Middleware $middleware) {';
        $replacement = $needle.PHP_EOL
            .'        $middleware->web(append: ['.PHP_EOL
            .'            \\LaravelGlimpse\\Http\\Middleware\\TrackVisitorMiddleware::class,'.PHP_EOL
            .'        ]);';

        if (str_contains($contents, $needle)) {
            return File::put($bootstrapPath, str_replace($needle, $replacement, $contents)) !== false;
        }

        // Pattern not found -> print manual instructions instead
        return false;
    }
}
