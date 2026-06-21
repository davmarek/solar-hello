<?php

use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    #[Computed]
    public function groups()
    {
        return auth()->user()->groups;
    }
};
?>

<div>
    <flux:sidebar.group
        heading="Your Groups"
        icon="rectangle-stack"
        class="grid"
        expandable
    >
        @foreach ($this->groups() as $group)
            <flux:sidebar.item
                href="{{ route('groups.show', $group) }}"
                wire:navigate
            >
                {{ Str::limit($group->name, 20) }}
            </flux:sidebar.item>
        @endforeach
    </flux:sidebar.group>
</div>
