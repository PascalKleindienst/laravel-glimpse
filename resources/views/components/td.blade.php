@props (['numeric' => false])
<td
    {{ $attributes->merge(['class' => 'w-full px-1 py-3 text-sm first:pl-3 last:pr-3' . ($numeric ? ' text-right tabular-nums whitespace-nowrap' : '')]) }}
>
    {{ $slot }}
</td>
