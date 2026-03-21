@props (['active' => false])
@php
$activeClass = $active ? 'text-accent-content border-b-accent-content' : 'border-b-transparent';
@endphp
<button
    {{ $attributes->merge(['class' => 'font-mono text-xs tracking-wider uppercase bg-transparent px-2.5 py-1 cursor-pointer border-b-2  transition hover:text-accent-content ' . $activeClass ]) }}
>
    {{ $slot }}
</button>
