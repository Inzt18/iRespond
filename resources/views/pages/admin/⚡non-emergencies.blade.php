<?php

use App\Models\ClassificationRejection;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Non-Emergency Submissions')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $reason = 'non_emergency';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingReason(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = ClassificationRejection::query()
            ->with('user')
            ->latest();

        if ($this->reason !== 'all') {
            $query->where('reason', $this->reason);
        }

        if (trim($this->search) !== '') {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('mobile_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('mobile_number', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        return [
            'rejections' => $query->paginate(20),
            'nonEmergencyCount' => ClassificationRejection::query()
                ->where('reason', 'non_emergency')
                ->count(),
            'uniquePhones' => ClassificationRejection::query()
                ->where('reason', 'non_emergency')
                ->whereNotNull('mobile_number')
                ->distinct('mobile_number')
                ->count('mobile_number'),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-4 md:p-6">
    <div>
        <flux:heading size="xl">{{ __('Non-Emergency Submissions') }}</flux:heading>
        <flux:text class="mt-1">
            {{ __('Phone numbers that uploaded images classified as non-emergency (or other CNN rejections).') }}
        </flux:text>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <flux:card class="space-y-1">
            <flux:text>{{ __('Non-emergency uploads') }}</flux:text>
            <flux:heading size="xl">{{ $nonEmergencyCount }}</flux:heading>
        </flux:card>
        <flux:card class="space-y-1">
            <flux:text>{{ __('Unique phone numbers') }}</flux:text>
            <flux:heading size="xl">{{ $uniquePhones }}</flux:heading>
        </flux:card>
    </div>

    <flux:card>
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end">
            <flux:input
                wire:model.live.debounce.300ms="search"
                :label="__('Search phone / name')"
                placeholder="09xxxxxxxxx"
                class="max-w-md"
            />
            <flux:select wire:model.live="reason" :label="__('Filter')" class="max-w-xs">
                <option value="non_emergency">{{ __('Non-emergency only') }}</option>
                <option value="low_confidence">{{ __('Low confidence') }}</option>
                <option value="uncertain">{{ __('Uncertain') }}</option>
                <option value="all">{{ __('All rejections') }}</option>
            </flux:select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-zinc-200 text-left">
                    <tr>
                        <th class="px-2 py-2">{{ __('When') }}</th>
                        <th class="px-2 py-2">{{ __('Mobile') }}</th>
                        <th class="px-2 py-2">{{ __('User') }}</th>
                        <th class="px-2 py-2">{{ __('CNN label') }}</th>
                        <th class="px-2 py-2">{{ __('Confidence') }}</th>
                        <th class="px-2 py-2">{{ __('Reason') }}</th>
                        <th class="px-2 py-2">{{ __('Image') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rejections as $item)
                        <tr class="border-b border-zinc-100">
                            <td class="px-2 py-2 whitespace-nowrap">
                                {{ $item->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-2 py-2 font-medium">
                                {{ $item->mobile_number ?: ($item->user?->mobile_number ?: '—') }}
                            </td>
                            <td class="px-2 py-2">
                                {{ $item->user?->name ?: ($item->user?->username ?: '—') }}
                            </td>
                            <td class="px-2 py-2">{{ str_replace('_', ' ', $item->predicted_label) }}</td>
                            <td class="px-2 py-2">
                                {{ $item->confidence === null ? '—' : number_format($item->confidence * 100, 1).'%' }}
                            </td>
                            <td class="px-2 py-2">{{ str_replace('_', ' ', $item->reason) }}</td>
                            <td class="px-2 py-2">
                                @if ($item->imageUrl())
                                    <a href="{{ $item->imageUrl() }}" target="_blank" class="text-emergency underline">
                                        {{ __('View') }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-2 py-6 text-center text-zinc-500">
                                {{ __('No non-emergency submissions logged yet. New rejected uploads will appear here.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $rejections->links() }}
        </div>
    </flux:card>
</div>
