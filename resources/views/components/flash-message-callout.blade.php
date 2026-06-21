@if (session('message'))
    <flux:callout variant="info" icon="information-circle">
        <flux:callout.text>{{ session('message') }}</flux:callout.text>
    </flux:callout>
@endif
