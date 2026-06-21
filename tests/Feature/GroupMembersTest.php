<?php

use App\Models\Group;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('owner can view group members', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    $group->users()->attach($owner);
    $member = User::factory()->create();
    $group->users()->attach($member);

    actingAs($owner)
        ->get(route('groups.members', $group))
        ->assertOk()
        ->assertSee($member->name);
});

test('non-owner cannot view group members', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    $otherUser = User::factory()->create();

    actingAs($otherUser)
        ->get(route('groups.members', $group))
        ->assertForbidden();
});

test('owner can remove member', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create(['owner_id' => $owner->id]);
    $group->users()->attach($owner);
    $member = User::factory()->create();
    $group->users()->attach($member);

    Livewire::actingAs($owner)
        ->test('pages::group.members', ['group' => $group])
        ->call('remove', $member->id);

    expect($group->users()->where('user_id', $member->id)->exists())->toBeFalse();
});
