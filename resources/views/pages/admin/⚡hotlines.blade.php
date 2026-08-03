<?php

use App\Models\ResponderHotline;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Responder Hotlines')] class extends Component {
    public ?int $editingId = null;

    public string $type = '';

    public string $label = '';

    public string $phone_number = '';

    public function edit(int $id): void
    {
        $hotline = ResponderHotline::findOrFail($id);
        $this->editingId = $hotline->id;
        $this->type = $hotline->type;
        $this->label = $hotline->label;
        $this->phone_number = $hotline->phone_number;
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->type = '';
        $this->label = '';
        $this->phone_number = '';
        $this->resetValidation();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'type' => [
                'required',
                'string',
                'max:50',
                Rule::unique('responder_hotlines', 'type')->ignore($this->editingId),
            ],
            'label' => ['required', 'string', 'max:100'],
            'phone_number' => ['required', 'string', 'max:30'],
        ]);

        if ($this->editingId) {
            ResponderHotline::whereKey($this->editingId)->update($validated);
            Flux::toast(variant: 'success', text: __('Hotline updated.'));
        } else {
            ResponderHotline::create($validated);
            Flux::toast(variant: 'success', text: __('Hotline created.'));
        }

        $this->resetForm();
    }

    public function delete(int $id): void
    {
        ResponderHotline::whereKey($id)->delete();
        if ($this->editingId === $id) {
            $this->resetForm();
        }
        Flux::toast(variant: 'success', text: __('Hotline deleted.'));
    }

    public function with(): array
    {
        return [
            'hotlines' => ResponderHotline::query()->orderBy('id')->get(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <flux:heading size="xl">{{ __('Responder Hotlines') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Manage dial numbers used by the mobile app.') }}</flux:text>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <flux:card>
                <flux:heading size="lg" class="mb-4">
                    {{ $editingId ? __('Edit Hotline') : __('Add Hotline') }}
                </flux:heading>

                <form wire:submit="save" class="space-y-4">
                    <flux:input wire:model="type" :label="__('Type key')" placeholder="fire / medical / natural_disaster" :disabled="(bool) $editingId" />
                    <flux:input wire:model="label" :label="__('Label')" placeholder="Fire" />
                    <flux:input wire:model="phone_number" :label="__('Phone number')" placeholder="116" />
                    <div class="flex gap-2">
                        <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
                        @if ($editingId)
                            <flux:button type="button" variant="ghost" wire:click="resetForm">{{ __('Cancel') }}</flux:button>
                        @endif
                    </div>
                </form>
            </flux:card>

            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('Current Hotlines') }}</flux:heading>
                <div class="space-y-3">
                    @forelse ($hotlines as $hotline)
                        <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            <div>
                                <div class="font-medium">{{ $hotline->label }}</div>
                                <div class="text-sm text-zinc-500">{{ $hotline->type }} · {{ $hotline->phone_number }}</div>
                            </div>
                            <div class="flex gap-2">
                                <flux:button size="sm" variant="ghost" wire:click="edit({{ $hotline->id }})">{{ __('Edit') }}</flux:button>
                                <flux:button size="sm" variant="danger" wire:click="delete({{ $hotline->id }})" wire:confirm="Delete this hotline?">
                                    {{ __('Delete') }}
                                </flux:button>
                            </div>
                        </div>
                    @empty
                        <flux:text>{{ __('No hotlines configured.') }}</flux:text>
                    @endforelse
                </div>
            </flux:card>
        </div>
</div>
