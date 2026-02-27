<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use LaravelGlimpse\Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        Sleep::fake();
        Model::unguard();
        Http::preventStrayRequests();
    })
    ->afterEach(function (): void {
        Str::createUuidsNormally();
        Sleep::fake(false);
    })
    ->in('Unit', 'Feature');
