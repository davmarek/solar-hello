<?php

namespace App\Console\Commands;

use App\Jobs\SyncIntegrationJob;
use App\Models\Integration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncIntegrationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'solar:sync
                            {--integration= : Sync a specific integration by ID (useful for debugging)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch sync jobs for all active pull-based integrations';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // Prepare query for all integrations
        $query = Integration::with('provider');

        // If integration_id is passed to command -> filter with that ID
        if ($id = $this->option('integration')) {
            $query->where('id', $id);
        }

        // Run the query
        $integrations = $query->get();

        if ($integrations->isEmpty()) {
            $this->info('No active pull integrations to sync.');

            return;
        }

        Log::info('Finished getting integrations - now will dispatch jobs for each integration.');

        // Run the SyncIntegrationJob for each integration found
        foreach ($integrations as $integration) {
            Log::info("Dispatching integration #{$integration->id} - {$integration->label}", [
                'id' => $integration->id,
                'place_id' => $integration->place_id,
                'label' => $integration->label,
            ]);

            SyncIntegrationJob::dispatch($integration);
            $this->line("  Queued #{$integration->id} — {$integration->label} ({$integration->provider->display_name})");
        }

        $this->info("Dispatched {$integrations->count()} sync job(s).");
    }
}
