<?php

namespace Database\Seeders;

use App\Models\Allocation;
use App\Models\Group;
use App\Models\Integration;
use App\Models\Place;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([IntegrationProviderSeeder::class]);

        $david = User::factory()->create([
            'name' => 'David Marek',
            'email' => 'david@test.com',
        ]);

        $petr = User::factory()->create([
            'name' => 'Petr Nosek',
            'email' => 'petr@test.com',
        ]);

        User::factory()->create([
            'name' => 'Dominik Machalik',
            'email' => 'domi@test.com',
        ]);

        $group = Group::factory()->hasAttached([$david, $petr])->create([
            'name' => 'Wake Family',
            'owner_id' => $david->id,
        ]);

        $place_producer = Place::factory()->create([
            'group_id' => $group->id,
            'user_id' => $david->id,
            'name' => 'Main Street 42',
        ]);

        $place_consumer = Place::factory()->create([
            'group_id' => $group->id,
            'user_id' => $david->id,
            'name' => 'Cabin by the lake',
            'description' => null,
        ]);

        // You can add an integration in the seeder like this
        /* Integration::factory()->create([
            'place_id' => $place_producer->id,
            'provider_id' => 1,
            'label' => 'Solar Panels',
            'credentials' => [
                'serial_number' => '',
                'token_id' => '',
            ],
        ]); */

        Integration::factory()->create([
            'place_id' => $place_consumer->id,
            'provider_id' => 2,
            'label' => 'Mock Shelly',
            'credentials' => [
                'api_key' => 'demo-key-abc123',
                'device_id' => 'device-home-01',
            ],
        ]);

        Allocation::factory()->create([
            'source_place_id' => $place_producer->id,
            'target_place_id' => $place_consumer->id,
            'percentage' => 100,
        ]);

    }
}
