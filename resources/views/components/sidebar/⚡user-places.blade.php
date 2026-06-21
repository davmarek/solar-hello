<?php

use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    #[Computed]
    public function places()
    {
        return auth()->user()->places;
    }
};
?>

<div>
    <flux:sidebar.group heading="Your Places" icon="map-pin" expandable>
        @foreach ($this->places() as $place)
            <flux:sidebar.item
                href="{{ route('places.show', ['group' => $place->group_id, 'place' => $place->id]) }}"
                wire:navigate
            >
                {{ Str::limit($place->name, 20) }}
            </flux:sidebar.item>
        @endforeach
    </flux:sidebar.group>
</div>
