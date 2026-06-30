<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('IRESPOND: EMERGENCY RESPONSE SYSTEM')"
            :description="__('Enter your registered mobile number to log in')"
        />

        <!-- Session Status -->
        <x-auth-session-status
            class="text-center"
            :status="session('status')"
        />

        <x-passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Mobile Number -->
            <flux:input
                name="mobile_number"
                :label="__('Mobile Number')"
                :value="old('mobile_number')"
                type="tel"
                required
                autofocus
                autocomplete="tel"
                placeholder="09XXXXXXXXX"
                pattern="^09\d{9}$"
                maxlength="11"
            />

            <!-- Remember Me -->
            <flux:checkbox
                name="remember"
                :label="__('Remember me')"
                :checked="old('remember')"
            />

            <div class="flex items-center justify-end">
                <flux:button
                    variant="primary"
                    type="submit"
                    class="w-full"
                    data-test="login-button"
                >
                    {{ __('Log in') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
            <span>{{ __("Don't have an account?") }}</span>
            <flux:link :href="route('register')" wire:navigate>
                {{ __('Sign up') }}
            </flux:link>
        </div>
    </div>
</x-layouts::auth>