<div class="space-y-6">
    <x-flash-message-callout />

    {{-- Top bar with group name and action buttons --}}
    <div class="flex flex-col gap-4 md:flex-row md:items-center">
        <flux:button
            :href="route('groups.show', $group)"
            icon="arrow-left"
            square
            variant="subtle"
            size="sm"
            class="p-0"
        />
        <div>
            <flux:subheading>Place</flux:subheading>
            <h1 class="text-3xl font-bold">{{ $place->name }}</h1>
        </div>
    </div>
    @isset ($place->description)
        <flux:subheading>{{ $place->description }}</flux:subheading>
    @endisset

    {{-- Action Buttons in header --}}
    <div class="flex flex-wrap gap-3">
        @if ($place->hasIntegration())
            <flux:button
                variant="primary"
                :href="route('places.manage-allocations', ['group' => $group, 'place' => $place])"
                icon="percent-badge"
                wire:navigate
            >
                Manage allocations
            </flux:button>
        @else
            <flux:button
                :href="route('integrations.create', ['group' => $group, 'place' => $place])"
                icon="plus"
                wire:navigate
            >
                Add Integration
            </flux:button>
        @endif

        <flux:modal.trigger name="place-modal">
            <flux:button icon="pencil"> Edit Place</flux:button>
        </flux:modal.trigger>
    </div>

    {{-- Allocation utilization --}}
    @if (count($this->allocationUtilisation) > 0)
        <flux:card class="space-y-4">
            <flux:heading size="lg" class="mb-0"
                >Shared power utilisation</flux:heading
            >
            <flux:text class="text-zinc-500">
                How much of the power you are sharing is actually being used.
            </flux:text>

            @foreach ($this->allocationUtilisation as $item)
                <div class="space-y-1">
                    <div class="flex items-center justify-between text-sm">
                        <span
                            class="font-medium"
                            >{{ $item['target_name'] }}</span
                        >
                        <div class="flex items-center gap-3 text-zinc-500">
                            <span
                                >{{ $item['percentage'] }}% of your export
                                allocated</span
                            >
                            @if ($item['utilisation_pct'] !== null)
                                <flux:badge
                                    @class([
                                'bg-green-100 text-green-800' => $item['utilisation_pct'] < 60,
                                'bg-yellow-100 text-yellow-800' => $item['utilisation_pct'] >= 60 && $item['utilisation_pct'] < 90,
                                'bg-red-100 text-red-800' => $item['utilisation_pct'] >= 90,
                            ])
                                >
                                    {{ $item['utilisation_pct'] }}% utilised
                                </flux:badge>
                            @elseif ($item['is_estimate'])
                                <flux:badge>No consumption data</flux:badge>
                            @else
                                <flux:badge>Awaiting data</flux:badge>
                            @endif
                        </div>
                    </div>

                    {{-- Progress bar --}}
                    @if ($item['utilisation_pct'] !== null)
                        <div
                            class="h-1.5 w-full rounded-full bg-zinc-100 dark:bg-zinc-700"
                        >
                            <div
                                @class ([
                                    "h-full rounded-full transition-all",
                                    'bg-red-500' => $item['utilisation_pct'] >= 90,
                                    'bg-yellow-500' => $item['utilisation_pct'] < 90 && $item['utilisation_pct'] >= 60,
                                    'bg-green-500' => $item['utilisation_pct'] < 60,
                                ])
                                style="width: {{ $item['utilisation_pct'] }}%"
                            ></div>
                        </div>
                    @endif
                </div>
            @endforeach
        </flux:card>
    @endif

    {{-- Power Statistics Cards --}}
    <div class="grid grid-cols-2 gap-4">
        @if (! $this->hasIncomingAllocations)
            <flux:callout icon="information-circle">
                <flux:callout.heading>
                    No incoming allocations yet
                </flux:callout.heading>
                <flux:callout.text>
                    Producing places in your group can share a percentage of
                    their excess solar export with you. Ask a producer to open
                    their place settings and add an allocation for your place.
                </flux:callout.text>
            </flux:callout>

        @elseif (! $place->latestSnapshot)
            <flux:callout icon="clock">
                <flux:callout.heading>
                    Waiting for producer data
                </flux:callout.heading>
                <flux:callout.text>
                    Allocations are configured but no energy data has arrived
                    yet. Available power will appear here after the first sync.
                </flux:callout.text>
            </flux:callout>

        @else
            <flux:card
                class="flex flex-col justify-center bg-yellow-100 dark:bg-yellow-500/50 dark:saturate-70"
            >
                <flux:text>Available power</flux:text>
                <flux:heading size="xl">
                    {{ number_format($place->latestSnapshot->allocated_w) }} W
                </flux:heading>
            </flux:card>
        @endif

        @if (! $place->integration)
            <flux:callout icon="bolt-slash">
                <flux:callout.heading>No device connected</flux:callout.heading>
                <flux:callout.text>
                    Connect an integration to see live grid exchange data for
                    this place.
                </flux:callout.text>
                <x-slot name="actions">
                    <flux:button
                        :href="route('integrations.create', ['group' => $group, 'place' => $place])"
                        icon="plus"
                        wire:navigate
                        size="sm"
                    >
                        Add Integration
                    </flux:button>
                </x-slot>
            </flux:callout>

        @elseif (! $place->latestReading)
            <flux:callout icon="clock">
                <flux:callout.heading>
                    Waiting for first sync
                </flux:callout.heading>
                <flux:callout.text>
                    {{ $place->integration->provider->display_name }} is
                    connected. Live readings will appear here shortly.
                </flux:callout.text>
            </flux:callout>

        @else
            @php
                $gridPowerW = $place->latestReading->grid_power_w ?? 0;
            @endphp
            <flux:card
                @class([
                    'flex flex-col justify-center',
                    'bg-red-100 dark:bg-red-500/50'     => $gridPowerW < 0,
                    'bg-green-100 dark:bg-green-500/50' => $gridPowerW > 0,
                    'bg-zinc-100 dark:bg-zinc-700'      => $gridPowerW === 0,
                ])
            >
                <flux:text>
                    @if ($gridPowerW < 0)
                        Importing from grid
                    @elseif ($gridPowerW > 0)
                        Exporting to grid
                    @else
                        No exchange with grid
                    @endif
                </flux:text>
                <flux:heading size="xl">
                    {{ number_format(abs($gridPowerW)) }} W
                </flux:heading>
            </flux:card>

        @endif
    </div>

    @if ($this->hasIncomingAllocations || $place->integration)
        <flux:card class="overflow-clip p-0">
            <div
                wire:ignore
                class="aspect-12/9 h-full w-full lg:aspect-video xl:aspect-21/9"
                @resize.window="$js.resizeChart"
                data-echarts
            ></div>
        </flux:card>
    @endif

    @if ($place->integration)
        <div class="mt-8 space-y-3">
            <flux:heading size="lg">Integration</flux:heading>
            <flux:card class="flex items-center justify-between gap-4">
                <div>
                    <flux:heading size="md">
                        {{ $place->integration->label }}
                    </flux:heading>
                    <flux:text class="text-zinc-500">
                        {{ $place->integration->provider->display_name }}
                    </flux:text>
                </div>
                <flux:button
                    wire:click="deleteIntegration"
                    wire:confirm="Remove this integration? This is permanent - all of its energy readings will be deleted and cannot be recovered."
                    variant="danger"
                    icon="trash"
                    size="sm"
                >
                    Remove
                </flux:button>
            </flux:card>
        </div>
    @endif
    {{-- Edit place modal --}}
    <flux:modal name="place-modal" class="w-10/12 md:max-w-2xl">
        <form wire:submit="updatePlace" class="space-y-6">
            <flux:heading size="lg">Edit place</flux:heading>
            <flux:input
                wire:model.live.debounce="editForm.name"
                label="Name"
                type="text"
                badge="Required"
                placeholder="Main Street 123 or My House"
                required
            />

            <flux:textarea
                wire:model="editForm.description"
                label="Description"
                type="text"
                placeholder="The one with the solar panels"
            />

            <div class="flex justify-between">
                <flux:button
                    type="button"
                    variant="danger"
                    icon="trash"
                    wire:click="deletePlace"
                    wire:confirm="Delete this place? This is permanent - the place and its integration will be removed and cannot be recovered."
                >
                    Delete
                </flux:button>
                <div class="flex space-x-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">
                        Save
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
