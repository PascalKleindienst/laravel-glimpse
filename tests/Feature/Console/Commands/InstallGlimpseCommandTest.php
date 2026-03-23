<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use LaravelGlimpse\Http\Middleware\TrackVisitorMiddleware;

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
    artisan('glimpse:install', ['--skip-migrate' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('TrackVisitor middleware');
});

it('displays middleware ok when TrackVisitor is registered', function (): void {
    File::partialMock();
    File::shouldReceive('get')->atLeast()
        ->with(base_path('bootstrap/app.php'))
        ->andReturn('->withMiddleware(function (Middleware $middleware) {');

    File::shouldReceive('put')
        ->once()
        ->with(
            base_path('bootstrap/app.php'),
            '->withMiddleware(function (Middleware $middleware) {'.PHP_EOL
            .'        $middleware->web(append: ['.PHP_EOL
            .'            \\LaravelGlimpse\\Http\\Middleware\\TrackVisitorMiddleware::class,'.PHP_EOL
            .'        ]);'
        )
        ->andReturnTrue();

    artisan('glimpse:install', ['--skip-migrate' => true])
        ->assertSuccessful();
});

it('shows a message when TrackVisitor is could not be registered because bootstrap/app.php does not exist', function (): void {
    File::partialMock()->shouldReceive('exists')->with(base_path('bootstrap/app.php'))->andReturnFalse();

    artisan('glimpse:install', ['--skip-migrate' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('TrackVisitorMiddleware middleware not detected — add it manually to bootstrap/app.php');
});

it('shows a message when TrackVisitor is could not be registered because middleware section is not found', function (): void {
    File::partialMock();

    File::shouldReceive('exists')->with(base_path('bootstrap/app.php'))->andReturnTrue();
    File::shouldReceive('get')
        ->with(base_path('bootstrap/app.php'))
        ->andReturn('');

    artisan('glimpse:install', ['--skip-migrate' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('TrackVisitorMiddleware middleware not detected — add it manually to bootstrap/app.php');
});

it('does not inject middleware if TrackVisitor is already registered', function (): void {
    File::partialMock();

    File::shouldReceive('exists')->with(base_path('bootstrap/app.php'))->andReturnTrue();
    File::shouldReceive('get')
        ->with(base_path('bootstrap/app.php'))
        ->andReturn(TrackVisitorMiddleware::class);
    File::shouldReceive('put')->never();

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

it('fails the database check when the db is not available', function (): void {
    DB::shouldReceive('connection')->once()->andReturnUsing(static fn (): RuntimeException => new RuntimeException());

    artisan('glimpse:install')
        ->assertSuccessful()
        ->expectsOutputToContain('Database connection — run php artisan migrate first');
});
