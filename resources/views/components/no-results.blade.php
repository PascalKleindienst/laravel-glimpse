@props (['icon' => '🌐', 'label' => __('glimpse::messages.no_results_yet')])
<div class="flex flex-col items-center justify-center gap-2 p-8 text-center font-mono text-xl text-mist-400 dark:text-gray-400">
    <div class="text-6xl opacity-40 {{ $attributes->get('icon:class') }}">{{ $icon }}</div>
    @if ($slot?->isNotEmpty())
        {{ $slot }}
    @else
        {{ $label }}
    @endif
</div>
