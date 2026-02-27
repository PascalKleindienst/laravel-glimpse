<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Enable / Disable Glimpse
    |--------------------------------------------------------------------------
    | Quickly toggle tracking without removing the middleware. Useful for
    | disabling in local/staging environments via your .env file.
    */
    'enabled' => env('GLIMPSE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Queue Connection & Queue Name
    |--------------------------------------------------------------------------
    | Glimpse dispatches a queued job for every hit so tracking adds zero
    | latency to your requests. Set to 'sync' during development/testing.
    */
    'queue_connection' => env('GLIMPSE_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),
    'queue' => env('GLIMPSE_QUEUE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Dashboard Path & Middleware
    |--------------------------------------------------------------------------
    | The URI where the Glimpse dashboard will be available. Protect it with
    | whatever middleware makes sense for your app (typically 'auth').
    */
    'path' => env('GLIMPSE_PATH', 'glimpse'),
    'middleware' => ['web', 'auth'],

    /*
    |--------------------------------------------------------------------------
    | Session Timeout (minutes)
    |--------------------------------------------------------------------------
    | How long without activity before a visitor's session is considered
    | ended and a new one will be created on their next request.
    */
    'session_timeout' => (int) env('GLIMPSE_SESSION_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    | How many days to keep raw tracking rows (sessions, page views, events).
    | Aggregate rows are kept forever by default (set null for forever).
    */
    'retention' => [
        'raw' => (int) env('GLIMPSE_RETENTION_RAW', 90),
        'aggregates' => null,
    ],
];
