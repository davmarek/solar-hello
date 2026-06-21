@use (Illuminate\Support\Carbon)
@php
    /** @var App\Models\Place[] $places */
    /** @var App\Models\Invitation[] $invitations */
    /** @var App\Models\User[] $users */
@endphp

<div class="space-y-6">
    <x-flash-message-callout />

    {{-- Top bar with group name and action buttons --}}
    <div class="flex flex-col items-center justify-between gap-6 md:flex-row">
        <div>
            <flux:subheading>Group</flux:subheading>
            <h1 class="text-3xl font-bold">{{ $group->name }}</h1>
        </div>

        {{-- Action Buttons --}}
        <div
            class="flex flex-wrap items-center justify-center gap-3 md:justify-end"
        >
            <flux:modal.trigger name="place-modal">
                <flux:button variant="primary" size="sm" icon="plus">
                    Add Place
                </flux:button>
            </flux:modal.trigger>

            @can ('manage-members', $group)
                <flux:separator vertical class="hidden md:block" />
                <flux:modal.trigger name="invitation-modal">
                    <flux:button size="sm" icon="user-plus" wire:navigate>
                        Invite
                    </flux:button>
                </flux:modal.trigger>
                <flux:button
                    size="sm"
                    :href="route('groups.members', $group)"
                    icon="users"
                >
                    Manage Members
                </flux:button>
            @endcan
        </div>
    </div>

    <flux:heading size="xl">Your Places</flux:heading>
    @if ($places->count() > 0)
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            @foreach ($places as $place)
                <a
                    href="{{ route('places.show', ['group' => $group, 'place' => $place]) }}"
                    aria-label="Place detail"
                    wire:navigate
                >
                    <flux:card
                        size="sm"
                        class="flex h-full flex-col justify-between p-6 hover:bg-zinc-50 dark:hover:bg-zinc-700"
                    >
                        <flux:heading
                            size="xl"
                            class="mb-4 flex items-center gap-6 text-2xl"
                            >{{ $place->name }}
                            <flux:icon
                                name="arrow-up-right"
                                class="ml-auto text-zinc-400"
                                variant="mini"
                            />
                        </flux:heading>
                        <flux:text
                            class="mb-4 text-gray-500"
                            >{{ Str::limit($place->description, 50) }}</flux:text
                        >

                        <div class="grid grid-cols-2 gap-4">
                            @if ($place->hasAllocationIn() && $place->latestSnapshot)
                                <flux:card
                                    class="bg-green-100 dark:bg-green-800 dark:saturate-70"
                                >
                                    <flux:text>Available power</flux:text>
                                    <flux:heading size="lg"
                                        >{{ $place->latestSnapshot->allocated_w }} W
                                    </flux:heading>
                                </flux:card>
                            @endif

                            @if ($place->latestReading)
                                <flux:card
                                    class="bg-yellow-100 dark:bg-yellow-500/50 dark:saturate-80"
                                >
                                    <flux:text>Grid Exchange</flux:text>
                                    <flux:heading size="lg"
                                        >{{ $place->latestReading->grid_power_w }} W</flux:heading
                                    >
                                </flux:card>
                            @endif
                        </div>
                    </flux:card>
                </a>
            @endforeach
        </div>
    @else
        <div
            class="flex items-center gap-2 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700"
        >
            <flux:icon.map-pin class="text-zinc-600 dark:text-zinc-300" />
            <flux:subheading class="grow"
                >You don't have any places yet</flux:subheading
            >

            <flux:modal.trigger name="place-modal" class="ml-4">
                <flux:button variant="primary" size="sm" icon="plus">
                    Add Place
                </flux:button>
            </flux:modal.trigger>
        </div>
    @endif

    {{-- Members table --}}
    <flux:heading size="xl" class="mt-8">Group Members</flux:heading>
    @can ('manage-members', $group)
        <flux:button
            size="sm"
            class="mb-4"
            :href="route('groups.members', $group)"
            icon="users"
            wire:navigate
        >
            Manage Members
        </flux:button>
    @endcan
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Member</flux:table.column>
            <flux:table.column>Email</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($group->users as $user)
                <flux:table.row>
                    {{-- Icon, Full Name and Owener badge --}}
                    <flux:table.cell
                        class="flex items-center gap-3"
                        variant="strong"
                    >
                        <flux:avatar
                            size="sm"
                            initials="{{ $user->initials() }}"
                        ></flux:avatar>
                        {{ $user->name }}
                        @if ($user->id === $group->owner_id)
                            <flux:badge
                                color="yellow"
                                size="sm"
                                inset="top bottom"
                                >Owner
                            </flux:badge>
                        @endif
                    </flux:table.cell>

                    {{-- Email --}}
                    <flux:table.cell>{{ $user->email }}</flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    {{-- Invitations List --}}
    @can ('manage-members', $group)
        <flux:card>
            <flux:heading size="lg" class="mb-4"
                >Pending Invitations
            </flux:heading>
            @if ($group->invitations->isEmpty())
                <p class="text-gray-500 italic">No pending invitations.</p>
            @else
                <flux:table>
                    <flux:table.rows>
                        @foreach ($group->invitations as $invitation)
                            <flux:table.row>
                                <flux:table.cell>
                                    {{ $invitation->email }}
                                </flux:table.cell>

                                <flux:table.cell align="end">
                                    <flux:button
                                        size="sm"
                                        icon="trash"
                                        variant="primary"
                                        color="red"
                                        wire:click="deleteInvitation({{ $invitation->id }})"
                                        wire:confirm="Are you sure you want to delete this invitation?"
                                    >
                                    </flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </flux:card>
    @endcan

    {{-- Create place modal --}}
    <flux:modal name="place-modal" class="md:w-96">
        <form wire:submit="createPlace" class="space-y-6">
            <flux:heading size="lg">Create a place</flux:heading>
            <flux:subheading
                >Defines a place that can consume power and can have
                integrations that collect data.</flux:subheading
            >
            <flux:input
                wire:model.live.debounce="placeForm.name"
                label="Name"
                type="text"
                badge="Required"
                placeholder="Main Street 123 or My House"
                required
            />

            <flux:textarea
                wire:model="placeForm.description"
                label="Description"
                type="text"
                placeholder="The one with the solar panels"
            />

            <div class="flex justify-end space-x-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    Create Place
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Invite modal --}}
    <flux:modal name="invitation-modal" class="md:w-96">
        <form wire:submit="inviteMember" class="space-y-6">
            <div>
                <flux:heading size="lg">Invite Member</flux:heading>
                <flux:subheading
                    >Invite a user to join {{ $group->name }}.
                </flux:subheading>
            </div>

            <flux:input
                wire:model="invitationEmail"
                label="Email Address"
                type="email"
                placeholder="friend@example.com"
            />

            <div class="flex justify-end space-x-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary"
                    >Send Invitation
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
