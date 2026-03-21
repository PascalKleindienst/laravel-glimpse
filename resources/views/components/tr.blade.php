@props (['percentage' => null, 'index' => 0])
@php
$class = 'animate-row-in group relative transition hover:bg-mist-200/30 dark:hover:bg-gray-700/30';

if ($percentage) {
    $class .= ' after:absolute after:bottom-0 after:left-0 after:h-0.5 after:w-full after:animate-[grow_1s_ease_forwards] after:delay-50 after:bg-teal-400 dark:after:bg-accent-content';
}

$width = $percentage ?? 0;
@endphp
<tr {{ $attributes->merge(['class' => $class, 'style' => "--_width: {$width}%; animation-delay: " . ($index*100) . "ms"]) }}>
    {{ $slot }}
</tr>
