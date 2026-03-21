<x-glimpse::card scroll :rows="$rows" :cols="$cols" wire:poll.60s :wire:key="$this->getKey()">
    <x-slot:header>
        <x-glimpse::card-title>Custom Events</x-glimpse::card-title>
        <span class="font-mono text-xs"> {{ $events->count() }} event types </span>
    </x-slot:header>

    <x-slot style="padding-inline: 0">
        <x-glimpse::table>
            @forelse ($events as $i => $event)
                @php $pct = round(($event['count'] / $max) * 100, 1); @endphp
                <x-glimpse::tr
                    :percentage="$pct"
                    :index="$i"
                    wire:key="event-{{ $dateRange->from->toDateString() }}-{{ $dateRange->to->toDateString() }}-{{ $event['event'] }}"
                >
                    <x-glimpse::td> <span class="text-amber me-2">⚡</span>{{ $event['event'] }} </x-glimpse::td>
                    <x-glimpse::td numeric>
                        <x-glimpse::percentage :percentage="$pct" />
                    </x-glimpse::td>
                    <x-glimpse::td numeric> {{ \Illuminate\Support\Number::format($event['count']) }} </x-glimpse::td>
                </x-glimpse::tr>
            @empty
                <tr>
                    <x-glimpse::no-results icon="⚡" icon:class="text-amber-400">
                        No events dispatched yet.<br />
                        <span class="mt-2 block text-sm">
                            Use <code class="rounded-sm bg-rose-400/40 px-2 py-1 font-mono text-rose-950">Glimpse::event('name')</code>
                        </span>
                    </x-glimpse::no-results>
                </tr>
            @endforelse
        </x-glimpse::table>
    </x-slot>
</x-glimpse::card>
