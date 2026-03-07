<?php

declare(strict_types=1);

Illuminate\Support\Facades\Route::middleware(['web', Workbench\App\Http\Middleware\SpoofIpMiddleware::class, LaravelGlimpse\Http\Middleware\TrackVisitorMiddleware::class])
    ->get('/test', function () {
        return 'Hello from workbench!';
    });
