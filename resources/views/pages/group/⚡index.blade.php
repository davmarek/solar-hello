<?php

use Livewire\Component;
use App\Models\Group;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $name = '';

//    public $selectedGroup = null;



    public function createGroup():void
    {
        $this->validate([
            'name' => 'required|string|max:255',
        ]);

        Auth::user()
            ->groups()
            ->create([
                'name' => $this->name,
                'owner_id' => Auth::id(),
            ]);

        $this->dispatch('group-created');

        $this->name = '';
        $this->modal('create-group-modal')->close();
        Toaster::success("Group has been created.");
    }


    public function render()
    {
        return $this->view([
            'groups' => Auth::user()->groups()->with('owner')->get(),
        ])
            ->layout('layouts::app')
            ->title('Groups');
    }
};

?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold">{{ __('Groups') }}</h1>
    </div>

    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        @foreach ($groups as $group)
            <flux:card>
                <a
                    href="{{ route('groups.show', $group) }}"
                    wire:navigate
                    class="hover:underline"
                >
                    <h3
                        class="text-lg font-medium text-gray-900 dark:text-gray-100"
                    >
                        {{ $group->name }}
                    </h3>
                </a>
                <p class="text-sm text-gray-500 dark:text-gray-400">Owner: {{ $group->owner->name }}</p>
            </flux:card>
        @endforeach

        <flux:card
            class="flex cursor-pointer flex-col items-center justify-center border-2 border-dashed border-gray-300 bg-transparent p-6 transition hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
            x-data="{ showModal: false }"
        >
            <flux:modal.trigger name="create-group-modal">
                <button type="button" class="h-full w-full text-center">
                    <flux:icon
                        name="plus"
                        class="mx-auto h-8 w-8 text-gray-400"
                    />
                    <span
                        class="mt-2 block text-sm font-medium text-gray-900 dark:text-gray-100"
                        >Create new group</span
                    >
                </button>
            </flux:modal.trigger>
        </flux:card>
    </div>

    <flux:modal name="create-group-modal" class="md:w-96">
        <form wire:submit="createGroup" class="space-y-6">
            <div>
                <flux:heading size="lg">Create Group</flux:heading>
                <flux:subheading
                    >Create a new solar sharing group.</flux:subheading
                >
            </div>

            <flux:input
                wire:model="name"
                label="Group Name"
                placeholder="My Solar Farm"
            />

            <div class="flex justify-end space-x-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary"
                    >Create</flux:button
                >
            </div>
        </form>
    </flux:modal>
</div>
