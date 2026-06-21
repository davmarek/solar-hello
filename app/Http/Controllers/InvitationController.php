<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class InvitationController extends Controller
{
    /**
     * Display the invitation page.
     *
     * For existing users: shows an "Accept Invitation" confirmation page.
     * For new users: shows a registration form pre-filled with the invited email.
     */
    public function show(Invitation $invitation): View
    {
        $invitation->load('group');
        $existingUser = User::where('email', $invitation->email)->first();

        if ($existingUser) {
            return view('invitations.accept', [
                'invitation' => $invitation,
                'user' => $existingUser,
            ]);
        }

        return view('invitations.register', [
            'invitation' => $invitation,
        ]);
    }

    /**
     * Accept an invitation for an existing user.
     * Adds the user to the group and deletes the invitation.
     */
    public function accept(Request $request, Invitation $invitation): RedirectResponse
    {
        $user = User::where('email', $invitation->email)->firstOrFail();

        if (! $user->groups()->where('group_id', $invitation->group_id)->exists()) {
            $user->groups()->attach($invitation->group_id);
        }

        $invitation->delete();

        if (auth()->check()) {
            return redirect()->route('groups.index');
        }

        return redirect()->route('login')->with('status', 'You have been added to the group. Please log in.');
    }

    /**
     * Register a new user from an invitation.
     * Creates the account, joins the group, and logs in.
     */
    public function store(Request $request, Invitation $invitation): RedirectResponse
    {
        // Ensure no account exists yet for this email
        if (User::where('email', $invitation->email)->exists()) {
            return redirect()->route('invitations.show', $invitation);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $invitation->email,
            'password' => Hash::make($validated['password']),
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        $user->groups()->attach($invitation->group_id);

        $invitation->delete();

        Auth::login($user);

        return redirect()->route('groups.index');
    }
}
