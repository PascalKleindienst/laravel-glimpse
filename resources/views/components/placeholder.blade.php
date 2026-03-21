<x-glimpse::card :cols="$cols ?? null" :rows="$rows ?? null" :class="$class ?? ''">
    <div class="h-[30px] flex items-center w-full mb-3 @md:mb-6">
        <div class="h-6 w-1/2 animate-pulse rounded bg-gray-50 dark:bg-gray-600"></div>
    </div>
    <div class="h-56 space-y-4">
        <div class="h-8 animate-pulse rounded bg-gray-50 dark:bg-gray-600"></div>
        <div class="h-8 animate-pulse rounded bg-gray-50 dark:bg-gray-600"></div>
        <div class="h-8 animate-pulse rounded bg-gray-50 dark:bg-gray-600"></div>
    </div>
</x-glimpse::card>
