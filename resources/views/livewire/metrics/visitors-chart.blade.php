<x-glimpse::card :rows="$rows" :cols="$cols" wire:poll.60s :wire:key="$this->getKey()">
    <x-slot:header>
        <x-glimpse::card-title>{{ __('glimpse::messages.cards.visitors_over_time') }}</x-glimpse::card-title>
        <div class="flex gap-2 font-mono text-xs">
            <span class="flex items-center gap-1">
                <span class="inline-block size-2 rounded-full bg-accent-content"></span>{{ __('glimpse::messages.columns.visitors') }}
            </span>
            <span class="flex items-center gap-1">
                <span class="inline-block size-2 rounded-full bg-sky-400"></span>{{ __('glimpse::messages.columns.views') }}
            </span>
        </div>
    </x-slot:header>

    <x-slot style="padding-inline: 0">
        <div
            wire:key="{{ $this->getKey() }}-graph"
            class="relative h-64"
            x-data="chart({
                    labels: @js($labels),
                    visitors: @js($visitors),
                    pageViews: @js($page_views)
                })"
        >
            <canvas x-ref="canvas" class="w-full"></canvas>
        </div>
    </x-slot>
</x-glimpse::card>

@script
    <script>
        const colors = {
            sky: 'rgb(0,188,255, 1)',
            skyBg: 'rgb(0,188,255, 0.04)',
            gray: 'rgba(156,163,175,0.8)',
            muted: 'rgba(156,163,175,0.2)',
            accent: 'rgb(0,213,190, 0.8)',
            accentBg: 'rgb(0,213,190, 0.04)'
        };

        const fonts = {
            mono: getComputedStyle(document.documentElement).getPropertyValue('--font-mono').trim()
        };

        Alpine.data('chart', (config) => ({
            init() {
                let chart = new Chart(this.$refs.canvas, {
                    type: 'line',
                    data: {
                        labels: config.labels,
                        datasets: [
                            {
                                label: @json (__('glimpse::messages.columns.visitors')),
                                data: config.visitors,
                                borderColor: colors.accent,
                                backgroundColor: colors.accentBg,
                                borderWidth: 1.5,
                                pointRadius: config.labels.length > 60 ? 0 : 2,
                                pointHoverRadius: 4,
                                pointBackgroundColor: colors.accent,
                                fill: true,
                                tension: 0.35
                            },
                            {
                                label: @json (__('glimpse::messages.columns.views')),
                                data: config.pageViews,
                                borderColor: colors.sky,
                                backgroundColor: colors.skyBg,
                                borderWidth: 1.5,
                                pointRadius: config.labels.length > 60 ? 0 : 2,
                                pointHoverRadius: 4,
                                pointBackgroundColor: colors.sky,
                                fill: true,
                                tension: 0.35
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgb(26,30,37, .8)',
                                borderColor: '#252931',
                                borderWidth: 1,
                                titleColor: 'rgba(226,230,237, .75)',
                                bodyColor: 'rgba(226,230,237, .9)',
                                padding: 10,
                                titleFont: { family: fonts.mono, size: 11 },
                                bodyFont: { family: fonts.mono, size: 12 }
                            }
                        },
                        scales: {
                            x: {
                                border: { display: false },
                                grid: { display: false },
                                ticks: {
                                    color: colors.gray,
                                    font: { family: fonts.mono, size: 10 },
                                    maxTicksLimit: 10,
                                    maxRotation: 0
                                }
                            },
                            y: {
                                border: { display: false },
                                grid: { color: colors.muted },
                                ticks: {
                                    color: colors.gray,
                                    font: { family: fonts.mono, size: 10 },
                                    maxTicksLimit: 5,
                                    callback: (v) => (v >= 1000 ? (v / 1000).toFixed(1) + 'k' : v)
                                },
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        }));
    </script>
@endscript
