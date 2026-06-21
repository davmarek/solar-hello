<?php

namespace App\Livewire\Forms;

use App\Models\Place;
use Livewire\Attributes\Validate;
use Livewire\Form;

class PlaceForm extends Form
{
    public ?Place $place;

    #[Validate('required|min:5|max:50')]
    public string $name = '';

    public ?string $description = null;

    public function setPlace(Place $place): void
    {
        $this->place = $place;
        $this->name = $place->name;
        $this->description = $place->description;
    }

    public function store(int $groupId, int $userId): Place
    {
        $this->validate();

        return Place::create([
            'name' => $this->name,
            'description' => $this->description ?: null,
            'user_id' => $userId,
            'group_id' => $groupId,
        ]);
    }

    public function update(): void
    {
        $this->validate();
        $this->place->update([
            'name' => $this->name,
            'description' => $this->description ?: null,
        ]);
    }
}
