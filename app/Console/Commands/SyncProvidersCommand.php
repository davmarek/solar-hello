<?php

namespace App\Console\Commands;

use App\Models\IntegrationProvider;
use App\Solar\Services\ProviderRegistry;
use Illuminate\Console\Command;

class SyncProvidersCommand extends Command
{
    protected $signature = 'solar:sync-providers';

    protected $description = 'Sync registered solar providers into the integration_providers table. Run after each deploy.';

    public function handle(ProviderRegistry $registry): int
    {
        $registeredNames = [];

        foreach ($registry->all() as $provider) {
            $schema = array_map(
                fn ($field) => $field->toArray(),
                $provider->getCredentialsSchema()
            );

            IntegrationProvider::updateOrCreate(
                ['name' => $provider->getName()],
                [
                    'display_name' => $provider->getDisplayName(),
                    'handler_class' => get_class($provider),
                    'config_schema' => $schema,
                    'is_active' => true,
                ],
            );

            $registeredNames[] = $provider->getName();
            $this->line("  ✓ {$provider->getName()}");
        }

        // Deactivate DB rows that no longer have a registered handler.
        $deactivated = IntegrationProvider::whereNotIn('name', $registeredNames)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        if ($deactivated > 0) {
            $this->warn("Deactivated {$deactivated} provider(s) with no registered handler.");
        }

        $this->info('Provider sync complete.');

        return self::SUCCESS;
    }
}
