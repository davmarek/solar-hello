<?php

namespace App\Solar\Contracts;

use App\Models\Integration;
use App\Solar\DTOs\CredentialField;
use App\Solar\DTOs\EnergyReadingDTO;
use Illuminate\Support\Collection;

interface SolarProviderInterface
{
    /**
     * Unique machine-readable identifier, e.g. "solax", "shelly".
     * Must match the `name` column in integration_providers.
     */
    public function getName(): string;

    /**
     * Human-readable label shown in the UI, e.g. "Solax Cloud".
     */
    public function getDisplayName(): string;

    /**
     * Describes the credential fields shown in the integration setup form.
     * Livewire renders these dynamically — no frontend changes needed for new providers.
     * The seeder serializes these via toArray() into integration_providers.config_schema.
     *
     * @return CredentialField[]
     */
    public function getCredentialsSchema(): array;

    /**
     * Validate credentials before saving — e.g. make a test API call.
     * Throw \RuntimeException with a user-friendly message on failure.
     * Return true on success.
     */
    public function validateCredentials(array $credentials): bool;

    /**
     * Fetch the latest reading(s) from the external API.
     *
     * @return Collection<EnergyReadingDTO>
     */
    public function fetchReadings(Integration $integration): Collection;
}
