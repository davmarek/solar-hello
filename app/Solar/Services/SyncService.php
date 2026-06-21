<?php

namespace App\Solar\Services;

use App\Jobs\CalculateAvailablePowerJob;
use App\Models\EnergyReading;
use App\Models\Integration;
use App\Solar\DTOs\EnergyReadingDTO;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

readonly class SyncService
{
    public function __construct(
        private ProviderRegistry $registry,
    ) {}

    /**
     * Sync a single pull-based integration.
     * Called by SyncIntegrationJob — do not call directly from controllers.
     *
     * @throws Throwable
     */
    public function sync(Integration $integration): void
    {
        try {
            $providerModel = $integration->provider;

            if (! $providerModel->is_active) {
                Log::info("Skipping sync for integration #{$integration->id}: provider '{$providerModel->name}' is inactive.");

                return;
            }

            // Fetch the reading from integrations provider
            $provider = $this->registry->get($providerModel->name);
            Log::debug("Provider found in registry for integration #{$integration->id}", [
                'provider' => $provider->getName(),
            ]);
            $readings = $provider->fetchReadings($integration);
            Log::debug("Reading fetched for integration #{$integration->id}", [
                'readings' => $readings,
            ]);

            // Save the readings
            $anyNewReadings = false;
            foreach ($readings as $dto) {
                Log::debug('Will try to persist reading', [
                    'dto' => $dto,
                ]);
                $wasCreated = $this->persist($integration, $dto);
                Log::debug('Hopefully reading was created', [
                    'wasCreated' => $wasCreated,
                ]);
                $anyNewReadings = $anyNewReadings || $wasCreated;
            }

            Log::debug('Any new readings?', [
                'anyNewReadings' => $anyNewReadings,
            ]);
            $integration->update([
                'last_synced_at' => now(),
                'last_error' => null,
            ]);

            // Recalculate available power for every place in the group if any new readings fetched
            if ($anyNewReadings) {
                Log::debug('New readings created - will dispatch jobs for each place in the group.', [
                    'integration_id' => $integration->id,
                    'place_id' => $integration->place_id,
                ]);
                $integration->place->group->places()
                    ->pluck('id')
                    ->each(fn ($placeId) => CalculateAvailablePowerJob::dispatch($placeId));
            }

        } catch (InvalidArgumentException $e) {
            Log::error($e->getMessage());
        } catch (Throwable $e) {
            Log::error("Energy sync failed for integration #{$integration->id}", [
                'provider' => $integration->provider->name,
                'place_id' => $integration->place_id,
                'error' => $e->getMessage(),
            ]);

            $integration->update([
                'last_error' => $e->getMessage(),
            ]);

            throw $e; // Re-throw so the job can retry with backoff
        }
    }

    // -------------------------------------------------------------------------

    private function persist(Integration $integration, EnergyReadingDTO $dto): bool
    {
        $integration->loadMissing('place');

        $reading = EnergyReading::firstOrCreate([
            'integration_id' => $integration->id,
            'recorded_at' => $dto->recordedAt,
        ], [
            'place_id' => $integration->place_id,              // Denormalized for fast place queries
            'group_id' => $integration->place->group_id,       // Denormalized for fast group queries
            'inverter_power_w' => $dto->inverterPowerW,
            'grid_power_w' => $dto->gridPowerW,
            'battery_power_w' => $dto->batteryPowerW,
            'battery_soc_pct' => $dto->batterySocPct,
            'energy_today_kwh' => $dto->energyTodayKwh,
            'energy_total_kwh' => $dto->energyTotalKwh,
            'raw_payload' => $dto->rawPayload,
        ]);

        return $reading->wasRecentlyCreated;
    }
}
