<x-glimpse::card scroll :rows="$rows" :cols="$cols" wire:poll.60s :wire:key="$this->getKey()">
    <x-slot:header>
        <x-glimpse::card-title>Traffic Sources</x-glimpse::card-title>
        <div class="flex">
            <x-glimpse::tab :active="$tab === 'channels'" wire:click="setTab('channels')"> Channels </x-glimpse::tab>
            <x-glimpse::tab :active="$tab === 'referrers'" wire:click="setTab('referrers')"> Referrers </x-glimpse::tab>
        </div>
    </x-slot:header>

    <x-slot style="padding-inline: 0">
        <x-glimpse::table>
            @if ($tab === 'channels')
                @forelse ($channels as $i => $row)
                    @php
                        $pct = round(($row['visitors'] / $channelMax) * 100, 1);
                        $cls = 'ch-' . ($row['channel'] ?? 'referral');
                    @endphp
                    <x-glimpse::tr
                        :percentage="$pct"
                        :index="$i"
                        wire:key="channel-{{ $dateRange->from->toDateString() }}-{{ $dateRange->to->toDateString() }}-{{ $row['channel'] }}"
                    >
                        <x-glimpse::td>
                            <span class="channel-badge {{ $cls }}">{{ $row['channel'] ?? 'unknown' }}</span>
                        </x-glimpse::td>
                        <x-glimpse::td numeric>
                            <x-glimpse::percentage :percentage="$pct" />
                        </x-glimpse::td>
                        <x-glimpse::td numeric> {{ \Illuminate\Support\Number::format($row['visitors']) }} </x-glimpse::td>
                    </x-glimpse::tr>
                @empty
                    <tr>
                        <x-glimpse::no-results label="No referrer data yet" />
                    </tr>
                @endforelse

            @else
                @forelse ($referrers as $i => $row)
                    @php $pct = round(($row['visitors'] / $referrerMax) * 100, 1); @endphp
                    <x-glimpse::tr
                        :percentage="$pct"
                        :index="$i"
                        wire:key="referrer-{{ $dateRange->from->toDateString() }}-{{ $dateRange->to->toDateString() }}-{{ $row['domain'] }}"
                    >
                        <x-glimpse::td>{{ $row['domain'] }}</x-glimpse::td>
                        <x-glimpse::td numeric>
                            <x-glimpse::percentage :percentage="$pct" />
                        </x-glimpse::td>
                        <x-glimpse::td numeric> {{ \Illuminate\Support\Number::format($row['visitors']) }} </x-glimpse::td>
                    </x-glimpse::tr>
                @empty
                    <tr>
                        <x-glimpse::no-results label="No referrer data yet" />
                    </tr>
                @endforelse
            @endif
        </x-glimpse::table>
    </x-slot>
</x-glimpse::card>
