@props([
    'title',
    'description',
])

<div class="flex w-full flex-col gap-2 text-center lg:text-start">
    <p class="font-display text-sm font-semibold uppercase tracking-[0.18em] text-emergency">
        {{ __('Emergency Response System') }}
    </p>
    <flux:heading size="xl" class="font-display text-3xl tracking-wide">{{ $title }}</flux:heading>
    <flux:subheading>{{ $description }}</flux:subheading>
</div>
