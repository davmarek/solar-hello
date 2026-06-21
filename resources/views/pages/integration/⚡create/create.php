<?php

use App\Models\Group;
use App\Models\Integration;
use App\Models\IntegrationProvider;
use App\Models\Place;
use App\Solar\Services\ProviderRegistry;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Group $group;

    public Place $place;

    public string $selectedProvider = '';

    public string $label = '';

    public array $credentials = [];

    /**
     * @return Collection<IntegrationProvider>
     */
    #[Computed]
    public function availableProviders(): Collection
    {
        return IntegrationProvider::where('is_active', true)->get();
    }

    public function mount(Group $group, Place $place): void
    {
        $this->authorize('manage-integrations', $place);

        if ($place->hasIntegration()) {
            $this->redirectRoute('places.show', ['group' => $group, 'place' => $place]);

            return;
        }

        $this->group = $group;
        $this->place = $place;
    }

    public function updatedSelectedProvider(string $value): void
    {
        $this->credentials = [];

        if (! $value) {
            return;
        }

        // Pre-populate credentials keys so Livewire wire:model bindings resolve
        foreach ($this->credentialFields as $field) {
            $this->credentials[$field->name] = '';
        }
    }

    public function save(): void
    {
        $this->authorize('manage-integrations', $this->place);

        $this->validate();

        if ($this->place->hasIntegration()) {
            $this->addError(
                'selectedProvider',
                'This place already has an integration. Remove it first to add a different one.'
            );

            return;
        }

        if (! $this->group->hasMember(auth()->user())) {
            $this->addError('selectedProvider', 'You are not a member of this group.');

            return;
        }

        $registry = app(ProviderRegistry::class);

        if (! $registry->has($this->selectedProvider)) {
            $this->addError('selectedProvider', 'Selected provider is not available.');

            return;
        }

        $providerModel = IntegrationProvider::where('name', $this->selectedProvider)
            ->where('is_active', true)
            ->firstOrFail();

        $provider = $registry->get($this->selectedProvider);

        try {
            $provider->validateCredentials($this->credentials);
        } catch (RuntimeException $e) {
            $this->addError('credentials', $e->getMessage());

            return;
        }

        Integration::create([
            'place_id' => $this->place->id,
            'provider_id' => $providerModel->id,
            'label' => $this->label ?: $providerModel->display_name,
            'credentials' => $this->credentials,
        ]);

        $this->redirectRoute('places.show', ['group' => $this->group, 'place' => $this->place]);
    }

    #[Computed]
    public function credentialFields(): array
    {
        if (! $this->selectedProvider) {
            return [];
        }

        $registry = app(ProviderRegistry::class);

        if (! $registry->has($this->selectedProvider)) {
            return [];
        }

        return $registry->get($this->selectedProvider)->getCredentialsSchema();
    }

    public function rules(): array
    {
        $rules = [
            'selectedProvider' => 'required|string|exists:integration_providers,name',
            'label' => 'nullable|string|max:100',
        ];

        foreach ($this->credentialFields as $field) {
            $rules["credentials.{$field->name}"] = $field->required
                ? 'required|string'
                : 'nullable|string';
        }

        return $rules;
    }
};
