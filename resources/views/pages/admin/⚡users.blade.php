<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Users')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->id === Auth::id()) {
            Flux::toast(variant: 'danger', text: __('You cannot disable your own account.'));

            return;
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        Flux::toast(
            variant: 'success',
            text: $user->is_active ? __('User enabled.') : __('User disabled.')
        );
    }

    public function with(): array
    {
        $query = User::query()
            ->withCount('emergencyReports')
            ->latest();

        if (trim($this->search) !== '') {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('mobile_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        return [
            'users' => $query->paginate(20),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <flux:heading size="xl">{{ __('Users') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Monitor registered accounts. Disable abusive users if needed.') }}</flux:text>
        </div>

        <flux:card>
            <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" placeholder="Mobile or name" class="mb-4 max-w-md" />

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <tr>
                            <th class="px-2 py-2">{{ __('Name') }}</th>
                            <th class="px-2 py-2">{{ __('Username') }}</th>
                            <th class="px-2 py-2">{{ __('Email') }}</th>
                            <th class="px-2 py-2">{{ __('Mobile') }}</th>
                            <th class="px-2 py-2">{{ __('Role') }}</th>
                            <th class="px-2 py-2">{{ __('Reports') }}</th>
                            <th class="px-2 py-2">{{ __('Active') }}</th>
                            <th class="px-2 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="px-2 py-2">{{ $user->name ?: trim(($user->first_name.' '.$user->last_name)) ?: '—' }}</td>
                                <td class="px-2 py-2">{{ $user->username ?: '—' }}</td>
                                <td class="px-2 py-2">{{ $user->email ?: '—' }}</td>
                                <td class="px-2 py-2">{{ $user->mobile_number ?: '—' }}</td>
                                <td class="px-2 py-2">{{ $user->role }}</td>
                                <td class="px-2 py-2">{{ $user->emergency_reports_count }}</td>
                                <td class="px-2 py-2">
                                    @if ($user->is_active)
                                        <span class="text-green-600">{{ __('Yes') }}</span>
                                    @else
                                        <span class="text-red-600">{{ __('No') }}</span>
                                    @endif
                                </td>
                                <td class="px-2 py-2 text-end">
                                    <flux:button size="sm" variant="ghost" wire:click="toggleActive({{ $user->id }})">
                                        {{ $user->is_active ? __('Disable') : __('Enable') }}
                                    </flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-2 py-6 text-center text-zinc-500">{{ __('No users found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $users->links() }}
            </div>
        </flux:card>
</div>
