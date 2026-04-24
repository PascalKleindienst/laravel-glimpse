<?php

declare(strict_types=1);

namespace LaravelGlimpse\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LaravelGlimpse\Database\Factories\GlimpseEventFactory;
use Override;

/**
 * @property string $name
 * @property string|null $session_hash
 * @property array<string, mixed> $properties
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read GlimpseSession|null $session
 *
 * @method static GlimpseEventFactory factory($count = null, $state = [])
 * @method static Builder<static>|GlimpseEvent newModelQuery()
 * @method static Builder<static>|GlimpseEvent newQuery()
 * @method static Builder<static>|GlimpseEvent query()
 *
 * @mixin Model
 */
final class GlimpseEvent extends Model
{
    /** @use HasFactory<GlimpseEventFactory> */
    use HasFactory;

    public $guarded = ['id'];

    /**
     * @return BelongsTo<GlimpseSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(GlimpseSession::class);
    }

    protected static function newFactory(): GlimpseEventFactory
    {
        return GlimpseEventFactory::new();
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }
}
