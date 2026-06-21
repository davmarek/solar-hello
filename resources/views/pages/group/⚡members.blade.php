<?php

use App\Models\Group;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

new class extends Component {
    public Group $group;


    public function mount(Group $group)
    {
        $this->authorize('manage-members', $group);
        $this->group = $group;
    }


    public function remove($userId)
    {
        if ($userId === $this->group->owner_id) {
            Toaster::warning('Cannot remove the owner.');

            return;
        }

        $this->group->users()->detach($userId);

        Toaster::success('Member removed successfully.');
    }

    public function render()
    {
        return $this->view([
            'users' => $this->group->load('users')->users,
        ])
            ->layout('layouts::app')
            ->title("Members - {$this->group->name}");
    }
};
?>

<div>
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center">
        <flux:button
            :href="route('groups.show', $group)"
            icon="arrow-left"
            square
            variant="subtle"
            size="sm"
            class="p-0"
        />
        <div>
            <h1 class="text-3xl font-bold">Members</h1>
            <flux:subheading
                >Manage members of {{ $group->name }}</flux:subheading
            >
        </div>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Email</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($users as $user)
                <flux:table.row>
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            <flux:avatar
                                src="{{ $user->profile_photo_url }}"
                                size="xs"
                            />
                            <span class="font-medium">{{ $user->name }}</span>
                            @if ($user->id === $group->owner_id)
                                <flux:badge size="sm" color="yellow" inset
                                    >Owner</flux:badge
                                >
                            @endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $user->email }}</flux:table.cell>

                    <flux:table.cell>
                        @if ($user->id !== $group->owner_id)
                            <flux:button
                                variant="ghost"
                                size="sm"
                                wire:click="remove({{ $user->id }})"
                                wire:confirm="Are you sure you want to remove this member?"
                                icon="trash"
                                class="text-red-500 hover:text-red-600"
                                aria-label="Remove"
                            />
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
