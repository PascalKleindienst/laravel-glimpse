@php use LaravelGlimpse\Values\DateRange; @endphp
@props (['dateRange', 'preset' => null])
<div {{ $attributes->merge(['class' => 'flex items-center justify-between mb-6 gap-4 flex-wrap']) }}>
    <span class="font-mono text-sm text-mist-500 uppercase dark:text-gray-400">{{ $dateRange->label() }}</span>

    <div class="flex flex-wrap gap-1 rounded border border-mist-200 bg-white p-0.5 shadow dark:border-gray-700 dark:bg-gray-800">
        @foreach (DateRange::presets() as $key => $label)
            <button
                class="font-mono text-sm px-3 py-1 border rounded cursor-pointer transition
                {{
                    $preset === $key
                        ? 'bg-accent border-accent text-accent-foreground hover:bg-teal-700 hover:text-accent-foreground dark:bg-accent/30 dark:text-teal-300 dark:border-teal-300/30'
                        : 'hover:text-mist-800 hover:bg-mist-100 dark:hover:text-white dark:hover:bg-gray-700 border-transparent  bg-transparent text-mist-400'
                }}"
                wire:click="setPreset('{{ $key }}')"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>
</div>
