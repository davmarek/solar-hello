<?php

namespace App\Jobs;

use App\Models\Allocation;
use App\Models\Place;
use App\Solar\Services\AvailablePowerService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CalculateAvailablePowerJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly int $placeId) {}

    /**
     * Execute the job.
     */
    public function handle(AvailablePowerService $service): void
    {
        // Skip calculation entirely if no one is sharing power with this place.
        // Allocations change rarely so this check is cheap relative to the
        // full calculation that would follow.
        if (! Allocation::where('target_place_id', $this->placeId)->exists()) {
            return;
        }

        $place = Place::with(['allocationsIn.sourcePlace'])->findOrFail($this->placeId);
        $service->calculateAndPersist($place);
    }

    public function uniqueId(): string
    {
        return 'calculate_available_power-'.$this->placeId;
    }
}
