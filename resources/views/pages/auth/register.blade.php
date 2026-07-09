<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Create an account')"
            :description="__('Enter your mobile number to create your account')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
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

            <div class="flex items-center justify-end">
                <flux:button
                    type="submit"
                    variant="primary"
                    class="w-full"
                    data-test="register-user-button"
                >
                    {{ __('Create Account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>
                {{ __('Log in') }}
            </flux:link>
        </div>
    </div>
</x-layouts::auth>