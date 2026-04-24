<?php

declare(strict_types=1);

namespace LaravelGlimpse\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LaravelGlimpse\Database\Factories\GlimpseAggregateFactory;
use LaravelGlimpse\Enums\Period;
use Override;

/**
 * @property int $id
 * @property Period $period
 * @property CarbonInterface $date
 * @property int $hour
 * @property string $metric
 * @property string $dimension
 * @property float $value
 * @property int $count
 * @property CarbonInterface $aggregated_at
 *
 * @method static GlimpseAggregateFactory factory($count = null, $state = [])
 * @method static Builder<static>|GlimpseAggregate newModelQuery()
 * @method static Builder<static>|GlimpseAggregate newQuery()
 * @method static Builder<static>|GlimpseAggregate query()
 *
 * @mixin Model
 */
final class GlimpseAggregate extends Model
{
    /** @use HasFactory<GlimpseAggregateFactory> */
    use HasFactory;

    public $timestamps = false;

    public $guarded = ['id'];

    protected static function newFactory(): GlimpseAggregateFactory
    {
        return GlimpseAggregateFactory::new();
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'period' => Period::class,
            'aggregated_at' => 'timestamp',
            'date' => 'date',
            'hour' => 'integer',
            'value' => 'float',
            'count' => 'integer',
        ];
    }
}
