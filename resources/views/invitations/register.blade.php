<x-layouts::auth title="Accept Invitation">
    <div class="flex flex-col gap-6">
        <x-auth-header
            title="Join {{ $invitation->group->name }}"
            description="Complete your registration to join the group"
        />

        <form
            method="POST"
            action="{{ route('invitations.store', $invitation) }}"
            class="flex flex-col gap-6"
        >
            @csrf

            <flux:input
                label="Email"
                value="{{ $invitation->email }}"
                disabled
                readonly
            />

            <flux:input
                name="name"
                label="Name"
                type="text"
                required
                autofocus
                autocomplete="name"
                placeholder="Full name"
                :value="old('name')"
            />

            <flux:input
                name="password"
                label="Password"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Password"
            />

            <flux:input
                name="password_confirmation"
                label="Confirm Password"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Confirm password"
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full">
                    Register & Join
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::auth>
