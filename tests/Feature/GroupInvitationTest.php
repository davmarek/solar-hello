<?php

use App\Mail\GroupInvitation;
use App\Models\Group;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('allows a user to create a group', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::group.index')
        ->set('name', 'My Solar Group')
        ->call('createGroup')
        ->assertDispatched('group-created');

    $this->assertDatabaseHas('groups', [
        'name' => 'My Solar Group',
        'owner_id' => $user->id,
    ]);
});

it('allows an owner to invite a member', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    $group->users()->attach($owner);

    Livewire::actingAs($owner)
        ->test('pages::group.show', ['group' => $group])
        ->set('invitationEmail', 'invitee@example.com')
        ->call('inviteMember');

    $this->assertDatabaseHas('invitations', [
        'group_id' => $group->id,
        'email' => 'invitee@example.com',
    ]);

    Mail::assertQueued(GroupInvitation::class, function ($mail) {
        return $mail->hasTo('invitee@example.com');
    });
});

it('shows registration form for new user invitation', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);

    $invitation = Invitation::create([
        'group_id' => $group->id,
        'email' => 'newuser@example.com',
        'token' => 'new_user_token',
    ]);

    $response = $this->get(route('invitations.show', $invitation));

    $response->assertStatus(200);
    $response->assertSee('Join '.$group->name);
    $response->assertSee('Register & Join', false);
});

it('allows a new user to register via invitation', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);

    $invitation = Invitation::create([
        'group_id' => $group->id,
        'email' => 'newuser@example.com',
        'token' => 'random_token',
    ]);

    $response = $this->post(route('invitations.store', $invitation), [
        'name' => 'New User',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('groups.index'));

    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
        'name' => 'New User',
    ]);

    $newUser = User::where('email', 'newuser@example.com')->first();
    expect($newUser->email_verified_at)->not->toBeNull();

    $this->assertDatabaseHas('group_user', [
        'group_id' => $group->id,
        'user_id' => $newUser->id,
    ]);

    $this->assertDatabaseMissing('invitations', ['id' => $invitation->id]);
});

it('shows acceptance page for existing user invitation', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);

    $existingUser = User::factory()->create(['email' => 'existing@example.com']);

    $invitation = Invitation::create([
        'group_id' => $group->id,
        'email' => 'existing@example.com',
        'token' => 'existing_token',
    ]);

    $response = $this->get(route('invitations.show', $invitation));

    $response->assertStatus(200);
    $response->assertSee('Join '.$group->name);
    $response->assertSee('Accept Invitation');
    $response->assertDontSee('Register & Join');
});

it('allows an existing user to accept invitation via post', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);

    $existingUser = User::factory()->create(['email' => 'existing@example.com']);

    $invitation = Invitation::create([
        'group_id' => $group->id,
        'email' => 'existing@example.com',
        'token' => 'existing_token',
    ]);

    $response = $this->post(route('invitations.accept', $invitation));

    $response->assertRedirect(route('login'));

    $this->assertDatabaseHas('group_user', [
        'group_id' => $group->id,
        'user_id' => $existingUser->id,
    ]);

    $this->assertDatabaseMissing('invitations', ['id' => $invitation->id]);
});

it('redirects an authenticated existing user to groups after accepting', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);

    $existingUser = User::factory()->create(['email' => 'member@example.com']);

    $invitation = Invitation::create([
        'group_id' => $group->id,
        'email' => 'member@example.com',
        'token' => 'auth_token',
    ]);

    $response = $this->actingAs($existingUser)
        ->post(route('invitations.accept', $invitation));

    $response->assertRedirect(route('groups.index'));

    $this->assertDatabaseHas('group_user', [
        'group_id' => $group->id,
        'user_id' => $existingUser->id,
    ]);
});

it('allows an owner to see pending invitations and delete them', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    $group->users()->attach($owner);

    $invitation = Invitation::create([
        'group_id' => $group->id,
        'email' => 'to_delete@example.com',
        'token' => 'delete_token',
    ]);

    Livewire::actingAs($owner)
        ->test('pages::group.show', ['group' => $group])
        ->assertSee('to_delete@example.com')
        ->call('deleteInvitation', $invitation->id);

    $this->assertDatabaseMissing('invitations', ['id' => $invitation->id]);
});

it('does not duplicate group membership when invitation is accepted twice', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);

    $existingUser = User::factory()->create(['email' => 'duplicate@example.com']);
    $existingUser->groups()->attach($group->id);

    $invitation = Invitation::create([
        'group_id' => $group->id,
        'email' => 'duplicate@example.com',
        'token' => 'dup_token',
    ]);

    $response = $this->post(route('invitations.accept', $invitation));

    expect($existingUser->groups()->where('group_id', $group->id)->count())->toBe(1);
    $this->assertDatabaseMissing('invitations', ['id' => $invitation->id]);
});
