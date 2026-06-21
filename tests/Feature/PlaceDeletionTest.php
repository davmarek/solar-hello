<?php

use App\Models\EnergyReading;
use App\Models\Group;
use App\Models\Integration;
use App\Models\IntegrationProvider;
use App\Models\Place;
use App\Models\User;
use Database\Seeders\IntegrationProviderSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(IntegrationProviderSeeder::class);

    $this->user = User::factory()->create();
    $this->group = Group::factory()->create(['owner_id' => $this->user->id]);
    $this->place = Place::factory()->create([
        'group_id' => $this->group->id,
        'user_id' => $this->user->id,
    ]);
    $this->provider = IntegrationProvider::where('name', 'mock_shelly')->firstOrFail();

    $this->actingAs($this->user);
});

test('deletePlace deletes the place and its integration', function () {
    $integration = Integration::create([
        'place_id' => $this->place->id,
        'provider_id' => $this->provider->id,
        'label' => 'Roof',
        'credentials' => ['device_id' => 'd', 'api_key' => 'k'],
        'status' => 'pending',
    ]);

    $reading = EnergyReading::create([
        'integration_id' => $integration->id,
        'place_id' => $this->place->id,
        'group_id' => $this->group->id,
        'recorded_at' => now(),
        'grid_power_w' => 100,
    ]);

    Livewire::test('pages::places.show', [
        'group' => $this->group,
        'place' => $this->place,
    ])
        ->call('deletePlace')
        ->assertHasNoErrors()
        ->assertRedirect(route('groups.show', ['group' => $this->group]));

    expect(Place::find($this->place->id))->toBeNull();
    expect(Integration::find($integration->id))->toBeNull();
    expect(EnergyReading::find($reading->id))->toBeNull();
});

test('deletePlace works for a place without an integration', function () {
    Livewire::test('pages::places.show', [
        'group' => $this->group,
        'place' => $this->place,
    ])
        ->call('deletePlace')
        ->assertHasNoErrors()
        ->assertRedirect(route('groups.show', ['group' => $this->group]));

    expect(Place::find($this->place->id))->toBeNull();
});

test('only the owner may delete the place', function () {
    $other = User::factory()->create();

    expect($this->user->can('delete', $this->place))->toBeTrue();
    expect($other->can('delete', $this->place))->toBeFalse();
});
