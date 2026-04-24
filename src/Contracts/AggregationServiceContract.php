<?php

declare(strict_types=1);

namespace LaravelGlimpse\Contracts;

use Carbon\CarbonInterface;
use LaravelGlimpse\Data\AggregationResult;

/**
 * Reads raw glimpse_sessions, glimpse_page_views, and glimpse_events rows
 * created within a time window, then upserts pre-computed buckets into
 * glimpse_aggregates so the dashboard never queries raw tables.
 *
 * Metric naming convention:
 *   'visitors'     – unique sessions (one per session_hash)
 *   'page_views'   – total page view rows
 *   'sessions'     – alias of visitors (kept separate for semantic clarity)
 *   'bounces'      – sessions where is_bounce = true
 *   'bounce_rate'  – percentage (0–100), computed per day/hour bucket
 *   'duration_sum' – sum of duration_seconds for avg calculation
 *   'avg_duration' – mean session duration in seconds
 *
 * Dimension naming convention:
 *   null                  – the top-level (no breakdown)
 *   'country:{ISO}'       – e.g. 'country:GB'
 *   'city:{name}'         – e.g. 'city:Berlin'
 *   'browser:{name}'      – e.g. 'browser:Chrome'
 *   'os:{name}'           – e.g. 'os:macOS'
 *   'platform:{name}'     – e.g. 'platform:mobile'
 *   'channel:{name}'      – e.g. 'channel:organic'
 *   'language:{tag}'      – e.g. 'language:en'
 *   'path:{path}'         – e.g. 'path:/pricing'
 *   'referrer:{domain}'   – e.g. 'referrer:google.com'
 *   'event:{name}'        – e.g. 'event:signup'
 */
interface AggregationServiceContract
{
    /**
     * Aggregate all raw data between $from and $to into hourly and daily buckets.
     *
     * This method is idempotent — calling it twice for the same window will
     * upsert (overwrite) existing aggregate rows, never duplicate them.
     */
    public function aggregate(CarbonInterface $from, CarbonInterface $to): AggregationResult;
}
