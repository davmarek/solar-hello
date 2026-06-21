<?php

use App\Models\Allocation;
use App\Models\Group;
use App\Models\Place;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public Group $group;

    #[Locked]
    public Place $place;

    public int $targetPlaceId = 0;

    public int $percentage = 0;

    #[Locked]
    public ?int $editingAllocationId = null;

    public int $editPercentage = 0;

    public function mount(Group $group, Place $place): void
    {
        $this->authorize('manage-allocations', $place);
        $this->group = $group;
        $this->place = $place;

        if (! $this->place->hasIntegration()) {
            $this->redirectRoute('places.show',
                ['group' => $group, 'place' => $place]
            );
        }
    }

    #[Computed]
    public function availableTargets(): Collection
    {
        $takenIds = $this->place->allocationsOut->pluck('target_place_id')->push($this->place->id);

        return Place::with('user')
            ->where('group_id', $this->place->group_id)
            ->whereNotIn('id', $takenIds)
            ->whereHas('allocationsIn', null, '<', Place::MAX_INCOMING_ALLOCATIONS)
            ->get();
    }

    #[Computed]
    public function allocationsOut(): Collection
    {
        return $this->place
            ->allocationsOut()
            ->with('targetPlace.user')
            ->get();
    }

    public function addAllocation(): void
    {
        $this->authorize('manage-allocations', $this->place);

        $this->validate([
            'targetPlaceId' => 'required|integer|exists:places,id',
            'percentage' => 'required|integer|min:1|max:100',
        ]);

        if ($this->targetPlaceId === $this->place->id) {
            $this->addError('targetPlaceId', 'A place cannot allocate to itself.');

            return;
        }

        $target = Place::findOrFail($this->targetPlaceId);

        if ($target->group_id !== $this->place->group_id) {
            $this->addError('targetPlaceId', 'Target place must be in the same group.');

            return;
        }

        // Check existing allocation to this target doesn't already exist
        if ($this->place->allocationsOut()->where('target_place_id', $this->targetPlaceId)->exists()) {
            $this->addError('targetPlaceId', 'An allocation to this place already exists. Edit it instead.');

            return;
        }

        // Check target hasn't reached the incoming allocation cap
        if (! $target->canReceiveMoreAllocations()) {
            $this->addError('targetPlaceId', 'This place already has the maximum of '.Place::MAX_INCOMING_ALLOCATIONS.' incoming allocations.');

            return;
        }

        // Check total would not exceed 100%
        $currentTotal = $this->place->totalAllocatedPercentage();
        if ($currentTotal + $this->percentage > 100) {
            $remaining = $this->place->remainingAllocationPercentage();
            $this->addError('percentage', "You have {$remaining}% remaining to allocate.");

            return;
        }

        Allocation::create([
            'source_place_id' => $this->place->id,
            'target_place_id' => $this->targetPlaceId,
            'percentage' => $this->percentage,
        ]);

        $this->reset(['targetPlaceId', 'percentage']);
        $this->dispatch('allocation-updated');
    }

    public function startEdit(int $allocationId): void
    {
        $this->authorize('manage-allocations', $this->place);

        $allocation = $this->place->allocationsOut()
            ->with('targetPlace')
            ->findOrFail($allocationId);
        $this->editingAllocationId = $allocationId;
        $this->editPercentage = $allocation->percentage;
    }

    public function saveEdit(): void
    {
        $this->authorize('manage-allocations', $this->place);

        $this->validate([
            'editPercentage' => 'required|integer|min:1|max:100',
        ]);

        $allocation = $this->place->allocationsOut()->findOrFail($this->editingAllocationId);

        // Total excluding the current allocation being edited
        $currentTotal = $this->place->allocationsOut()
            ->where('id', '!=', $this->editingAllocationId)
            ->sum('percentage');

        if ($currentTotal + $this->editPercentage > 100) {
            $remaining = 100 - $currentTotal;
            $this->addError('editPercentage', "You have {$remaining}% available for this allocation.");

            return;
        }

        $allocation->update(['percentage' => $this->editPercentage]);
        $this->reset(['editingAllocationId']);
        $this->dispatch('allocation-updated');
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingAllocationId']);
    }

    public function deleteAllocation(int $allocationId): void
    {
        $this->authorize('manage-allocations', $this->place);
        Allocation::where('id', $allocationId)
            ->where('source_place_id', $this->place->id) // ensure ownership
            ->delete();

        $this->dispatch('allocation-updated');
    }
};
