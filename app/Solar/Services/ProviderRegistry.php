<?php

namespace App\Solar\Services;

use App\Solar\Contracts\SolarProviderInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Singleton registry for all solar providers.
 *
 * To add a new provider:
 *   1. Create a class implementing SolarProviderInterface.
 *   2. Register it in AppServiceProvider::boot() via $registry->register(new YourProvider()).
 *   3. Re-run: php artisan db:seed --class=IntegrationProviderSeeder
 *
 * Nothing else needs to change anywhere in the codebase.
 */
class ProviderRegistry
{
    /** @var array<string, SolarProviderInterface> */
    private array $providers = [];

    public function register(SolarProviderInterface $provider): void
    {
        $this->providers[$provider->getName()] = $provider;
    }

    public function get(string $name): SolarProviderInterface
    {
        if (! isset($this->providers[$name])) {
            throw new InvalidArgumentException("Solar provider [{$name}] is not registered.");
        }

        return $this->providers[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->providers[$name]);
    }

    /** @return Collection<SolarProviderInterface> */
    public function all(): Collection
    {
        return collect(array_values($this->providers));
    }
}
