<?php

use App\Models\Group;
use App\Models\Integration;
use App\Models\IntegrationProvider;
use App\Models\Place;
use App\Models\User;
use Database\Seeders\IntegrationProviderSeeder;
use Illuminate\Support\Facades\Http;
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

test('the create page redirects to the place when an integration already exists', function () {
    Integration::create([
        'place_id' => $this->place->id,
        'provider_id' => $this->provider->id,
        'label' => 'Existing',
        'credentials' => ['device_id' => 'd', 'api_key' => 'k'],
        'status' => 'pending',
    ]);

    $response = $this->get(route('integrations.create', [
        'group' => $this->group,
        'place' => $this->place,
    ]));

    $response->assertRedirect(route('places.show', [
        'group' => $this->group,
        'place' => $this->place,
    ]));
});

test('save() creates the integration and redirects to the place', function () {
    Http::fake([
        'mock-shelly-863176168765.europe-west1.run.app/*' => Http::response(['power' => 42], 200),
    ]);

    $this->group->users()->attach($this->user);

    Livewire::test('pages::integration.create', [
        'group' => $this->group,
        'place' => $this->place,
    ])
        ->set('selectedProvider', 'mock_shelly')
        ->set('credentials.device_id', 'dev-1')
        ->set('credentials.api_key', 'key-1')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('places.show', [
            'group' => $this->group,
            'place' => $this->place,
        ]));

    expect($this->place->integration()->count())->toBe(1);
});

test('save() blocks a second integration added after mount', function () {
    // Mount on an empty place and fill the form.
    $component = Livewire::test('pages::integration.create', [
        'group' => $this->group,
        'place' => $this->place,
    ])
        ->set('selectedProvider', 'mock_shelly')
        ->set('credentials.device_id', 'dev-1')
        ->set('credentials.api_key', 'key-1');

    // Race: an integration appears between mount and save.
    Integration::create([
        'place_id' => $this->place->id,
        'provider_id' => $this->provider->id,
        'label' => 'Race',
        'credentials' => ['device_id' => 'd', 'api_key' => 'k'],
        'status' => 'pending',
    ]);

    $component->call('save')->assertHasErrors('selectedProvider');

    expect($this->place->integration()->count())->toBe(1);
});

test('a place may have one integration', function () {
    $integration = Integration::create([
        'place_id' => $this->place->id,
        'provider_id' => $this->provider->id,
        'label' => 'Roof',
        'credentials' => ['device_id' => 'd', 'api_key' => 'k'],
        'status' => 'pending',
    ]);

    expect($this->place->fresh()->integration->is($integration))->toBeTrue();
    expect($this->place->fresh()->hasIntegration())->toBeTrue();
});
