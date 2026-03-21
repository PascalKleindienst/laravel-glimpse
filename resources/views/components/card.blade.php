@props (['header' => null, 'cols' => 6, 'rows' => 1, 'scroll' => false])
@php
$headerClass = 'flex items-center justify-between px-5 text-md text-mist-400 dark:text-gray-400';

if (! ($header?->attributes['no-border'] ?? false)) {
    $headerClass .= ' border-b border-mist-200 dark:border-gray-700 py-4';
} else {
    $headerClass .= ' pt-4';
}

@endphp
<div
    {{ $attributes->merge(['class' => "shadow flex flex-col relative overflow-hidden rounded-lg border border-mist-200 bg-white dark:border-gray-700 dark:bg-gray-800 default:col-span-full default:lg:col-span-{$cols} default:row-span-{$rows}"]) }}
>
    @if ($header?->hasActualContent())
        <div {{ $header->attributes->merge(['class' => $headerClass]) }}> {{ $header }}</div>
    @endif

    @if ($slot?->hasActualContent())
        <div {{ $slot->attributes->merge(['class' => 'grow-1 px-5 py-4']) }}>
            @if ($scroll)
                <x-glimpse::scroll>{{ $slot }}</x-glimpse::scroll>
            @else
                {{ $slot }}
            @endif
        </div>
    @endif
</div>
