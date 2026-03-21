<div>
    <x-glimpse::data-range-picker :date-range="$this->dateRange" :preset="$this->preset" />

    <div class="mb-5 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        <x-glimpse::stat-card
            wire:key="visitors-stats-{{ $this->dateRange->label() }}"
            label="Visitors"
            :value="$this->summary['visitors'] ?? 0"
            :previous="$this->previousSummary['visitors'] ?? 0"
        />
        <x-glimpse::stat-card
            wire:key="page-views-stats-{{ $this->dateRange->label() }}"
            label="Page Views"
            :value="$this->summary['page_views'] ?? 0"
            :previous="$this->previousSummary['page_views'] ?? 0"
        />
        <x-glimpse::stat-card
            wire:key="bounce-rate-stats-{{ $this->dateRange->label() }}"
            label="Bounce Rate"
            :value="$this->summary['bounce_rate'] ?? 0"
            :previous="$this->previousSummary['bounce_rate'] ?? 0"
            format="pct"
            lower
        />
        <x-glimpse::stat-card
            wire:key="avg-duration-stats-{{ $this->dateRange->label() }}"
            label="Avg Duration"
            :value="$this->summary['avg_duration'] ?? 0"
            :previous="$this->previousSummary['avg_duration'] ?? 0"
            format="dur"
        />
    </div>

    <div class="mb-3 grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-12">
        <livewire:glimpse.visitors-chart :date-range="$this->dateRange" cols="12" />
        <livewire:glimpse.pages-table :date-range="$this->dateRange" cols="6" />
        <livewire:glimpse.referrers-table :date-range="$this->dateRange" cols="6" />
        <livewire:glimpse.geo-breakdown :date-range="$this->dateRange" cols="4" />
        <livewire:glimpse.devices-breakdown :date-range="$this->dateRange" cols="4" />
        <livewire:glimpse.events-table :date-range="$this->dateRange" cols="4" />
    </div>
</div>
