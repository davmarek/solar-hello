<x-layouts::auth title="Accept Invitation">
    <div class="flex flex-col gap-6">
        <x-auth-header
            title="Join {{ $invitation->group->name }}"
            description="You've been invited to join this group"
        />

        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <div class="flex flex-col gap-2 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-zinc-500 dark:text-zinc-400">Invited as</span>
                    <span class="font-medium">{{ $invitation->email }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-zinc-500 dark:text-zinc-400">Group</span>
                    <span class="font-medium">{{ $invitation->group->name }}</span>
                </div>
            </div>
        </div>

        <form
            method="POST"
            action="{{ route('invitations.accept', $invitation) }}"
            class="flex flex-col gap-4"
        >
            @csrf

            <flux:button type="submit" variant="primary" class="w-full">
                Accept Invitation
            </flux:button>
        </form>

        <div class="space-x-1 text-center text-sm text-zinc-600 rtl:space-x-reverse dark:text-zinc-400">
            <span>Don't want to join?</span>
            <flux:link :href="route('home')" wire:navigate>Go back home</flux:link>
        </div>
    </div>
</x-layouts::auth>
