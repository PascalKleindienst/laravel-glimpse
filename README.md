# ⚡ Glimpse Analytics

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pascalkleindienst/laravel-glimpse.svg?style=flat-square)](https://packagist.org/packages/pascalkleindienst/laravel-glimpse)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/pascalkleindienst/laravel-glimpse/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/PascalKleindienst/laravel-glimpse/actions/workflows/tests.yml?query=branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/pascalkleindienst/laravel-glimpse/lint.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/PascalKleindienst/laravel-glimpse/actions/workflows/lint.yml?query=branch%3Amain)
[![codecov](https://codecov.io/gh/PascalKleindienst/laravel-glimpse/graph/badge.svg?token=MelHC4atzv)](https://codecov.io/gh/PascalKleindienst/laravel-glimpse)
[![Total Downloads](https://img.shields.io/packagist/dt/pascalkleindienst/laravel-glimpse.svg?style=flat-square)](https://packagist.org/packages/pascalkleindienst/laravel-glimpse)

**Privacy-first, server-side analytics for Laravel 12+.**  
No cookies. No JavaScript tracking pixel. No GDPR consent banners.  
Just clean, fast, anonymous analytics built entirely on the server.

---

## Features

- **Zero client-side footprint** — tracking is 100% server-side via a Laravel middleware
- **Session-based unique visitors** — derived from Laravel's existing session ID (SHA-256 hashed, never stored raw)
- **No PII stored** — IPs are one-way hashed with your app key; no names, emails, or identifiers
- **Cookie-free** — no new cookies introduced; uses the session cookie your app already sets
- **Livewire dashboard** — a Pulse-style real-time dashboard at `/glimpse`
- **Custom events** — `Glimpse::event('checkout', ['plan' => 'pro'])`
- **GeoIP** — country, region, city, language (MaxMind or SxGeo, optional)
- **Device detection** — browser, OS, platform (desktop/mobile/tablet)
- **Referrer classification** — organic, social, paid, email, referral, direct
- **Pre-aggregated** — dashboard queries never touch raw tables; fast at any scale
- **Queue-driven** — zero latency added to your requests

---

## Requirements

| Dependency | Version      |
|------------|--------------|
| PHP        | ^8.4\|^8.5   |
| Laravel    | ^12.0\|^13.0 |
| Livewire   | ^3.6.4\|^4.0 |

---

## Installation

```bash
composer require pascalkleindienst/laravel-glimpse
php artisan glimpse:install
```

`glimpse:install` will:

1. Publish `config/glimpse.php`
2. Publish and run the database migrations
3. Inject `TrackVisitorMiddleware` middleware into `bootstrap/app.php`
4. Run a health check and print next steps

---

## Quick Start

### 1. Register the middleware

The install command does this automatically. If you need to do it manually, add to `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \LaravelGlimpse\Http\Middleware\TrackVisitorMiddleware::class,
    ]);
})
```

### 2. Start the queue worker

Glimpse dispatches a queued job per request so tracking adds zero latency:

```bash
php artisan queue:work
```

### 3. Enable the scheduler

Add to your crontab (or use Laravel's built-in scheduler):

```
* * * * * php /path/to/your/app/artisan schedule:run >> /dev/null 2>&1
```

Glimpse auto-registers two scheduled commands:

- `glimpse:aggregate` — every 5 minutes (aggregates raw data)
- `glimpse:prune` — daily at 03:00 (deletes data beyond the retention window)

### 4. Open the dashboard

```
https://your-app.com/glimpse
```

---

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=glimpse-config
```

```php
// config/glimpse.php

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
```

---

## Custom Events

Track any meaningful action in your app:

```php
use LaravelGlimpse\Facades\Glimpse;

// Simple event
Glimpse::event('signup');

// Event with payload
Glimpse::event('checkout', [
    'plan'  => 'pro',
    'value' => 99,
]);

// Virtual page view (for SPAs / AJAX navigation)
Glimpse::page('/checkout/step-2', 'Checkout — Step 2');

// Swallow invalid names silently instead of throwing
Glimpse::dispatchSilently($untrustedName, $properties);
```

### Using the global helper

```php
glimpse('signup');
glimpse('checkout', ['plan' => 'pro']);
glimpse(); // returns the Glimpse instance
```

### Associating events from queued jobs

```php
// In your controller — capture the hash before dispatching
$hash = Glimpse::currentSessionHash();
dispatch(new ProcessOrderJob($orderId, $hash));

// Inside the job
Glimpse::event('order_processed', ['id' => $orderId], $hash);
```

### Listening to dispatched events

```php
use LaravelGlimpse\Events\GlimpseEventDispatchedEvent;

Event::listen(GlimpseEventDispatchedEvent::class, function ($e) {
    if ($e->name === 'enterprise_signup') {
        Notification::route('slack', config('services.slack.sales'))
            ->notify(new NewEnterpriseSignupNotification($e->properties));
    }
});
```

---

## Dashboard Access Control

By default, any authenticated user can access the dashboard. Customise this in a service provider:

```php
use LaravelGlimpse\GlimpseGate;
use Illuminate\Http\Request;

// In AppServiceProvider::boot()
GlimpseGate::using(function (Request $request): bool {
    return $request->user()?->hasRole('admin') ?? false;
});
```

---

## Querying Analytics in Code

Access the full query API anywhere in your application:

```php
use LaravelGlimpse\Facades\Glimpse;
use LaravelGlimpse\Values\DateRange;
use Illuminate\Support\Carbon;

// Quick summary
$stats = Glimpse::query()->summary(DateRange::last7Days());
$stats = Glimpse::query()->summary(DateRange::today());
$stats = Glimpse::query()->summary(DateRange::custom(Carbon::parse('2026-01-01'), Carbon::now());

// $stats = [
//   'visitors'     => 1240,
//   'page_views'   => 4823,
//   'sessions'     => 1240,
//   'bounce_rate'  => 42.3,
//   'avg_duration' => 127.0,
// ]

// Full query API
$query = Glimpse::query();

$pages     = $query->topPages(DateRange::last30Days());
$channels  = $query->topChannels(DateRange::last30Days());
$countries = $query->topCountries(DateRange::last30Days());
$browsers  = $query->topBrowsers(DateRange::last7Days());
$events    = $query->topEvents(DateRange::last7Days());
$series    = $query->timeSeries(DateRange::last7Days());
```

---

---

## GeoIP Setup

Geo resolution is **disabled** by default (driver = `null`). To enable:

### MaxMind GeoLite2 (recommended)

1. Sign up for a free MaxMind account and download `GeoLite2-City.mmdb`
2. Place the file at `storage/app/glimpse/GeoLite2-City.mmdb`
3. Set in `.env`:

```env
GLIMPSE_GEO_DRIVER=maxmind
GLIMPSE_MAXMIND_DB=/absolute/path/to/GeoLite2-City.mmdb
```

### SypexGeo (no sign-up required)

1. Download `SxGeoCity.dat` and `SxGeo.php` from [sypexgeo.net](https://sypexgeo.net)
2. Place both files at `storage/app/glimpse/`
3. Set in `.env`:

```env
GLIMPSE_GEO_DRIVER=sxgeo
```

---

## Artisan Commands

| Command             | Description                                           |
|---------------------|-------------------------------------------------------|
| `glimpse:install`   | Install Glimpse (publish, migrate, wire middleware)   |
| `glimpse:aggregate` | Roll raw data into aggregate buckets (auto-scheduled) |
| `glimpse:prune`     | Delete data beyond retention window (auto-scheduled)  |
| `glimpse:backfill`  | Re-aggregate a historical date range                  |

### Backfill historical data

```bash
# Backfill the last 90 days
php artisan glimpse:backfill --days=90

# Backfill a specific range
php artisan glimpse:backfill --from=2024-01-01 --to=2024-03-31

# Process in smaller chunks to limit memory usage
php artisan glimpse:backfill --days=365 --chunk=7
```

---

## Customising the Dashboard

Publish the views to override them:

```bash
php artisan vendor:publish --tag=glimpse-views
```

Views are published to `resources/views/vendor/glimpse/`.

---

## How It Works

### Session identity

On every request the `TrackVisitorMiddleware` computes:

```
session_hash = SHA-256(laravel_session_id)
```

This is stored as the visitor identity. The raw session ID is never persisted. When the session expires (default 2 hours
in Laravel), so does the identity — genuinely ephemeral.

### Request lifecycle

```
HTTP Request
    │
    ├─ TrackVisitorMiddleware (sync — reads cache only)
    │      └─ Dispatch ProcessVisitJob to queue
    │
    └─ Response returned immediately ← zero DB writes in the request

Queue worker (async)
    └─ ProcessVisitJob
           ├─ GeoResolver    (IP → country/city)
           ├─ DeviceResolver (UA → browser/OS/platform)
           ├─ LanguageResolver (Accept-Language Header → language)
           ├─ ReferrerResolver (referer → channel)
           └─ Write to glimpse_sessions + glimpse_page_views
```

### Aggregation

Every 5 minutes, `glimpse:aggregate` reads raw rows and upserts pre-computed buckets into `glimpse_aggregates`. The
dashboard **only reads from `glimpse_aggregates`** — it never queries raw tables, so it's fast at any volume.

---

## Privacy

Glimpse is designed to be GDPR/ePrivacy compliant by default:

- **No cookies introduced** — uses the session cookie already set by Laravel
- **No PII stored** — IPs are one-way hashed (SHA-256 + app key) before storage
- **No third-party requests** — all data stays on your server
- **No cross-site tracking** — identifiers are session-scoped
- **No fingerprinting** — User-Agent is parsed but never stored

> **Legal note**: Glimpse minimizes data collection but you are responsible for your own compliance. Consult a legal
> professional for advice specific to your jurisdiction.

---

## Testing

```bash
composer test
```

---

## License

MIT — see [LICENSE](LICENSE.md).
