<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('LGU Responders')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    public string $first_name = '';

    public string $middle_name = '';

    public string $last_name = '';

    public string $mobile_number = '';

    public string $department_type = 'fire';

    public string $department_name = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function createResponder(): void
    {
        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'mobile_number' => [
                'required',
                'regex:/^09\d{9}$/',
                Rule::unique('users', 'mobile_number'),
            ],
            'department_type' => ['required', 'in:fire,medical,natural_disaster'],
            'department_name' => ['nullable', 'string', 'max:150'],
        ]);

        $fullName = trim(implode(' ', array_filter([
            $validated['first_name'],
            $validated['middle_name'] ?? null,
            $validated['last_name'],
        ])));

        $defaultNames = User::DEPARTMENTS;

        User::create([
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'name' => $fullName,
            'mobile_number' => $validated['mobile_number'],
            'role' => 'responder',
            'department_type' => $validated['department_type'],
            'department_name' => $validated['department_name']
                ?: ($defaultNames[$validated['department_type']] ?? null),
            'is_active' => true,
        ]);

        $this->reset([
            'first_name',
            'middle_name',
            'last_name',
            'mobile_number',
            'department_name',
        ]);
        $this->department_type = 'fire';

        Flux::toast(variant: 'success', text: __('Responder account created.'));
    }

    public function toggleActive(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->role !== 'responder') {
            Flux::toast(variant: 'danger', text: __('Not a responder account.'));

            return;
        }

        if ($user->id === Auth::id()) {
            return;
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        Flux::toast(
            variant: 'success',
            text: $user->is_active ? __('Responder enabled.') : __('Responder disabled.')
        );
    }

    public function with(): array
    {
        $query = User::query()
            ->where('role', 'responder')
            ->latest();

        if (trim($this->search) !== '') {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('mobile_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('department_name', 'like', "%{$search}%");
            });
        }

        return [
            'responders' => $query->paginate(15),
            'departments' => User::DEPARTMENTS,
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-4 md:p-6">
    <div>
        <p class="font-display text-xs font-semibold uppercase tracking-[0.18em] text-emergency">{{ __('LGU Access') }}</p>
        <flux:heading size="xl" class="font-display mt-1 text-3xl tracking-wide">{{ __('Responders') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Create accounts for Fire, Medical, and Disaster Risk departments.') }}</flux:text>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <flux:card class="space-y-4">
            <flux:heading size="lg">{{ __('Create Responder Account') }}</flux:heading>

            <form wire:submit="createResponder" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="first_name" :label="__('First name')" required />
                    <flux:input wire:model="middle_name" :label="__('Middle name')" />
                </div>
                <flux:input wire:model="last_name" :label="__('Last name')" required />
                <flux:input wire:model="mobile_number" :label="__('Mobile number')" placeholder="09XXXXXXXXX" required />
                <flux:select wire:model="department_type" :label="__('Department')" required>
                    @foreach ($departments as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="department_name" :label="__('Office / station name (optional)')" placeholder="e.g. Barangay Fire Station 1" />
                <flux:button type="submit" variant="primary">{{ __('Create Responder') }}</flux:button>
            </form>
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-4">{{ __('Responder Accounts') }}</flux:heading>
            <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" placeholder="Mobile, name, office" class="mb-4" />

            <div class="space-y-3">
                @forelse ($responders as $responder)
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        <div>
                            <div class="font-medium">{{ $responder->name ?: ($responder->first_name.' '.$responder->last_name) }}</div>
                            <div class="text-sm text-zinc-500">
                                {{ $responder->mobile_number }} · {{ $responder->departmentLabel() }}
                                @if ($responder->department_name)
                                    · {{ $responder->department_name }}
                                @endif
                            </div>
                            <div class="text-xs {{ $responder->is_active ? 'text-green-600' : 'text-red-600' }}">
                                {{ $responder->is_active ? __('Active') : __('Disabled') }}
                            </div>
                        </div>
                        <flux:button size="sm" variant="ghost" wire:click="toggleActive({{ $responder->id }})">
                            {{ $responder->is_active ? __('Disable') : __('Enable') }}
                        </flux:button>
                    </div>
                @empty
                    <flux:text>{{ __('No responder accounts yet.') }}</flux:text>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $responders->links() }}
            </div>
        </flux:card>
    </div>
</div>
