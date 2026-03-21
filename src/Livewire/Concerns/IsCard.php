<?php

declare(strict_types=1);

namespace LaravelGlimpse\Livewire\Concerns;

use LaravelGlimpse\Contracts\QueryServiceContract;
use LaravelGlimpse\Values\DateRange;
use Livewire\Attributes\Reactive;

trait IsCard
{
    #[Reactive]
    public DateRange $dateRange;

    /**
     * The number of columns to span.
     *
     * @var string|null|int<1, 12>
     */
    public int|string|null $cols = null;

    /**
     * The number of rows to span.
     *
     * @var string|null|int<1, 6>
     */
    public int|string|null $rows = null;

    private QueryServiceContract $query;

    /**
     * Render the placeholder.
     */
    // public function placeholder(): Renderable
    // {
    //     return View::make('glimpse::components.placeholder', [
    //         'cols' => $this->cols ?? null,
    //         'rows' => $this->rows ?? null,
    //     ]);
    // }

    public function getKey(): string
    {
        return self::class.':'.$this->dateRange->from->toDateString().'-'.$this->dateRange->to->toDateString();
    }

    public function bootIsCard(): void
    {
        $this->query = resolve(QueryServiceContract::class);
    }
}
