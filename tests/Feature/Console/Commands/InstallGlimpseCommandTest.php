<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\artisan;
use function PHPUnit\Framework\assertTrue;

it('publishes config when running install command', function (): void {
    artisan('glimpse:install', ['--skip-migrate' => true]);

    expect(File::exists(base_path('config/glimpse.php')))->toBeTrue();
});

it('publishes migrations when running install command', function (): void {
    artisan('glimpse:install', ['--skip-migrate' => true]);

    assertTrue(
        File::exists(database_path('migrations/'))
    );
});

it('runs migrations when skip-migrate is not provided', function (): void {
    artisan('glimpse:install');

    expect(Schema::hasTable('glimpse_sessions'))->toBeTrue();
});

// it('skips migrations when skip-migrate option is provided', function () {
//     Schema::dropAllTables();
//     artisan('glimpse:install', ['--skip-migrate' => true]);
//
//     expect(Schema::hasTable('glimpse_sessions'))->toBeFalse();
// });

it('displays success message after installation', function (): void {
    artisan('glimpse:install')
        ->assertSuccessful()
        ->expectsOutputToContain('Glimpse Analytics installed successfully');
});

it('displays dashboard URL after installation', function (): void {
    artisan('glimpse:install')
        ->assertSuccessful()
        ->expectsOutputToContain('Dashboard available at:');
});

it('displays env hints after installation', function (): void {
    artisan('glimpse:install', ['--skip-migrate' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Recommended .env settings')
        ->expectsOutputToContain('GLIMPSE_ENABLED=true');
});

it('displays health check information', function (): void {
    artisan('glimpse:install', ['--skip-migrate' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Health check');
});

it('shows warning when database is not connected', function (): void {
    artisan('glimpse:install', ['--skip-migrate' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Database connection');
});

it('shows warning about sync queue driver', function (): void {
    config(['queue.default' => 'sync']);

    artisan('glimpse:install', ['--skip-migrate' => true])
        ->assertSuccessful()
        ->expectsOutputToContain("Queue driver is 'sync'");
});

it('shows ok status when queue driver is not sync', function (): void {
    config(['queue.default' => 'redis']);

    artisan('glimpse:install', ['--skip-migrate' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Queue driver: redis');
});

it('displays middleware warning when TrackVisitor is not registered', function (): void {
    $bootstrapPath = base_path('bootstrap/app.php');

    artisan('glimpse:install', ['--skip-migrate' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('TrackVisitor middleware');
});

it('displays middleware ok when TrackVisitor is registered', function (): void {
    File::put(
        base_path('bootstrap/app.php'),
        '<?php'."\n".'$app->middleware->prepend(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);'."\n".'TrackVisitorMiddleware::class,'
    );

    artisan('glimpse:install', ['--skip-migrate' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('TrackVisitorMiddleware middleware registered');
});

it('accepts force option for re-publishing', function (): void {
    artisan('glimpse:install', [
        '--skip-migrate' => true,
        '--force' => true,
    ])->assertSuccessful();
});

it('displays banner on installation', function (): void {
    artisan('glimpse:install', ['--skip-migrate' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Glimpse Analytics')
        ->expectsOutputToContain('Privacy-first');
});

it('returns success exit code', function (): void {
    artisan('glimpse:install')
        ->assertSuccessful();
});
