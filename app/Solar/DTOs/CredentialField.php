<?php

namespace App\Solar\DTOs;

/**
 * Describes a single credential input field for a provider's setup form.
 *
 * Providers return an array of these from getCredentialsSchema().
 * Livewire renders the form dynamically from this definition.
 * The seeder serializes these to JSON for storage in integration_providers.config_schema.
 *
 * Adding a field here automatically makes it available everywhere:
 * the setup form, validation rules, and any future API serialization.
 */
readonly class CredentialField
{
    public function __construct(
        public string $name,                    // Key stored in the credentials JSON blob
        public string $label,                   // Human-readable label shown in the form
        public string $type = 'text',    // HTML input type: 'text', 'password', 'number', etc.
        public string $placeholder = '',        // Input placeholder text
        public string $help = '',        // Helper text shown below the field
        public bool $required = true,      // Whether the field is required
        public bool $secret = false,     // Render as password input + mask in logs/UI
    ) {}

    /**
     * Serialize to array for JSON storage in integration_providers.config_schema
     * and for passing to Livewire/frontend.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->secret ? 'password' : $this->type,
            'placeholder' => $this->placeholder,
            'help' => $this->help,
            'required' => $this->required,
            'secret' => $this->secret,
        ];
    }

    /**
     * Reconstruct a CredentialField from a stored array (e.g. from config_schema JSON).
     *
     * @param  array<int,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            label: $data['label'],
            type: $data['type'] ?? 'text',
            placeholder: $data['placeholder'] ?? '',
            help: $data['help'] ?? '',
            required: $data['required'] ?? true,
            secret: $data['secret'] ?? false,
        );
    }
}
