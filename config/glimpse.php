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

    /*
    |--------------------------------------------------------------------------
    | GeoIP Driver
    |--------------------------------------------------------------------------
    | 'maxmind'  – MaxMind GeoIP2 City database (requires a licence key and
    |              the mmdbphp extension or geoip2/geoip2 package).
    | 'sxgeo'    – SypexGeo free database bundled with the package (no key).
    | 'null'     – Disables geo resolution entirely.
    */
    'geo' => [
        'driver' => env('GLIMPSE_GEO_DRIVER', 'null'),
        'maxmind_db' => env('GLIMPSE_MAXMIND_DB', storage_path('app/glimpse/GeoLite2-City.mmdb')),
        'sxgeo_db' => env('GLIMPSE_SXGEO_DB', storage_path('app/glimpse/SxGeoCity.dat')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Resolver
    |--------------------------------------------------------------------------
    | The following array lists the "resolvers" that will be registered with
    | Glimpse, along with their configuration. Resolvers gather information
    | about the request and return an array of attributes to be stored.
    |
    */
    'resolver' => [
        LaravelGlimpse\Resolvers\DeviceResolver::class => [],
        LaravelGlimpse\Resolvers\LanguageResolver::class => [],
        LaravelGlimpse\Resolvers\GeoResolver::class => [],
        LaravelGlimpse\Resolvers\ReferrerResolver::class => [
            'search_engines' => [
                'google', 'bing', 'yahoo', 'duckduckgo', 'baidu', 'yandex',
                'ecosia', 'startpage', 'qwant', 'brave', 'kagi', 'ask',
            ],
            'social_media' => [
                'facebook', 'twitter', 'x.com', 't.co', 'instagram', 'linkedin',
                'pinterest', 'tiktok', 'reddit', 'youtube', 'snapchat', 'whatsapp',
                'telegram', 'mastodon', 'threads', 'bluesky', 'bsky', 'discord', 'slack',
                'twitch',
            ],
            'email_clients' => [
                'mail.google', 'outlook', 'mail.yahoo', 'protonmail', 'webmail',
                'roundcube', 'mail.ru',
            ],
            // Query params that reliably indicate paid / campaign traffic.
            'paid_params' => ['gclid', 'fbclid', 'msclkid', 'ttclid', 'li_fat_id'],
        ],
    ],
];
