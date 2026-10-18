<?php

use App\Models\EmergencyReport;
use App\Models\RejectedResolution;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Responder Resolution Images')] class extends Component {
    use WithPagination;

    #[Url]
    public string $status = 'pending';

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function setAuthenticity(int $id, string $decision): void
    {
        if (! in_array($decision, ['approved', 'rejected', 'pending'], true)) {
            return;
        }

        $report = EmergencyReport::query()
            ->whereNotNull('resolution_image_path')
            ->findOrFail($id);

        $report->update([
            'authenticity_status' => $decision,
            'authenticity_reviewed_at' => now(),
            'authenticity_reviewed_by' => Auth::id(),
        ]);

        if ($decision === 'rejected') {
            $report->archiveRejectedResolution(Auth::id());
        }

        Flux::toast(
            variant: $decision === 'rejected' ? 'danger' : 'success',
            text: __('Resolution #:id marked :status.', [
                'id' => $report->id,
                'status' => $decision,
            ]),
        );
    }

    public function with(): array
    {
        $base = EmergencyReport::query()->whereNotNull('resolution_image_path');

        $query = (clone $base)
            ->with(['user', 'assignedResponder'])
            ->latest('updated_at');

        if ($this->status !== 'all') {
            $query->where('authenticity_status', $this->status);
        }

        return [
            'reports' => $query->paginate(12),
            'pendingCount' => (clone $base)->where('authenticity_status', 'pending')->count(),
            'approvedCount' => (clone $base)->where('authenticity_status', 'approved')->count(),
            'rejectedCount' => (clone $base)->where('authenticity_status', 'rejected')->count(),
            'rejectedArchive' => RejectedResolution::query()
                ->with(['report', 'responder', 'rejector'])
                ->latest('rejected_at')
                ->paginate(10, pageName: 'archivePage'),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-4 md:p-6">
    <div>
        <p class="font-display text-xs font-semibold uppercase tracking-[0.18em] text-emergency">{{ __('Authenticity') }}</p>
        <flux:heading size="xl" class="font-display mt-1 text-3xl tracking-wide">{{ __('Responder Resolution Image') }}</flux:heading>
        <flux:text class="mt-1">
            {{ __('Photos responders upload when marking a case resolved. Approve or reject each one.') }}
        </flux:text>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <flux:card class="space-y-1">
            <flux:text>{{ __('Pending approval') }}</flux:text>
            <flux:heading size="xl">{{ $pendingCount }}</flux:heading>
        </flux:card>
        <flux:card class="space-y-1">
            <flux:text>{{ __('Approved') }}</flux:text>
            <flux:heading size="xl">{{ $approvedCount }}</flux:heading>
        </flux:card>
        <flux:card class="space-y-1">
            <flux:text>{{ __('Rejected') }}</flux:text>
            <flux:heading size="xl">{{ $rejectedCount }}</flux:heading>
        </flux:card>
    </div>

    <flux:card class="max-w-xs">
        <flux:select wire:model.live="status" :label="__('Approval status')">
            <option value="pending">{{ __('Pending') }}</option>
            <option value="approved">{{ __('Approved') }}</option>
            <option value="rejected">{{ __('Rejected') }}</option>
            <option value="all">{{ __('All') }}</option>
        </flux:select>
    </flux:card>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($reports as $report)
            <flux:card class="space-y-3">
                <img
                    src="{{ Storage::disk('public')->url($report->resolution_image_path) }}"
                    alt="Resolution photo for report {{ $report->id }}"
                    class="h-48 w-full rounded-lg bg-zinc-100 object-cover"
                />
                <div class="space-y-1 text-sm">
                    <div class="font-semibold text-zinc-900">{{ __('Report #:id', ['id' => $report->id]) }}</div>
                    <div class="capitalize text-zinc-600">{{ str_replace('_', ' ', $report->confirmed_type) }} · {{ $report->status }}</div>
                    <div class="text-zinc-500">
                        {{ __('Responder') }}:
                        {{ $report->assignedResponder?->name ?: ($report->assignedResponder?->mobile_number ?? '—') }}
                    </div>
                    <div class="text-xs font-semibold uppercase tracking-wide
                        {{ $report->authenticity_status === 'approved' ? 'text-green-700' : ($report->authenticity_status === 'rejected' ? 'text-red-700' : 'text-amber-700') }}">
                        {{ $report->authenticity_status ?? 'pending' }}
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <flux:button size="sm" variant="primary" wire:click="setAuthenticity({{ $report->id }}, 'approved')">
                        {{ __('Approve') }}
                    </flux:button>
                    <flux:button size="sm" variant="danger" wire:click="setAuthenticity({{ $report->id }}, 'rejected')">
                        {{ __('Reject') }}
                    </flux:button>
                    <flux:button size="sm" variant="ghost" :href="route('admin.reports.show', $report)" wire:navigate>
                        {{ __('Open report') }}
                    </flux:button>
                </div>
            </flux:card>
        @empty
            <flux:card class="md:col-span-2 xl:col-span-3">
                <flux:text>{{ __('No resolution images in this status yet.') }}</flux:text>
            </flux:card>
        @endforelse
    </div>

    @if (method_exists($reports, 'links'))
        <div>{{ $reports->links() }}</div>
    @endif

    <flux:card class="space-y-4">
        <div>
            <flux:heading size="lg">{{ __('Rejected resolutions') }}</flux:heading>
            <flux:text class="mt-1">
                {{ __('Permanent record of every rejected resolution photo, kept even if the responder deletes or replaces it.') }}
            </flux:text>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-zinc-500">
                        <th class="py-2 pe-4 font-semibold">{{ __('Photo') }}</th>
                        <th class="py-2 pe-4 font-semibold">{{ __('Report') }}</th>
                        <th class="py-2 pe-4 font-semibold">{{ __('Type') }}</th>
                        <th class="py-2 pe-4 font-semibold">{{ __('Responder') }}</th>
                        <th class="py-2 pe-4 font-semibold">{{ __('Rejected on') }}</th>
                        <th class="py-2 pe-4 font-semibold">{{ __('Rejected by') }}</th>
                        <th class="py-2 pe-4 font-semibold">{{ __('Note') }}</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rejectedArchive as $item)
                        <tr class="border-b border-zinc-100 align-middle">
                            <td class="py-2 pe-4">
                                @if ($item->imageUrl())
                                    <a href="{{ $item->imageUrl() }}" target="_blank" rel="noopener">
                                        <img src="{{ $item->imageUrl() }}" alt="" class="h-14 w-20 rounded-md bg-zinc-100 object-cover" />
                                    </a>
                                @else
                                    <span class="text-zinc-400">{{ __('Image removed') }}</span>
                                @endif
                            </td>
                            <td class="py-2 pe-4 font-semibold">#{{ $item->emergency_report_id ?? '—' }}</td>
                            <td class="py-2 pe-4 capitalize">{{ str_replace('_', ' ', $item->report_type ?? '—') }}</td>
                            <td class="py-2 pe-4">{{ $item->responder?->name ?: ($item->responder?->mobile_number ?? '—') }}</td>
                            <td class="py-2 pe-4 whitespace-nowrap">{{ $item->rejected_at?->timezone(config('app.timezone'))->format('M d, Y g:i A') ?? '—' }}</td>
                            <td class="py-2 pe-4">{{ $item->rejector?->name ?: ($item->rejector?->mobile_number ?? '—') }}</td>
                            <td class="py-2 pe-4 max-w-xs truncate">{{ $item->note ?: '—' }}</td>
                            <td class="py-2 text-end">
                                @if ($item->report)
                                    <flux:button size="sm" variant="ghost" :href="route('admin.reports.show', $item->report)" wire:navigate>
                                        {{ __('Open report') }}
                                    </flux:button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-6 text-center text-zinc-500">{{ __('No rejected resolutions yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $rejectedArchive->links() }}</div>
    </flux:card>
</div>
