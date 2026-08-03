<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-950 antialiased">
        <div class="relative grid min-h-dvh flex-col lg:grid-cols-2">
            {{-- Brand / concept panel --}}
            <div class="irespond-auth-panel relative hidden overflow-hidden text-white lg:flex lg:flex-col">
                <div class="irespond-grid absolute inset-0 opacity-60"></div>

                <div class="relative z-10 flex h-full flex-col p-10 xl:p-14">
                    <a href="{{ route('home') }}" class="flex items-center gap-3" wire:navigate>
                        <span class="irespond-pulse-ring flex size-11 items-center justify-center rounded-xl bg-emergency">
                            <x-app-logo-icon class="size-6 text-white" />
                        </span>
                        <span class="font-display text-3xl font-bold tracking-wide">iRespond</span>
                    </a>

                    <div class="mt-auto max-w-lg space-y-6 pb-6">
                        <p class="font-display text-5xl font-bold leading-none tracking-wide xl:text-6xl">
                            Monitor.<br>
                            Verify.<br>
                            Respond.
                        </p>
                        <p class="max-w-md text-base leading-relaxed text-white/75">
                            Admin command center for emergency reports — image classification,
                            GIS location integrity, and responder hotline oversight.
                        </p>

                        <div class="flex flex-wrap gap-2 pt-2">
                            <span class="rounded-full border border-white/15 bg-white/5 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-white/90">Fire</span>
                            <span class="rounded-full border border-white/15 bg-white/5 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-white/90">Medical</span>
                            <span class="rounded-full border border-white/15 bg-white/5 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-white/90">Natural Disaster</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Form panel --}}
            <div class="relative flex items-center justify-center bg-zinc-950 px-6 py-10 sm:px-10">
                <div class="absolute inset-x-0 top-0 h-1 bg-emergency"></div>

                <div class="mx-auto w-full max-w-md space-y-8">
                    <a href="{{ route('home') }}" class="flex items-center justify-center gap-3 lg:hidden" wire:navigate>
                        <span class="flex size-10 items-center justify-center rounded-xl bg-emergency">
                            <x-app-logo-icon class="size-5 text-white" />
                        </span>
                        <span class="font-display text-2xl font-bold tracking-wide text-white">iRespond</span>
                    </a>

                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
