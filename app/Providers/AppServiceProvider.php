<?php

namespace App\Providers;

use App\Solar\Providers\MockShellyProvider;
use App\Solar\Providers\SolaxProvider;
use App\Solar\Services\ProviderRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ProviderRegistry::class);

        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureSolarProviders();
        Model::preventLazyLoading(! app()->isProduction());
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    /**
     * @throws BindingResolutionException
     */
    private function configureSolarProviders(): void
    {
        // ── Solar providers ────────────────────────────────────────────────────
        // Add new providers here and re-run IntegrationProviderSeeder.
        // Nothing else in the codebase needs to change.
        $registry = $this->app->make(ProviderRegistry::class);
        $registry->register(new SolaxProvider);
        $registry->register(new MockShellyProvider);
    }
}
