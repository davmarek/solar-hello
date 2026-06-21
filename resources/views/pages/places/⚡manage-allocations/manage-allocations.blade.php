<div class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center">
        <flux:button
            :href="route('places.show',['group'=>$group, 'place'=>$place])"
            icon="arrow-left"
            square
            variant="subtle"
            size="sm"
            class="p-0"
        />
        <div>
            <flux:subheading class="mb-0"
                >Managing allocations for</flux:subheading
            >
            <h1 class="text-3xl font-bold">{{ $place->name }}</h1>
        </div>
    </div>

    {{-- ── Remaining capacity indicator ──────────────────────────────────── --}}
    @php $remaining = $place->remainingAllocationPercentage(); @endphp

    <div class="flex items-center gap-3">
        <div
            class="h-2 flex-1 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-700"
        >
            <div
                class="h-full rounded-full transition-all {{ $remaining === 0 ? 'bg-orange-500' : 'bg-green-500' }}"
                style="width: {{ 100 - $remaining }}%"
            ></div>
        </div>
        <span class="shrink-0 text-sm text-zinc-500">
            {{ 100 - $remaining }}% allocated · {{ $remaining }}% remaining
        </span>
    </div>

    {{-- ── Existing allocations ───────────────────────────────────────────── --}}
    <flux:heading size="lg">Existing allocations</flux:heading>
    @forelse ($this->allocationsOut as $allocation)
        <div
            wire:key="allocation-{{ $allocation->id }}"
            class="flex items-center gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700"
        >
            <div class="flex-1">
                <p class="font-medium text-zinc-900 dark:text-white">{{ $allocation->targetPlace->name }}</p>
                <p class="text-sm text-zinc-500">{{ $allocation->targetPlace->user->name }}</p>
            </div>

            @if ($editingAllocationId === $allocation->id)
                <div class="flex items-center gap-2">
                    <flux:input
                        wire:model="editPercentage"
                        type="number"
                        min="1"
                        max="100"
                        class="w-20"
                        suffix="%"
                    />
                    <flux:error name="editPercentage" />
                    <flux:button
                        wire:click="saveEdit"
                        variant="primary"
                        size="sm"
                    >
                        Save
                    </flux:button>
                    <flux:button
                        wire:click="cancelEdit"
                        variant="ghost"
                        size="sm"
                        >Cancel</flux:button
                    >
                </div>
            @else
                <flux:badge size="lg"
                    >{{ $allocation->percentage }}%</flux:badge
                >
                <flux:button
                    wire:click="startEdit({{ $allocation->id }})"
                    variant="ghost"
                    icon="pencil"
                    size="sm"
                />
                <flux:button
                    wire:click="deleteAllocation({{ $allocation->id }})"
                    wire:confirm="Remove this allocation?"
                    variant="ghost"
                    icon="trash"
                    size="sm"
                />
            @endif
        </div>
    @empty
        <p class="text-sm text-zinc-400 italic">No allocations defined yet. Add one below.</p>
    @endforelse

    {{-- Add new allocation --}}
    @if ($remaining > 0 && $this->availableTargets->isNotEmpty())
        <flux:separator />
        <div class="space-y-4">
            <flux:heading size="lg">Add allocation</flux:heading>
            <div class="flex flex-col items-stretch gap-3 md:flex-row">
                <flux:field class="flex-1">
                    <flux:label>Target place</flux:label>
                    <flux:select wire:model="targetPlaceId">
                        <flux:select.option wire:key="0" value="0" disabled>
                            Select a place…
                        </flux:select.option>

                        @foreach ($this->availableTargets as $target)
                            <flux:select.option
                                wire:key="{{ $target->id }}"
                                value="{{ $target->id }}"
                            >
                                {{ $target->name }} ({{ $target->user->name }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="targetPlaceId" class="md:hidden" />
                </flux:field>

                <flux:field class="md:w-28">
                    <flux:label>Percentage</flux:label>
                    <flux:input
                        wire:model="percentage"
                        type="number"
                        min="1"
                        max="{{ $remaining }}"
                        placeholder="e.g. 30"
                        suffix="%"
                    />
                    <flux:error name="percentage" class="md:hidden" />
                </flux:field>

                <div class="pt-6">
                    <flux:button wire:click="addAllocation" variant="primary"
                        >Add</flux:button
                    >
                </div>
            </div>
            <flux:error name="targetPlaceId" class="hidden md:block" />
            <flux:error name="percentage" class="hidden md:block" />
        </div>
    @elseif ($remaining === 0)
        <flux:callout icon="check-circle" variant="success">
            <flux:callout.text>
                You have allocated 100% of your excess power.</flux:callout.text
            >
        </flux:callout>
    @elseif ($this->availableTargets->isEmpty())
        <p class="text-sm text-zinc-400 italic">All places in the group already have an allocation from you.</p>
    @endif
</div>
