<?php

use App\Livewire\Forms\PlaceForm;
use App\Mail\GroupInvitation;
use App\Models\Group;
use App\Models\Invitation;
use App\Models\Place;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Form;
use Masmerise\Toaster\Toaster;

new class extends Component
{
    public Group $group;

    public string $invitationEmail = '';

    public PlaceForm $placeForm;

    public function mount(Group $group): void
    {
        $this->authorize('view', $this->group);
        $this->group = $group;
    }

    /**
     * Called on submitting the create place form modal
     */
    public function createPlace(): void
    {
        $this->authorize('create-place', $this->group);
        $place = $this->placeForm->store($this->group->id, auth()->id());
        $this->placeForm->reset();
        $this->modal('place-modal')->close();
        Toaster::success("Place \"{$place->name}\" has been created");
    }

    /**
     * Called on submitting the modal for inviting members
     */
    public function inviteMember(): void
    {
        $this->validate([
            'invitationEmail' => 'required|email|max:255',
        ]);

        $this->authorize('manage-members', $this->group);

        $invitation = Invitation::create([
            'group_id' => $this->group->id,
            'email' => $this->invitationEmail,
            'token' => Str::random(32),
        ]);

        Mail::to($this->invitationEmail)->send(new GroupInvitation($invitation));

        $this->modal('invitation-modal')->close();
        Toaster::success("Invitation has been sent to {$this->invitationEmail}");
        $this->reset('invitationEmail');
    }

    /**
     * Called on confirming the deletion of invitation
     */
    public function deleteInvitation(int $invitationId): void
    {
        $this->authorize('manage-members', $this->group);

        $invitation = Invitation::findOrFail($invitationId);

        if ($invitation->group_id !== $this->group->id) {
            abort(403);
        }

        $invitation->delete();
        Toaster::success('Invitation deleted successfully.');
    }

    public function render()
    {
        $this->group->load([
            'owner',
            'users',
            'places.integration',
            'places.user',
            'places.latestReading',
            'places.latestSnapshot',
            'invitations',
        ]);

        return $this->view([
            'places' => $this->group->places->where('user_id', auth()->id()),
        ])
            ->title($this->group->name.' - Group');
    }
};
