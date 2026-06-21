<?php

namespace App\Solar\Providers;

use App\Models\Integration;
use App\Solar\Contracts\SolarProviderInterface;
use App\Solar\DTOs\CredentialField;
use App\Solar\DTOs\EnergyReadingDTO;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Uri;
use RuntimeException;

/**
 * Mock Shelly API provider — pull-based, measures production.
 *
 * API docs: https://mock-shelly-863176168765.europe-west1.run.app
 */
class MockShellyProvider implements SolarProviderInterface
{
    private const string API_URL = 'https://mock-shelly-863176168765.europe-west1.run.app/api/v1/meter';

    public function getName(): string
    {
        return 'mock_shelly';
    }

    public function getDisplayName(): string
    {
        return 'Mock Shelly';
    }

    /** @return CredentialField[] */
    public function getCredentialsSchema(): array
    {
        return [
            new CredentialField(
                name: 'device_id',
                label: 'Mock Device ID',
                placeholder: 'eg. device-home-01',
                help: 'You can find this in the API description',
                required: true,
                secret: false,
            ),
            new CredentialField(
                name: 'api_key',
                label: 'Mock API key',
                placeholder: 'e.g. demo-key-abc123',
                help: 'You can find this in the API description',
                required: true,
                secret: true,
            ),
        ];
    }

    public function validateCredentials(array $credentials): bool
    {
        $response = $this->callApi($credentials['api_key'], $credentials['device_id']);

        if (! $response->successful()) {
            throw new RuntimeException('Could not reach the Mock Shelly API. Please try again.');
        }

        $body = $response->json();

        if (isset($body['error'])) {
            throw new RuntimeException('Mock Shelly rejected the credentials: '.$body['error']);
        }

        return true;
    }

    public function fetchReadings(Integration $integration): Collection
    {
        $credentials = $integration->credentials;
        $response = $this->callApi($credentials['api_key'], $credentials['device_id']);

        if (! $response->successful()) {
            throw new RuntimeException("Mock Shelly API request failed with HTTP {$response->status()}");
        }

        $body = $response->json();

        if (isset($body['error'])) {
            throw new RuntimeException('Mock Shelly API error: '.$body['error']);
        }

        $result = $body ?? [];

        return collect([
            new EnergyReadingDTO(
                inverterPowerW: 0.0,
                gridPowerW: (float) -($result['total_act_power_w'] ?? 0),
                batteryPowerW: 0.0,
                batterySocPct: 0.0,
                energyTodayKwh: 0.0,
                energyTotalKwh: 0.0,
                recordedAt: isset($result['timestamp'])
                    ? Carbon::parse($result['timestamp'])->floorMinute(5)
                    : Carbon::now()->floorMinute(5),
                rawPayload: $result,
            ),
        ]);
    }

    /**
     * @throws ConnectionException
     */
    private function callApi(string $apiKey, string $deviceId): Response
    {
        $uri = Uri::of(self::API_URL)->withQuery([
            'api_key' => $apiKey,
            'device_id' => $deviceId,
        ]);

        Log::info('Calling Mock Shelly API', ['device_id' => $deviceId]);

        return Http::connectTimeout(5)->timeout(15)->get($uri);
    }
}
