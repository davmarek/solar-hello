<?php

use App\Models\IntegrationProvider;

test('solar:sync-providers upserts each registered provider as active', function () {
    $this->artisan('solar:sync-providers')->assertSuccessful();

    expect(IntegrationProvider::where('name', 'solax')->where('is_active', true)->exists())->toBeTrue();
    expect(IntegrationProvider::where('name', 'mock_shelly')->where('is_active', true)->exists())->toBeTrue();
});

test('solar:sync-providers deactivates rows whose handler is no longer registered', function () {
    IntegrationProvider::create([
        'name' => 'orphaned_provider',
        'display_name' => 'Orphan',
        'handler_class' => 'App\\Nope',
        'config_schema' => [],
        'is_active' => true,
    ]);

    $this->artisan('solar:sync-providers')->assertSuccessful();

    expect(IntegrationProvider::where('name', 'orphaned_provider')->value('is_active'))->toBeFalse();
});

test('solar:sync-providers preserves created_at on update', function () {
    $this->artisan('solar:sync-providers')->assertSuccessful();
    $original = IntegrationProvider::where('name', 'solax')->firstOrFail();

    $this->travel(1)->minutes();
    $this->artisan('solar:sync-providers')->assertSuccessful();

    $after = IntegrationProvider::where('name', 'solax')->firstOrFail();
    expect($after->created_at->equalTo($original->created_at))->toBeTrue();
});
