{{-- Country code → emoji flag helper --}}
@php
if (! function_exists('glimpseFlag')) {
    function glimpseFlag(string $code): string {
        $code = strtoupper(trim($code));
        if (strlen($code) !== 2) {return '🌐';}
        $offset = 0x1F1E6 - ord('A');
        return mb_chr($offset + ord($code[0])) . mb_chr($offset + ord($code[1]));
    }
}
@endphp

<x-glimpse::card scroll :rows="$rows" :cols="$cols" wire:poll.60s :wire:key="$this->getKey()">
    <x-slot:header>
        <x-glimpse::card-title>Geography</x-glimpse::card-title>

        <div class="flex">
            <x-glimpse::tab :active="$tab === 'countries'" wire:click="setTab('countries')"> Countries </x-glimpse::tab>
            <x-glimpse::tab :active="$tab === 'cities'" wire:click="setTab('cities')"> Cities </x-glimpse::tab>
            <x-glimpse::tab :active="$tab === 'languages'" wire:click="setTab('languages')"> Languages </x-glimpse::tab>
        </div>
    </x-slot:header>

    <x-slot style="padding-inline: 0">
        <x-glimpse::table>
            @if ($tab === 'countries')
                @forelse ($countries as $i => $row)
                    @php $pct = round(($row['visitors'] / $countryMax) * 100, 1); @endphp
                    <x-glimpse::tr
                        :percentage="$pct"
                        :index="$i"
                        wire:key="country-{{ $dateRange->from->toDateString() }}-{{ $dateRange->to->toDateString() }}-{{ $row['country_code'] }}"
                    >
                        <x-glimpse::td>
                            <span>{{ glimpseFlag($row['country_code']) }}</span>
                            {{ $row['country_code'] }}
                        </x-glimpse::td>
                        <x-glimpse::td numeric>
                            <x-glimpse::percentage :percentage="$pct" />
                        </x-glimpse::td>
                        <x-glimpse::td numeric> {{ \Illuminate\Support\Number::format($row['visitors']) }} </x-glimpse::td>
                    </x-glimpse::tr>
                @empty
                    <tr>
                        <x-glimpse::no-results label="No geo data yet" />
                    </tr>
                @endforelse
            @elseif ($tab === 'cities')
                @forelse ($cities as $i => $row)
                    @php $pct = round(($row['visitors'] / $cityMax) * 100, 1); @endphp
                    <x-glimpse::tr
                        :percentage="$pct"
                        :index="$i"
                        wire:key="cities-{{ $dateRange->from->toDateString() }}-{{ $dateRange->to->toDateString() }}-{{ $row['city'] }}"
                    >
                        <x-glimpse::td> {{ $row['city'] }} </x-glimpse::td>
                        <x-glimpse::td numeric>
                            <x-glimpse::percentage :percentage="$pct" />
                        </x-glimpse::td>
                        <x-glimpse::td numeric> {{ \Illuminate\Support\Number::format($row['visitors']) }} </x-glimpse::td>
                    </x-glimpse::tr>
                @empty
                    <tr>
                        <x-glimpse::no-results label="No city data yet" />
                    </tr>
                @endforelse
            @else
                @forelse ($languages as $i => $row)
                    @php $pct = round(($row['visitors'] / $languageMax) * 100, 1); @endphp
                    <x-glimpse::tr
                        :percentage="$pct"
                        :index="$i"
                        wire:key="language-{{ $dateRange->from->toDateString() }}-{{ $dateRange->to->toDateString() }}-{{ $row['language'] }}"
                    >
                        <x-glimpse::td> {{ strtoupper($row['language']) }} </x-glimpse::td>
                        <x-glimpse::td numeric>
                            <x-glimpse::percentage :percentage="$pct" />
                        </x-glimpse::td>
                        <x-glimpse::td numeric> {{ \Illuminate\Support\Number::format($row['visitors']) }} </x-glimpse::td>
                    </x-glimpse::tr>
                @empty
                    <tr>
                        <x-glimpse::no-results label="No language data yet" />
                    </tr>
                @endforelse
            @endif
        </x-glimpse::table>
    </x-slot>
</x-glimpse::card>
