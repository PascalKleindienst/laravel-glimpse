<?php

declare(strict_types=1);

namespace LaravelGlimpse\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LaravelGlimpse\Database\Factories\GlimpsePageViewFactory;
use Override;

/**
 * @property string $session_hash
 * @property string|null $url
 * @property string|null $path
 * @property string|null $query_string
 * @property string|null $referrer
 * @property int|null $time_on_page_seconds
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read GlimpseSession|null $session
 *
 * @method static GlimpsePageViewFactory factory($count = null, $state = [])
 * @method static Builder<static>|GlimpsePageView newModelQuery()
 * @method static Builder<static>|GlimpsePageView newQuery()
 * @method static Builder<static>|GlimpsePageView query()
 *
 * @mixin Model
 */
final class GlimpsePageView extends Model
{
    /** @use HasFactory<GlimpsePageViewFactory> */
    use HasFactory;

    public $guarded = ['id'];

    /**
     * @return BelongsTo<GlimpseSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(GlimpseSession::class);
    }

    /**
     * Backfill time_on_page_seconds when the NEXT page view arrives.
     * Called on the PREVIOUS page view row.
     */
    public function closeWithTimestamp(CarbonInterface $next): void
    {
        $this->time_on_page_seconds = $next->getTimestamp() - ($this->created_at?->getTimestamp() ?? 0);
        $this->updated_at = $next;
        $this->save();
    }

    protected static function newFactory(): GlimpsePageViewFactory
    {
        return GlimpsePageViewFactory::new();
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'time_on_page_seconds' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
