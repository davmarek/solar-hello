<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * Seeds the integration_providers table by delegating to the
 * `solar:sync-providers` command (single source of truth).
 */
class IntegrationProviderSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('solar:sync-providers');
    }
}
