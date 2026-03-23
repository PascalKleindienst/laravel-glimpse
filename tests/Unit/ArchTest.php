<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use LaravelGlimpse\Contracts\Resolver;
use LaravelGlimpse\GlimpseGate;
use LaravelGlimpse\GlimpseServiceProvider;
use LaravelGlimpse\Resolvers\GeoResolver;

arch()->preset()->php();
arch()->preset()->laravel();
arch()->preset()->security();

arch('avoid mutation')
    ->expect('LaravelGlimpse')
    ->classes()
    ->toBeReadonly()
    ->ignoring([
        'LaravelGlimpse\Exceptions',
        'LaravelGlimpse\Facades',
        'LaravelGlimpse\Jobs',
        'LaravelGlimpse\Models',
        'LaravelGlimpse\Console\Commands',
        'LaravelGlimpse\Livewire',
        GeoResolver::class,
        GlimpseServiceProvider::class,
        GlimpseGate::class,
    ]);

arch('avoid inheritance')
    ->expect('LaravelGlimpse')
    ->classes()
    ->toExtendNothing()
    ->ignoring([
        'LaravelGlimpse\Exceptions',
        'LaravelGlimpse\Models',
        'LaravelGlimpse\Console\Commands',
        'LaravelGlimpse\Jobs',
        'LaravelGlimpse\Facades',
        'LaravelGlimpse\Livewire',
        GlimpseServiceProvider::class,
    ]);

arch('avoid open for extension')
    ->expect('LaravelGlimpse')
    ->classes()
    ->toBeFinal();

arch('avoid abstraction')
    ->expect('LaravelGlimpse')
    ->not->toBeAbstract()
    ->ignoring([
        'LaravelGlimpse\Contracts',
    ]);

arch('factories')
    ->expect('Database\Factories')
    ->toExtend(Factory::class)
    ->toHaveMethod('definition')
    ->toOnlyBeUsedIn([
        'LaravelGlimpse\Models',
    ]);

arch('models')
    ->expect('LaravelGlimpse\\Models')
    ->classes()
    ->toExtend(Model::class);

arch('resolvers')
    ->expect('LaravelGlimpse\\Resolvers')
    ->classes()
    ->toImplement(Resolver::class);

arch('values')
    ->expect('LaravelGlimpse\\Values')
    ->classes()
    ->toImplement(Stringable::class);
