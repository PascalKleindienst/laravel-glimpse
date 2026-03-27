@props (['numeric' => false, 'variant' => null])

@php
$variantClass = match($variant) {
    'icon' => 'w-auto text-center',
    default => 'w-full'
};
@endphp

<td
    {{ $attributes->merge(['class' => $variantClass . ' px-1 py-3 text-sm first:pl-3 last:pr-3' . ($numeric ? ' text-right tabular-nums whitespace-nowrap' : '')]) }}
>
    {{ $slot }}
</td>
