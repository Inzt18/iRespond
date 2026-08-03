<x-layouts::auth :title="__('Admin Login')">
    <div class="flex flex-col gap-6 rounded-2xl border border-white/10 bg-zinc-900/80 p-6 shadow-2xl shadow-black/40 backdrop-blur sm:p-8">
        <x-auth-header
            :title="__('Admin Access')"
            :description="__('Sign in to monitor emergency transactions and data integrity')"
        />

        <x-auth-session-status
            class="text-center"
            :status="session('status')"
        />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <flux:input
                name="username"
                :label="__('Username')"
                :value="old('username')"
                type="text"
                required
                autofocus
                autocomplete="username"
                placeholder="admin"
            />

            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="current-password"
                viewable
            />

            <flux:checkbox
                name="remember"
                :label="__('Remember me')"
                :checked="old('remember')"
            />

            <flux:button
                variant="primary"
                type="submit"
                class="w-full"
                data-test="login-button"
            >
                {{ __('Enter Command Center') }}
            </flux:button>
        </form>

        <div class="space-x-1 text-center text-sm text-zinc-400">
            <span>{{ __('Need an admin account?') }}</span>
            <flux:link :href="route('register')" wire:navigate>
                {{ __('Register') }}
            </flux:link>
        </div>
    </div>
</x-layouts::auth>
