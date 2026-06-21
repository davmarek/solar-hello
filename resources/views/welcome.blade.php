<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include ('partials.head')
</head>
<body
    class="min-h-svh bg-white text-zinc-900 antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900 dark:text-white"
>
    <div class="relative flex min-h-svh flex-col overflow-hidden">
        {{-- Soft sun-glow accent in the background --}}
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-x-0 -top-40 -z-10 flex justify-center"
        >
            <div
                class="h-[40rem] w-[40rem] rounded-full bg-emerald-200/40 blur-3xl dark:bg-emerald-500/10"
            ></div>
        </div>

        {{-- Header --}}
        <header
            class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-6"
        >
            <a
                href="{{ route('home') }}"
                class="flex items-center gap-2 font-medium"
            >
                <x-app-logo-icon
                    class="size-6 text-emerald-600 dark:text-emerald-400"
                />
                <span class="text-base font-semibold tracking-tight">
                    {{ config('app.name', 'Solar Hello') }}
                </span>
            </a>

            <nav class="flex items-center gap-2 text-sm">
                @auth
                    <a
                        href="{{ route('groups.index') }}"
                        class="inline-flex h-9 items-center rounded-md bg-zinc-900 px-4 font-medium text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
                    >
                        Open dashboard
                    </a>
                @else
                    <a
                        href="{{ route('login') }}"
                        class="inline-flex h-9 items-center rounded-md px-3 font-medium text-zinc-700 transition hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white"
                    >
                        Sign in
                    </a>
                    <a
                        href="{{ route('register') }}"
                        class="inline-flex h-9 items-center rounded-md bg-zinc-900 px-4 font-medium text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
                    >
                        Get started
                    </a>
                @endauth
            </nav>
        </header>

        {{-- Hero --}}
        <main
            class="mx-auto flex w-full max-w-4xl flex-1 flex-col items-center justify-center px-6 pb-12 text-center"
        >
            <span
                class="mb-7 inline-flex items-center gap-2 rounded-full border border-zinc-200/80 bg-white/60 px-3 py-1 text-xs font-medium text-zinc-700 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-300"
            >
                <span class="size-1.5 rounded-full bg-emerald-500"></span>
                Share solar energy with people you trust
            </span>

            <h1
                class="text-balance text-4xl font-semibold tracking-tight sm:text-5xl md:text-6xl"
            >
                Sunlight,
                <span class="text-emerald-600 dark:text-emerald-400">
                    shared.
                </span>
            </h1>

            <p
                class="mt-5 max-w-xl text-balance text-base leading-relaxed text-zinc-600 sm:text-lg dark:text-zinc-400"
            >
                Connect inverters and meters across the homes you care about.
                Allocate spare power where it's needed most. Watch every
                kilowatt land somewhere useful.
            </p>

            <div
                class="mt-10 flex flex-col items-center gap-3 sm:flex-row sm:gap-4"
            >
                @auth
                    <a
                        href="{{ route('groups.index') }}"
                        class="inline-flex h-11 items-center rounded-md bg-zinc-900 px-6 text-sm font-medium text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
                    >
                        Open dashboard
                    </a>
                @else
                    <a
                        href="{{ route('register') }}"
                        class="inline-flex h-11 items-center rounded-md bg-zinc-900 px-6 text-sm font-medium text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
                    >
                        Create an account
                    </a>
                    <a
                        href="{{ route('login') }}"
                        class="inline-flex h-11 items-center rounded-md px-4 text-sm font-medium text-zinc-700 transition hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white"
                    >
                        I already have one →
                    </a>
                @endauth
            </div>
        </main>

        {{-- Feature trio --}}
        <section
            class="mx-auto grid w-full max-w-5xl grid-cols-1 gap-4 px-6 pb-12 md:grid-cols-3"
        >
            @php
                $features = [
                    [
                        'title' => 'Connect',
                        'body' => 'Bring Solax, Shelly, and other inverters into one place — credentials encrypted, sync handled for you.',
                    ],
                    [
                        'title' => 'Allocate',
                        'body' => 'Direct surplus production from one home to another. Set a percentage, change it whenever life changes.',
                    ],
                    [
                        'title' => 'Understand',
                        'body' => 'Per-place dashboards turn raw readings into the only number you actually want: power available right now.',
                    ],
                ];
            @endphp

            @foreach ($features as $feature)
                <div
                    class="rounded-xl border border-zinc-200/80 bg-white/40 p-5 backdrop-blur transition hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900/40 dark:hover:border-zinc-700"
                >
                    <h3 class="text-sm font-semibold tracking-tight">
                        {{ $feature['title'] }}
                    </h3>
                    <p
                        class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400"
                    >
                        {{ $feature['body'] }}
                    </p>
                </div>
            @endforeach
        </section>

        <footer
            class="mx-auto w-full max-w-6xl px-6 pb-8 pt-4 text-center text-xs text-zinc-500 dark:text-zinc-500"
        >
            David Marek - {{ date('Y') }} {{ config('app.name', 'Solar Hello') }}
        </footer>
    </div>
</body>
</html>
