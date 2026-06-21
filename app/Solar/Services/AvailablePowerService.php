<?php

namespace App\Solar\Services;

use App\Models\AvailablePowerSnapshot;
use App\Models\Place;

readonly class AvailablePowerService
{
    /**
     * Calculate and persist an AvailablePowerSnapshot for the given place.
     *
     * Available power for a place is the sum of contributions from all source
     * places that have an allocation targeting it:
     *
     *   contribution = max(0, source.grid_power_w_sum) * (percentage / 100)
     *
     * grid_power_w is positive when exporting to the grid (surplus available to share)
     * and negative when importing (place is consuming from the grid, contributes nothing).
     * Negative values are clamped to zero in the allocation calculation.
     *
     * The place's own consumption is derived from its own grid_power_w:
     *   - If grid_power_w is negative, the place is consuming abs(grid_power_w) from the grid
     *   - If grid_power_w is positive, the place is self-sufficient and exporting surplus
     *
     * is_estimate is true when the place has no integration data of its own,
     * meaning we know what's allocated to it but not what it is actually consuming.
     */
    public function calculateAndPersist(Place $place): AvailablePowerSnapshot
    {
        $place->loadMissing([
            'latestReading',
            'allocationsIn.sourcePlace.latestReading',
        ]);

        // ── Allocated power ────────────────────────────────────────────────────
        $allocatedW = 0.0;

        foreach ($place->allocationsIn as $allocation) {
            $sourceGridW = $allocation->sourcePlace->latestReading?->grid_power_w ?? 0.0;

            // Clamp to zero — a place importing from the grid contributes nothing
            $allocatedW += max(0.0, $sourceGridW) * ($allocation->percentage / 100);
        }

        // ── Own consumption ────────────────────────────────────────────────────
        // Derived from grid_power_w: negative means the place is importing (consuming).
        // Positive means exporting — the place is self-sufficient, consumption from
        // the grid is zero (it may still be consuming, but not from the grid).
        $ownGridW = $place->latestReading?->grid_power_w;

        // null means no integration data exists for this place yet
        $consumptionW = $ownGridW !== null
            ? abs(min(0.0, $ownGridW))  // keep only the importing (negative) portion as a positive magnitude
            : null;

        $isEstimate = $consumptionW === null;
        $netW = $isEstimate ? null : $allocatedW - $consumptionW;

        return AvailablePowerSnapshot::firstOrCreate([
            'place_id' => $place->id,
            'group_id' => $place->group_id,
            'calculated_at' => now()->floorMinute(5),
        ], [
            'allocated_w' => $allocatedW,
            'consumption_w' => $consumptionW,
            'net_w' => $netW,
            'is_estimate' => $isEstimate,
        ]);
    }
}
