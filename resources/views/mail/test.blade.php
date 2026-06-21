<x-mail::message>
    # Test successful! If you see this, *Mailpit* is working **perfectly**.

    <x-mail::button :url="$url"> Visit the app </x-mail::button>

    <x-mail::panel> This is the panel content. </x-mail::panel>

    <x-mail::table>
        | Laravel | Table | Example | | :------- | :-------: | -------: | | Col
        2 is | Centered | $10 | | Col 3 is | Right-Aligned | $20 |
    </x-mail::table>
</x-mail::message>
