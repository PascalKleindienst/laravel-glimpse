<?php

declare(strict_types=1);

namespace LaravelGlimpse\Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Concerns\WithWorkbench;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;
    use WithWorkbench;

    protected $enablesPackageDiscoveries = true;

    protected function setUp(): void
    {
        self::usesTestingFeature(new WithMigration('laravel', 'queue'));

        parent::setUp();

        AliasLoader::getInstance()->setAliases([]);
    }

    protected function defineEnvironment($app): void
    {
        $app->make(Repository::class)->set('view.cache', false);

        tap($app->make(Repository::class), function (Repository $config): void {
            // Setup queue database connections.
            $config->set([
                'queue.batching.database' => 'testing',
                'queue.failed.database' => 'testing',
            ]);

            $config->set('queue.failed.driver', 'null');
        });
    }
}
