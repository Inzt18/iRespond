<?php

use App\Models\EmergencyReport;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Emergency Reports')] class extends Component {
    use WithPagination;

    #[Url]
    public string $status = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $search = '';

    #[Url]
    public bool $flaggedOnly = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function updatingFlaggedOnly(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = EmergencyReport::query()->with('user')->latest();

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if ($this->type !== '') {
            $query->where('confirmed_type', $this->type);
        }

        if ($this->flaggedOnly) {
            $query->where('flagged', true);
        }

        if (trim($this->search) !== '') {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhereHas('user', fn ($u) => $u->where('mobile_number', 'like', "%{$search}%"));
            });
        }

        return [
            'reports' => $query->paginate(15),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ __('Emergency Reports') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Monitor all citizen submissions for integrity.') }}</flux:text>
            </div>
            <flux:button size="sm" variant="ghost" :href="route('admin.analytics')" wire:navigate>
                {{ __('Analytics') }}
            </flux:button>
        </div>

        <flux:card>
            <flux:text class="mb-3 text-sm font-medium text-zinc-700">{{ __('Category') }}</flux:text>
            <div class="flex flex-wrap gap-2">
                <flux:button
                    size="sm"
                    wire:click="$set('type', '')"
                    :variant="$type === '' ? 'primary' : 'ghost'"
                >
                    {{ __('All') }}
                </flux:button>
                <flux:button
                    size="sm"
                    wire:click="$set('type', 'fire')"
                    :variant="$type === 'fire' ? 'primary' : 'ghost'"
                    class="{{ $type === 'fire' ? '' : '!text-red-700' }}"
                >
                    {{ __('Fire') }}
                </flux:button>
                <flux:button
                    size="sm"
                    wire:click="$set('type', 'medical')"
                    :variant="$type === 'medical' ? 'primary' : 'ghost'"
                    class="{{ $type === 'medical' ? '' : '!text-blue-700' }}"
                >
                    {{ __('Medical') }}
                </flux:button>
                <flux:button
                    size="sm"
                    wire:click="$set('type', 'natural_disaster')"
                    :variant="$type === 'natural_disaster' ? 'primary' : 'ghost'"
                    class="{{ $type === 'natural_disaster' ? '' : '!text-orange-700' }}"
                >
                    {{ __('Natural Disaster') }}
                </flux:button>
            </div>
        </flux:card>

        <flux:card class="grid gap-3 md:grid-cols-4">
            <flux:input wire:model.live.debounce.300ms="search" :label="__('Search ID / mobile')" placeholder="09... or report id" />
            <flux:select wire:model.live="status" :label="__('Status')">
                <option value="">{{ __('All') }}</option>
                <option value="pending">pending</option>
                <option value="responding">responding</option>
                <option value="resolved">resolved</option>
                <option value="invalid">invalid</option>
            </flux:select>
            <flux:select wire:model.live="type" :label="__('Type')">
                <option value="">{{ __('All types') }}</option>
                <option value="fire">{{ __('Fire') }}</option>
                <option value="medical">{{ __('Medical') }}</option>
                <option value="natural_disaster">{{ __('Natural Disaster') }}</option>
            </flux:select>
            <div class="flex items-end">
                <flux:checkbox wire:model.live="flaggedOnly" :label="__('Flagged only')" />
            </div>
        </flux:card>

        <flux:card>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <tr>
                            <th class="px-2 py-2">{{ __('ID') }}</th>
                            <th class="px-2 py-2">{{ __('Date') }}</th>
                            <th class="px-2 py-2">{{ __('Confirmed') }}</th>
                            <th class="px-2 py-2">{{ __('Predicted') }}</th>
                            <th class="px-2 py-2">{{ __('Confidence') }}</th>
                            <th class="px-2 py-2">{{ __('Status') }}</th>
                            <th class="px-2 py-2">{{ __('Reporter') }}</th>
                            <th class="px-2 py-2">{{ __('Issues') }}</th>
                            <th class="px-2 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reports as $report)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="px-2 py-2">#{{ $report->id }}</td>
                                <td class="px-2 py-2 whitespace-nowrap">
                                    {{ $report->created_at?->timezone(config('app.timezone'))->format('M d, Y g:i A') }}
                                </td>
                                <td class="px-2 py-2">{{ $report->confirmed_type }}</td>
                                <td class="px-2 py-2">{{ $report->predicted_type ?? '—' }}</td>
                                <td class="px-2 py-2">
                                    {{ $report->confidence !== null ? number_format($report->confidence * 100, 1).'%' : '—' }}
                                </td>
                                <td class="px-2 py-2">{{ $report->status }}</td>
                                <td class="px-2 py-2">{{ $report->user?->mobile_number ?? '—' }}</td>
                                <td class="px-2 py-2">
                                    @php $issues = $report->integrityIssues(); @endphp
                                    @if ($report->flagged || count($issues))
                                        <span class="text-red-600">
                                            {{ $report->flagged ? 'flagged' : '' }}
                                            {{ implode(', ', $issues) }}
                                        </span>
                                    @else
                                        <span class="text-zinc-500">—</span>
                                    @endif
                                </td>
                                <td class="px-2 py-2 text-end">
                                    <flux:button size="sm" :href="route('admin.reports.show', $report)" wire:navigate>
                                        {{ __('Review') }}
                                    </flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-2 py-6 text-center text-zinc-500">{{ __('No reports found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $reports->links() }}
            </div>
        </flux:card>
</div>
