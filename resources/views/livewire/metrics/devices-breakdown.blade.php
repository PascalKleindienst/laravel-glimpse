@php /** @var \Illuminate\Support\Collection<array-key, array{platform: \LaravelGlimpse\Enums\Platform, visitors: int, percentage: float}> $platforms */ @endphp
@php /** @var \Illuminate\Support\Collection<array-key, array{os: \LaravelGlimpse\Values\Os, visitors: int}> $os */ @endphp

<x-glimpse::card scroll :rows="$rows" :cols="$cols" wire:poll.60s :wire:key="$this->getKey()">
    <x-slot:header>
        <x-glimpse::card-title>{{ __('glimpse::messages.cards.devices') }}</x-glimpse::card-title>

        <div class="flex">
            <x-glimpse::tab :active="$tab === 'platforms'" wire:click="setTab('platforms')">
                {{ __('glimpse::messages.tabs.platforms') }}
            </x-glimpse::tab>
            <x-glimpse::tab :active="$tab === 'browsers'" wire:click="setTab('browsers')">
                {{ __('glimpse::messages.tabs.browsers') }}
            </x-glimpse::tab>
            <x-glimpse::tab :active="$tab === 'os'" wire:click="setTab('os')"> {{ __('glimpse::messages.tabs.os') }} </x-glimpse::tab>
        </div>
    </x-slot:header>

    <x-slot style="padding-inline: 0">
        <x-glimpse::table>
            @if ($tab === 'platforms')
                @forelse ($platforms as $i => $row)
                    <x-glimpse::tr
                        :percentage="$row['percentage']"
                        :index="$i"
                        wire:key="platform-{{ $dateRange->from->toDateString() }}-{{ $dateRange->to->toDateString() }}-{{ $row['platform']->name }}"
                    >
                        <x-glimpse::td variant="icon">
                            <span @class (['text-purple-400' => $row['platform']->value === 'bot'])> {{ $row['platform']->icon() }} </span>
                        </x-glimpse::td>
                        <x-glimpse::td>{{ $row['platform']->name }} </x-glimpse::td>
                        <x-glimpse::td numeric>
                            <x-glimpse::percentage :percentage="$row['percentage']" />
                        </x-glimpse::td>
                        <x-glimpse::td numeric> {{ \Illuminate\Support\Number::format($row['visitors']) }} </x-glimpse::td>
                    </x-glimpse::tr>
                @empty
                    <tr>
                        <x-glimpse::no-results icon="📱" :label="__('glimpse::messages.no_device_data')" />
                    </tr>
                @endforelse
            @elseif ($tab === 'browsers')
                @forelse ($browsers as $i => $row)
                    @php $pct = round(($row['visitors'] / $browserMax) * 100, 1); @endphp
                    <x-glimpse::tr
                        :percentage="$pct"
                        :index="$i"
                        wire:key="browser-{{ $dateRange->from->toDateString() }}-{{ $dateRange->to->toDateString() }}-{{ $row['browser'] }}"
                    >
                        <x-glimpse::td>{{ $row['browser'] }} </x-glimpse::td>
                        <x-glimpse::td numeric>
                            <x-glimpse::percentage :percentage="$pct" />
                        </x-glimpse::td>
                        <x-glimpse::td numeric> {{ \Illuminate\Support\Number::format($row['visitors']) }} </x-glimpse::td>
                    </x-glimpse::tr>
                @empty
                    <tr>
                        <x-glimpse::no-results :label="__('glimpse::messages.no_browser_data')" />
                    </tr>
                @endforelse
            @else
                @forelse ($os as $i => $row)
                    @php $pct = round(($row['visitors'] / $osMax) * 100, 1); @endphp
                    <x-glimpse::tr
                        :percentage="$pct"
                        :index="$i"
                        wire:key="os-{{ $dateRange->from->toDateString() }}-{{ $dateRange->to->toDateString() }}-{{ $row['os'] }}"
                    >
                        <x-glimpse::td variant="icon">
                            <span
                                @class ([
                                'text-sky-400' => $row['os']->icon === 'windows',
                                'text-mist-300' => $row['os']->icon === 'mac',
                                'text-mist-800 dark:text-mist-300' => $row['os']->icon === 'linux',
                                'text-emerald-400' => $row['os']->icon === 'android',
                            ])
                            >
                                @if ($row['os']->icon === 'windows')
                                    <x-glimpse::icon.windows />
                                @elseif ($row['os']->icon === 'mac')
                                    <x-glimpse::icon.mac />
                                @elseif ($row['os']->icon === 'android')
                                    <x-glimpse::icon.android />
                                @elseif ($row['os']->icon === 'linux')
                                    <x-glimpse::icon.linux />
                                @else
                                    💻
                                @endif
                            </span>
                        </x-glimpse::td>
                        <x-glimpse::td>{{ $row['os']->name }} </x-glimpse::td>
                        <x-glimpse::td numeric>
                            <x-glimpse::percentage :percentage="$pct" />
                        </x-glimpse::td>
                        <x-glimpse::td numeric> {{ \Illuminate\Support\Number::format($row['visitors']) }} </x-glimpse::td>
                    </x-glimpse::tr>
                @empty
                    <tr>
                        <x-glimpse::no-results icon="💻" :label="__('glimpse::messages.no_os_data')" />
                    </tr>
                @endforelse
            @endif
        </x-glimpse::table>
    </x-slot>
</x-glimpse::card>
