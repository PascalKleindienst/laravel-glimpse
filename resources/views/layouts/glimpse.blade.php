<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Laravel Glimpse Analytics</title>

    {{ Vite::useHotFile('public/vendor/glimpse/glimpse.hot')
        ->useBuildDirectory("vendor/glimpse")
        ->withEntryPoints(['resources/css/glimpse.css', 'resources/js/glimpse.js']) }}

    @livewireStyles
</head>
<body class="bg-mist-50 font-sans antialiased dark:bg-gray-900 dark:text-white">
    <div class="grid min-h-dvh grid-rows-[52px_1fr]">
        {{-- Navigation --}}
        <nav class="sticky top-0 z-50 border-b border-mist-200 bg-white font-mono dark:border-gray-600 dark:bg-gray-800">
            <div class="container mx-auto flex h-full items-center justify-between px-6">
                <div class="flex items-center gap-2 text-lg font-medium uppercase">
                    <div
                        aria-label="{{ config('glimpse.enabled') ? __('glimpse::messages.enabled') : __('glimpse::messages.disabled') }}"
                        @class ([
                            'bg-rose-500 shadow-[0_0_8px_var(--color-rose-500)' => ! config('glimpse.enabled'),
                            'bg-emerald-500 shadow-[0_0_8px_var(--color-emerald-500)' => config('glimpse.enabled'),
                            'size-2 animate-pulse rounded-full'
                        ])
                    ></div>
                    Glimpse

                    @if (! config('glimpse.enabled'))
                        <span class="text-xs text-rose-400 italic">({{ __('glimpse::messages.disabled') }})</span>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <x-glimpse::theme-switcher />
                    {{--<span>{{ now()->translatedFormat('j M Y') }}</span>--}}
                </div>
            </div>
        </nav>

        {{-- Page --}}
        <main class="container mx-auto p-6">{{ $slot }}</main>
    </div>

    @livewireScripts
</body>
</html>
