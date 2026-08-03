<x-layouts::auth :title="__('Admin Register')">
    <div class="flex flex-col gap-6 rounded-2xl border border-white/10 bg-zinc-900/80 p-6 shadow-2xl shadow-black/40 backdrop-blur sm:p-8">
        <x-auth-header
            :title="__('Create Admin Account')"
            :description="__('Register authorized personnel for emergency report monitoring')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-4">
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input
                    name="first_name"
                    :label="__('First name')"
                    :value="old('first_name')"
                    type="text"
                    required
                    autofocus
                    autocomplete="given-name"
                />

                <flux:input
                    name="middle_name"
                    :label="__('Middle name')"
                    :value="old('middle_name')"
                    type="text"
                    autocomplete="additional-name"
                />
            </div>

            <flux:input
                name="last_name"
                :label="__('Last name')"
                :value="old('last_name')"
                type="text"
                required
                autocomplete="family-name"
            />

            <flux:input
                name="email"
                :label="__('Email')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="admin@example.com"
            />

            <flux:input
                name="username"
                :label="__('Username')"
                :value="old('username')"
                type="text"
                required
                autocomplete="username"
                placeholder="admin"
            />

            <flux:input
                name="mobile_number"
                :label="__('Mobile number (optional)')"
                :value="old('mobile_number')"
                type="tel"
                autocomplete="tel"
                placeholder="09XXXXXXXXX"
                pattern="^09\d{9}$"
                maxlength="11"
            />

            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                viewable
            />

            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                viewable
            />

            <flux:button
                type="submit"
                variant="primary"
                class="mt-2 w-full"
                data-test="register-user-button"
            >
                {{ __('Create Admin Account') }}
            </flux:button>
        </form>

        <div class="space-x-1 text-center text-sm text-zinc-400">
            <span>{{ __('Already registered?') }}</span>
            <flux:link :href="route('login')" wire:navigate>
                {{ __('Log in') }}
            </flux:link>
        </div>
    </div>
</x-layouts::auth>
