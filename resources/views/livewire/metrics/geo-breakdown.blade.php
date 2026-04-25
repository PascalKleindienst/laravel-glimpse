@php /** @var \Illuminate\Support\Collection<array-key, array{country: \LaravelGlimpse\Values\Country, visitors: int}> $countries */ @endphp
<x-glimpse::card scroll :rows="$rows" :cols="$cols" wire:poll.60s :wire:key="$this->getKey()">
    <x-slot:header>
        <x-glimpse::card-title>{{ __('glimpse::messages.cards.geography') }}</x-glimpse::card-title>

        <div class="flex">
            <x-glimpse::tab :active="$tab === 'countries'" wire:click="setTab('countries')">
                {{ __('glimpse::messages.tabs.countries') }}
            </x-glimpse::tab>
            <x-glimpse::tab :active="$tab === 'cities'" wire:click="setTab('cities')"> {{ __('glimpse::messages.tabs.cities') }} </x-glimpse::tab>
            <x-glimpse::tab :active="$tab === 'languages'" wire:click="setTab('languages')">
                {{ __('glimpse::messages.tabs.languages') }}
            </x-glimpse::tab>
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
                        wire:key="country-{{ $dateRange->from->toDateString() }}-{{ $dateRange->to->toDateString() }}-{{ $row['country']->iso }}"
                    >
                        <x-glimpse::td>
                            <span>{{ $row['country']->flag }}</span>
                            {{ $row['country']->name }}
                        </x-glimpse::td>
                        <x-glimpse::td numeric>
                            <x-glimpse::percentage :percentage="$pct" />
                        </x-glimpse::td>
                        <x-glimpse::td numeric> {{ \Illuminate\Support\Number::format($row['visitors']) }} </x-glimpse::td>
                    </x-glimpse::tr>
                @empty
                    <tr>
                        <x-glimpse::no-results :label="__('glimpse::messages.no_geo_data')" />
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
                        <x-glimpse::no-results :label="__('glimpse::messages.no_city_data')" />
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
                        <x-glimpse::no-results :label="__('glimpse::messages.no_language_data')" />
                    </tr>
                @endforelse
            @endif
        </x-glimpse::table>
    </x-slot>
</x-glimpse::card>
