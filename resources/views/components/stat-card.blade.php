@props (['label', 'value' => 0, 'previous' => 0, 'format' => 'number', 'lower' => false])
@php
$valueDisplay = match($format) {
    'pct' => \Illuminate\Support\Number::format($value, 1, 1) . '%',
    'dur' => gmdate('i:s', (int)$value),
    default => \Illuminate\Support\Number::format($value),
};

$delta = ['pct' => null, 'dir' => 'flat'];
if ($previous != 0) {
    $pct = round((($value - $previous) / $previous) * 100, 1);
    $up  = $lower ? $pct < 0 : $pct > 0;
    $delta = ['pct' => abs($pct), 'dir' => $pct == 0 ? 'flat' : ($up ? 'up' : 'down')];
}

$deltaClass = match($delta['dir']) {
    'up' => 'text-accent-content bg-accent/20',
    'down' => 'text-rose-400 bg-rose-500/20',
    default => 'bg-mist-200 text-mist-500 dark:bg-gray-700 dark:text-gray-400'
}

@endphp

<div {{ $attributes }}>
    <x-glimpse::card>
        <x-slot:header no-border>
            {{ $label }}
        </x-slot:header>

        <div class="mb-4 font-mono text-3xl leading-none tracking-tight">{{ $valueDisplay }}</div>

        @if ($delta['pct'] !== null)
            <span class="inline-flux items-center gap-2 font-mono text-sm px-2 py-1 rounded-sm  {{ $deltaClass }}">
                {{ $delta['dir'] === 'up' ? '↑' : '↓' }} {{ $delta['pct'] }}%
                <span class="text-xs opacity-80">vs prev</span>
            </span>
        @else
            <span class="inline-flux flat items-center gap-2 rounded-sm px-2 py-1 font-mono text-sm {{ $deltaClass }}">— no data</span>
        @endif
    </x-glimpse::card>
</div>
