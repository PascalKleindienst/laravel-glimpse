<x-glimpse::card scroll :rows="$rows" :cols="$cols" wire:poll.60s :wire:key="$this->getKey()">
    <x-slot:header>
        <x-glimpse::card-title>Devices</x-glimpse::card-title>

        <div class="flex">
            <x-glimpse::tab :active="$tab === 'platforms'" wire:click="setTab('platforms')"> Platforms </x-glimpse::tab>
            <x-glimpse::tab :active="$tab === 'browsers'" wire:click="setTab('browsers')"> Browsers </x-glimpse::tab>
            <x-glimpse::tab :active="$tab === 'os'" wire:click="setTab('os')"> OS </x-glimpse::tab>
        </div>
    </x-slot:header>

    <x-slot style="padding-inline: 0">
        <x-glimpse::table>
            @if ($tab === 'platforms')
                @php
                $platformIcons = [
                    'desktop' => '🖥',
                    'mobile'  => '📱',
                    'tablet'  => '⬛',
                    'bot'     => '🤖',
                ];
            @endphp
                @forelse ($platforms as $i => $row)
                    @php
                    $icon  = $platformIcons[$row['platform']] ?? '📱';
                @endphp
                    <x-glimpse::tr
                        :percentage="$row['percentage']"
                        :index="$i"
                        wire:key="platform-{{ $dateRange->from->toDateString() }}-{{ $dateRange->to->toDateString() }}-{{ $row['platform'] }}"
                    >
                        <x-glimpse::td>{{ $icon }} {{ $row['platform'] }} </x-glimpse::td>
                        <x-glimpse::td numeric>
                            <x-glimpse::percentage :percentage="$row['percentage']" />
                        </x-glimpse::td>
                        <x-glimpse::td numeric> {{ \Illuminate\Support\Number::format($row['visitors']) }} </x-glimpse::td>
                    </x-glimpse::tr>
                @empty
                    <tr>
                        <x-glimpse::no-results icon="📱" label="No device data yet" />
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
                        <x-glimpse::no-results label="No browser data yet" />
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
                        <x-glimpse::td>{{ $row['os'] }} </x-glimpse::td>
                        <x-glimpse::td numeric>
                            <x-glimpse::percentage :percentage="$pct" />
                        </x-glimpse::td>
                        <x-glimpse::td numeric> {{ \Illuminate\Support\Number::format($row['visitors']) }} </x-glimpse::td>
                    </x-glimpse::tr>
                @empty
                    <tr>
                        <x-glimpse::no-results icon="💻" label="No os data yet" />
                    </tr>
                @endforelse
            @endif
        </x-glimpse::table>
    </x-slot>
</x-glimpse::card>
