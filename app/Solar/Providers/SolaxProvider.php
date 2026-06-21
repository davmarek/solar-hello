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
 * Solax Cloud API provider — pull-based, measures production.
 *
 * API docs: https://www.solaxcloud.com/phoebus/resource/files/userGuide/Solax_API_for_End-user_V2.0.pdf
 */
class SolaxProvider implements SolarProviderInterface
{
    private const string API_URL = 'https://www.solaxcloud.com/proxyApp/proxy/api/getRealtimeInfo.do';

    public function getName(): string
    {
        return 'solax';
    }

    public function getDisplayName(): string
    {
        return 'Solax Cloud';
    }

    /** @return CredentialField[] */
    public function getCredentialsSchema(): array
    {
        return [
            new CredentialField(
                name: 'serial_number',
                label: 'Inverter Serial Number',
                placeholder: 'e.g. SP1234567890',
                help: 'Found on the sticker on your inverter or in the Solax app.',
                required: true,
                secret: false,
            ),
            new CredentialField(
                name: 'token_id',
                label: 'API Token ID',
                placeholder: 'Your Solax Cloud token',
                help: 'Generate this in SolaxCloud → Account → API Token.',
                required: true,
                secret: true,
            ),
        ];
    }

    public function validateCredentials(array $credentials): bool
    {
        $response = $this->callApi($credentials['token_id'], $credentials['serial_number']);

        if (! $response->successful()) {
            throw new RuntimeException('Could not reach the Solax API. Please try again.');
        }

        $body = $response->json();

        if (($body['success'] ?? false) === false) {
            throw new RuntimeException('Solax rejected the credentials: '.($body['exception'] ?? 'Unknown error'));
        }

        return true;
    }

    public function fetchReadings(Integration $integration): Collection
    {
        $credentials = $integration->credentials;
        $response = $this->callApi($credentials['token_id'], $credentials['serial_number']);

        if (! $response->successful()) {
            throw new RuntimeException("Solax API request failed with HTTP {$response->status()}");
        }

        $body = $response->json();

        if (($body['success'] ?? false) === false) {
            throw new RuntimeException('Solax API error: '.($body['exception'] ?? 'Unknown error'));
        }

        $result = $body['result'] ?? [];

        return collect([
            new EnergyReadingDTO(
                inverterPowerW: (float) ($result['acpower'] ?? 0),
                gridPowerW: (float) ($result['feedinpower'] ?? 0),
                batteryPowerW: (float) ($result['batPower'] ?? 0),
                batterySocPct: isset($result['soc']) ? (float) ($result['soc'] ?? 0) : null,
                energyTodayKwh: (float) ($result['yieldtoday'] ?? 0),
                energyTotalKwh: (float) ($result['yieldtotal'] ?? 0),
                recordedAt: isset($result['uploadTime'])
                    ? Carbon::createFromFormat('Y-m-d H:i:s', $result['uploadTime'])->floorMinute(5)
                    : Carbon::now()->floorMinute(5),
                rawPayload: $result,
            ),
        ]);
    }

    /**
     * @throws ConnectionException
     */
    private function callApi(string $tokenId, string $serialNumber): Response
    {
        $uri = Uri::of(self::API_URL)->withQuery([
            'tokenId' => $tokenId,
            'sn' => $serialNumber,
        ]);

        Log::info('Calling Solax API', ['sn' => $serialNumber]);

        return Http::connectTimeout(5)->timeout(15)->get($uri);
    }
}
