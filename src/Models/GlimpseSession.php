<?php

declare(strict_types=1);

namespace LaravelGlimpse\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LaravelGlimpse\Database\Factories\GlimpseSessionFactory;
use LaravelGlimpse\Enums\Platform;
use LaravelGlimpse\Enums\ReferrerChannel;
use Override;

/**
 * @property string $session_hash
 * @property string|null $ip_hash
 * @property string|null $country_code
 * @property string|null $region
 * @property string|null $city
 * @property string|null $language
 * @property string|null $browser
 * @property string|null $browser_version
 * @property string|null $os
 * @property string|null $os_version
 * @property Platform|null $platform
 * @property string|null $referrer_url
 * @property string|null $referrer_domain
 * @property ReferrerChannel|null $referrer_channel
 * @property string|null $entry_page
 * @property string|null $exit_page
 * @property int<1, max> $page_view_count
 * @property int $duration_seconds
 * @property bool $is_bounce
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable $last_seen_at
 * @property-read Collection<int, GlimpseEvent> $events
 * @property-read int|null $events_count
 * @property-read Collection<int, GlimpsePageView> $pageViews
 * @property-read int|null $page_views_count
 *
 * @method static GlimpseSessionFactory factory($count = null, $state = [])
 * @method static Builder<static>|GlimpseSession newModelQuery()
 * @method static Builder<static>|GlimpseSession newQuery()
 * @method static Builder<static>|GlimpseSession query()
 *
 * @mixin Model
 */
final class GlimpseSession extends Model
{
    /** @use HasFactory<GlimpseSessionFactory> */
    use HasFactory;

    public $timestamps = false;

    public $guarded = [];

    /**
     * @return HasMany<GlimpsePageView, $this>
     */
    public function pageViews(): HasMany
    {
        return $this->hasMany(GlimpsePageView::class, 'session_hash', 'session_hash');
    }

    /**
     * @return HasMany<GlimpseEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(GlimpseEvent::class, 'session_hash', 'session_hash');
    }

    /**
     * Mark the session as having more than one page view (no longer a bounce)
     * and update the exit page and timing stats.
     */
    public function recordSubsequentHit(string $path, CarbonImmutable $now): void
    {
        $this->exit_page = $path;
        $this->page_view_count++;
        $this->duration_seconds = $now->getTimestamp() - $this->started_at->getTimestamp();
        $this->is_bounce = false;
        $this->last_seen_at = $now;
        $this->save();
    }

    protected static function newFactory(): GlimpseSessionFactory
    {
        return GlimpseSessionFactory::new();
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'platform' => Platform::class,
            'referrer_channel' => ReferrerChannel::class,
            'is_bounce' => 'boolean',
            'page_view_count' => 'integer',
            'duration_seconds' => 'integer',
            'started_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }
}
