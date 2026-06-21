<div class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center">
        <flux:button
            :href="route('places.show',['group'=>$group, 'place'=>$place])"
            icon="arrow-left"
            square
            variant="subtle"
            size="sm"
            class="p-0"
        />
        <div>
            <flux:subheading class="mb-0">Add integration for</flux:subheading>
            <h1 class="text-3xl font-bold">{{ $place->name }}</h1>
        </div>
    </div>

    {{-- -- Provider selector ------------------------------------------------ --}}
    <flux:field>
        <flux:label>Provider</flux:label>
        <flux:description
            >Choose the hardware brand you want to connect.</flux:description
        >

        <flux:select
            wire:model.live="selectedProvider"
            placeholder="Select a provider…"
        >
            @foreach ($this->availableProviders as $provider)
                <flux:select.option wire:key="provider-{{ $provider->name }}" value="{{ $provider->name }}">
                    {{ $provider->display_name }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:error name="selectedProvider" />
    </flux:field>

    {{-- -- Per-provider credential form ------------------------------------- --}}
    @if ($selectedProvider)
        <flux:separator />
        <flux:fieldset>
            <div class="space-y-6">
                <flux:input
                    wire:model="label"
                    label="Label"
                    description="Optional name to identify this integration, e.g. South Roof."
                    placeholder="e.g. South Roof Panels"
                />

                @foreach ($this->credentialFields as $field)
                    @if ($field->secret)
                        <flux:field wire:key="cred-{{ $field->name }}">
                            <flux:label>
                                {{ $field->label }}
                                @if (!$field->required)
                                    <flux:badge size="sm" inset="top bottom"
                                        >Optional</flux:badge
                                    >
                                @endif
                            </flux:label>

                            @if ($field->help)
                                <flux:description
                                    >{{ $field->help }}</flux:description
                                >
                            @endif

                            <flux:input
                                type="password"
                                wire:model="credentials.{{ $field->name }}"
                                placeholder="{{ $field->placeholder }}"
                                viewable
                                autocomplete="off"
                            />

                            <flux:error name="credentials.{{ $field->name }}" />
                        </flux:field>
                    @else
                        <flux:input
                            wire:key="cred-{{ $field->name }}"
                            wire:model="credentials.{{ $field->name }}"
                            label="{{ $field->label }}"
                            description="{{ $field->help }}"
                            placeholder="{{ $field->placeholder }}"
                        >
                            @if (!$field->required)
                                <x-slot name="iconTrailing">
                                    <flux:badge size="sm" class="mr-1"
                                        >Optional</flux:badge
                                    >
                                </x-slot>
                            @endif
                        </flux:input>
                        <flux:error name="credentials.{{ $field->name }}" />
                    @endif
                @endforeach

                @error ('credentials')
                    <flux:callout variant="danger" icon="exclamation-triangle">
                        <flux:callout.text>{{ $message }}</flux:callout.text>
                    </flux:callout>
                @enderror
            </div>
        </flux:fieldset>
        {{-- -- Form actions ------------------------------------------------ --}}
        <div class="flex justify-end gap-3">
            <flux:button
                :href="route('places.show', ['group' => $group, 'place' => $place])"
                variant="ghost"
                >Cancel
            </flux:button>

            <flux:button
                wire:click="save"
                wire:loading.attr="disabled"
                wire:target="save"
                variant="primary"
                icon="bolt"
            >
                <span wire:loading.remove wire:target="save"
                    >Add Integration</span
                >
                <span wire:loading wire:target="save">Connecting…</span>
            </flux:button>
        </div>

    @endif
</div>
