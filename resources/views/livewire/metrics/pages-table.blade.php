<x-glimpse::card scroll :rows="$rows" :cols="$cols" wire:poll.60s :wire:key="$this->getKey()">
    <x-slot:header>
        <x-glimpse::card-title>Top Pages</x-glimpse::card-title>
        <span class="font-mono text-xs"> {{ $pages->count() }} pages </span>
    </x-slot:header>

    <x-slot style="padding-inline: 0">
        <x-glimpse::table>
            @forelse ($pages as $i => $page)
                @php
                    $percentage = round(($page['views'] / $max) * 100, 1);
                @endphp
                <x-glimpse::tr
                    :percentage="$percentage"
                    :index="$i"
                    wire:key="pages-{{ $dateRange->from->toDateString() }}-{{ $dateRange->to->toDateString() }}-{{ $page['path'] }}"
                >
                    <x-glimpse::td>{{ $page['path'] }}</x-glimpse::td>
                    <x-glimpse::td numeric>
                        <x-glimpse::percentage :percentage="$percentage" />
                    </x-glimpse::td>
                    <x-glimpse::td numeric> {{ \Illuminate\Support\Number::format($page['views']) }} </x-glimpse::td>
                </x-glimpse::tr>
            @empty
                <tr>
                    <x-glimpse::no-results label="No page data yet" />
                </tr>
            @endforelse
        </x-glimpse::table>
    </x-slot>
</x-glimpse::card>
