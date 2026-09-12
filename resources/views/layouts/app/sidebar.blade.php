<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        {{-- Force light / white admin theme (ignore saved dark preference) --}}
        <script>
            (() => {
                localStorage.setItem('flux.appearance', 'light');
                document.documentElement.classList.remove('dark');
            })();
        </script>
    </head>
    <body class="min-h-screen bg-white text-zinc-900 antialiased">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-white">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <div class="px-4 pb-2">
                <div class="rounded-xl border border-emergency/20 bg-emergency/10 px-3 py-2">
                    <p class="font-display text-xs font-semibold uppercase tracking-[0.16em] text-emergency">
                        {{ __('Command Center') }}
                    </p>
                    <p class="mt-0.5 text-xs text-zinc-500">
                        {{ __('Integrity monitoring') }}
                    </p>
                </div>
            </div>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Operations')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="chart-bar" :href="route('admin.analytics')" :current="request()->routeIs('admin.analytics')" wire:navigate>
                        {{ __('Analytics') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="bell" :href="route('admin.notifications')" :current="request()->routeIs('admin.notifications')" wire:navigate>
                        {{ __('Notifications') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clipboard-document-list" :href="route('admin.reports')" :current="request()->routeIs('admin.reports') || request()->routeIs('admin.reports.show')" wire:navigate>
                        {{ __('Reports') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="map" :href="route('admin.map')" :current="request()->routeIs('admin.map')" wire:navigate>
                        {{ __('GIS Map') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="shield-check" :href="route('admin.responders')" :current="request()->routeIs('admin.responders')" wire:navigate>
                        {{ __('Responders') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="phone" :href="route('admin.hotlines')" :current="request()->routeIs('admin.hotlines')" wire:navigate>
                        {{ __('Hotlines') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="users" :href="route('admin.users')" :current="request()->routeIs('admin.users')" wire:navigate>
                        {{ __('Users') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="no-symbol" :href="route('admin.non-emergencies')" :current="request()->routeIs('admin.non-emergencies')" wire:navigate>
                        {{ __('Non-Emergency') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <div class="hidden px-4 pb-2 lg:block">
                <livewire:admin-notification-bell />
            </div>

            <flux:sidebar.nav>
                <flux:sidebar.item icon="cog-6-tooth" :href="route('profile.edit')" :current="request()->routeIs('profile.edit')" wire:navigate>
                    {{ __('Settings') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden border-b border-zinc-200 bg-white">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <div class="me-2">
                <livewire:admin-notification-bell />
            </div>

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
