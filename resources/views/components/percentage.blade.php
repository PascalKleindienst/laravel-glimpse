@props (['percentage' => 0])
<span {{ $attributes->merge(['class' => 'text-sm text-transparent transition group-hover:text-gray-400']) }}>
    {{ \Illuminate\Support\Number::percentage($percentage) }}
</span>
