<?php

use App\Models\Allocation;
use App\Models\Group;
use App\Models\Integration;
use App\Models\IntegrationProvider;
use App\Models\Place;
use App\Models\User;
use Database\Seeders\IntegrationProviderSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(IntegrationProviderSeeder::class);

    $this->owner = User::factory()->create();
    $this->group = Group::factory()->create(['owner_id' => $this->owner->id]);
    $this->source = Place::factory()->create([
        'group_id' => $this->group->id,
        'user_id' => $this->owner->id,
    ]);

    // manage-allocations redirects unless the source has an integration.
    $provider = IntegrationProvider::where('name', 'mock_shelly')->firstOrFail();
    Integration::create([
        'place_id' => $this->source->id,
        'provider_id' => $provider->id,
        'label' => 'Test',
        'credentials' => ['device_id' => 'd', 'api_key' => 'k'],
        'status' => 'pending',
    ]);

    $this->actingAs($this->owner);
});

function fillTargetIncoming(Place $target, int $count): void
{
    for ($i = 0; $i < $count; $i++) {
        $sourcePlace = Place::factory()->create([
            'group_id' => $target->group_id,
            'user_id' => User::factory()->create()->id,
        ]);

        Allocation::create([
            'source_place_id' => $sourcePlace->id,
            'target_place_id' => $target->id,
            'percentage' => 10,
        ]);
    }
}

test('addAllocation succeeds when target is below the incoming cap', function () {
    $target = Place::factory()->create([
        'group_id' => $this->group->id,
        'user_id' => User::factory()->create()->id,
    ]);
    fillTargetIncoming($target, 4);

    Livewire::test('pages::places.manage-allocations', [
        'group' => $this->group,
        'place' => $this->source,
    ])
        ->set('targetPlaceId', $target->id)
        ->set('percentage', 10)
        ->call('addAllocation')
        ->assertHasNoErrors();

    expect($target->fresh()->incomingAllocationsCount())->toBe(5);
});

test('addAllocation fails when target already has the maximum incoming allocations', function () {
    $target = Place::factory()->create([
        'group_id' => $this->group->id,
        'user_id' => User::factory()->create()->id,
    ]);
    fillTargetIncoming($target, Place::MAX_INCOMING_ALLOCATIONS);

    Livewire::test('pages::places.manage-allocations', [
        'group' => $this->group,
        'place' => $this->source,
    ])
        ->set('targetPlaceId', $target->id)
        ->set('percentage', 10)
        ->call('addAllocation')
        ->assertHasErrors(['targetPlaceId']);

    expect($target->fresh()->incomingAllocationsCount())->toBe(Place::MAX_INCOMING_ALLOCATIONS);
});

test('availableTargets excludes places at the incoming cap', function () {
    $full = Place::factory()->create([
        'group_id' => $this->group->id,
        'user_id' => User::factory()->create()->id,
    ]);
    fillTargetIncoming($full, Place::MAX_INCOMING_ALLOCATIONS);

    $open = Place::factory()->create([
        'group_id' => $this->group->id,
        'user_id' => User::factory()->create()->id,
    ]);
    fillTargetIncoming($open, Place::MAX_INCOMING_ALLOCATIONS - 1);

    $targets = Livewire::test('pages::places.manage-allocations', [
        'group' => $this->group,
        'place' => $this->source,
    ])->get('availableTargets');

    expect($targets->pluck('id')->all())
        ->toContain($open->id)
        ->not->toContain($full->id);
});

test('editing an existing allocation is not blocked by the incoming cap', function () {
    $target = Place::factory()->create([
        'group_id' => $this->group->id,
        'user_id' => User::factory()->create()->id,
    ]);
    fillTargetIncoming($target, Place::MAX_INCOMING_ALLOCATIONS - 1);

    $allocation = Allocation::create([
        'source_place_id' => $this->source->id,
        'target_place_id' => $target->id,
        'percentage' => 10,
    ]);

    expect($target->fresh()->incomingAllocationsCount())->toBe(Place::MAX_INCOMING_ALLOCATIONS);

    Livewire::test('pages::places.manage-allocations', [
        'group' => $this->group,
        'place' => $this->source,
    ])
        ->call('startEdit', $allocation->id)
        ->set('editPercentage', 25)
        ->call('saveEdit')
        ->assertHasNoErrors();

    expect($allocation->fresh()->percentage)->toBe(25);
});
