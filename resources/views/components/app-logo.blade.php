@props ([
    'sidebar' => false,
])

@if ($sidebar)
    <flux:sidebar.brand name="Solar Hello" {{ $attributes }}>
        <x-slot
            name="logo"
            class="flex aspect-square size-8 items-center justify-center"
        >
            <x-app-logo-icon
                class="size-7 text-emerald-600 dark:text-emerald-400"
            />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Solar Hello" {{ $attributes }}>
        <x-slot
            name="logo"
            class="flex aspect-square size-8 items-center justify-center"
        >
            <x-app-logo-icon
                class="size-7 text-emerald-600 dark:text-emerald-400"
            />
        </x-slot>
    </flux:brand>
@endif
