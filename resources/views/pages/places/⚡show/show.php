<?php

use App\Livewire\Forms\PlaceForm;
use App\Models\Allocation;
use App\Models\AvailablePowerSnapshot;
use App\Models\Group;
use App\Models\Place;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

new class extends Component
{
    const int LAST_X_HOURS = 24;

    #[Locked]
    public Group $group;

    public Place $place;

    public array $chartSeries = [];

    public PlaceForm $editForm;

    public function mount(Place $place): void
    {
        $this->authorize('view', $place);
        $place->load('integration.provider', 'energyReadings', 'availablePowerSnapshots', 'latestReading', 'latestSnapshot');
        $this->place = $place;
        $this->editForm->setPlace($place);

        $this->buildChartSeries();

        $this->dispatch('chart-data-updated');
    }

    private function buildChartSeries(): void
    {
        $recentReadings = $this->place->energyReadings
            ->where('recorded_at', '>=', now()->subHours(self::LAST_X_HOURS));

        $recentSnapshots = $this->place->availablePowerSnapshots
            ->where('calculated_at', '>=', now()->subHours(self::LAST_X_HOURS));

        $series = [];
        if ($recentReadings->isNotEmpty()) {
            $series[] = [
                'name' => 'Grid Exchange',
                'type' => 'line',
                'step' => 'end',
                'data' => $recentReadings
                    ->map(fn ($r) => [$r->recorded_at->toIso8601String(), $r->grid_power_w])
                    ->values()->all(),
                'itemStyle' => ['opacity' => 0],
            ];
        }

        $allocated = $recentSnapshots
            ->map(fn ($s) => [$s->calculated_at->toIso8601String(), $s->allocated_w])
            ->values();

        if ($allocated->count() > 0) {
            $series[] = [
                'name' => 'Allocated',
                'type' => 'line',
                'step' => 'end',
                'data' => $allocated->all(),
                'itemStyle' => ['opacity' => 0],
            ];
        }

        // Provider specific series
        $providerName = strtolower($this->place->integration?->provider->name ?? '');

        if ($providerName === 'solax') {
            $series[] = [
                'name' => 'Inverter Power',
                'type' => 'line',
                'step' => 'end',
                'data' => $recentReadings
                    ->map(fn ($r) => [$r->recorded_at->toIso8601String(), $r->inverter_power_w])
                    ->values()->all(),
                'itemStyle' => ['opacity' => 0],
            ];
            $series[] = [
                'name' => 'Battery SoC',
                'type' => 'line',
                'step' => 'end',
                'yAxisIndex' => 1, // Right side of graph (0-100%)
                'data' => $recentReadings
                    ->map(fn ($r) => [$r->recorded_at->toIso8601String(), $r->battery_soc_pct])
                    ->values()->all(),
                'itemStyle' => ['opacity' => 0],
            ];
        }

        $this->chartSeries = $series;
    }

    public function updatePlace(): void
    {
        $this->authorize('update', $this->place);
        $this->editForm->update();
        $this->modal('place-modal')->close();
        Toaster::success("Place \"{$this->place->name}\" was updated.");
    }

    public function deletePlace(): void
    {
        $this->authorize('delete', $this->place);

        $name = $this->place->name;

        // Energy readings are removed by the FK cascadeOnDelete when the
        // integration is deleted.
        $this->place->integration?->delete();
        $this->place->delete();

        session()->flash('message', "Place \"{$name}\" was deleted.");

        $this->redirectRoute('groups.show', ['group' => $this->group], navigate: true);
    }

    public function deleteIntegration(): void
    {
        $this->authorize('manage-integrations', $this->place);

        $integration = $this->place->integration;

        if (! $integration) {
            return;
        }

        $label = $integration->label;

        // Energy readings are removed by the FK cascadeOnDelete on integration_id.
        $integration->delete();

        // Refresh relations and re-build the chart so it updates instantly
        $this->place->load('integration.provider', 'energyReadings', 'availablePowerSnapshots', 'latestReading', 'latestSnapshot');
        $this->buildChartSeries();
        $this->dispatch('chart-data-updated');

        Toaster::success("Integration \"{$label}\" was removed.");
    }

    #[Computed]
    public function hasIncomingAllocations(): bool
    {
        return Allocation::where('target_place_id', $this->place->id)->exists();
    }

    #[Computed]
    public function allocationUtilisation(): array
    {
        $allocations = Allocation::where('source_place_id', $this->place->id)
            ->with('targetPlace')
            ->get();

        if ($allocations->isEmpty()) {
            return [];
        }

        $targetPlaceIds = $allocations->pluck('target_place_id');

        // Aggregates for the last 7 days for accurate utilisation
        $aggregates = AvailablePowerSnapshot::whereIn('place_id', $targetPlaceIds)
            ->where('calculated_at', '>=', now()->subDays(7))
            ->selectRaw('
                place_id,
                SUM(CASE WHEN consumption_w IS NOT NULL THEN allocated_w ELSE 0 END) as total_allocated,
                SUM(consumption_w) as total_consumption
            ')
            ->groupBy('place_id')
            ->get()
            ->keyBy('place_id');

        // Provide the latest snapshot for is_estimate and fallback
        $latestSnapshotIds = AvailablePowerSnapshot::whereIn('place_id', $targetPlaceIds)
            ->selectRaw('MAX(id) as id')
            ->groupBy('place_id')
            ->pluck('id');

        $latestSnapshots = AvailablePowerSnapshot::whereIn('id', $latestSnapshotIds)
            ->get()
            ->keyBy('place_id');

        return $allocations->map(function ($allocation) use ($aggregates, $latestSnapshots) {
            $latest = $latestSnapshots->get($allocation->target_place_id);
            $aggregate = $aggregates->get($allocation->target_place_id);

            $utilisationPct = null;

            if ($aggregate && $aggregate->total_allocated > 0 && $aggregate->total_consumption !== null) {
                $utilisationPct = min(100, round(
                    ($aggregate->total_consumption / $aggregate->total_allocated) * 100, 1
                ));
            }

            return [
                'allocation' => $allocation,
                'target_name' => $allocation->targetPlace->name,
                'percentage' => $allocation->percentage,
                'allocated_w' => $latest?->allocated_w,
                'consumption_w' => $latest?->consumption_w,
                'utilisation_pct' => $utilisationPct,
                'is_estimate' => $latest?->is_estimate ?? true,
            ];
        })->all();
    }
};
