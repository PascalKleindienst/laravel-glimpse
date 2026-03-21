<div
    class="max-h-64 overflow-y-auto scrollbar:size-1.5 scrollbar-track:rounded scrollbar-track:bg-black/30 scrollbar-thumb:rounded scrollbar-thumb:bg-gray-400/40 scrollbar-thumb:transition hover:scrollbar-thumb:bg-gray-400"
>
    {{ $slot }}
    <div
        class="pointer-events-none absolute right-0 bottom-0 left-0 h-6 w-full origin-bottom bg-linear-to-t from-white dark:from-gray-900"
        wire:ignore
    ></div>
</div>
