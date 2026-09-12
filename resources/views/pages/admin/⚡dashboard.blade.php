<?php

use App\Models\EmergencyReport;
use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Admin Dashboard')] class extends Component {
    public function with(): array
    {
        $reports = EmergencyReport::query();

        return [
            'totalReports' => (clone $reports)->count(),
            'pendingCount' => (clone $reports)->where('status', 'pending')->count(),
            'respondingCount' => (clone $reports)->where('status', 'responding')->count(),
            'resolvedCount' => (clone $reports)->where('status', 'resolved')->count(),
            'invalidCount' => (clone $reports)->where('status', 'invalid')->count(),
            'flaggedCount' => (clone $reports)->where('flagged', true)->count(),
            'byType' => EmergencyReport::query()
                ->selectRaw('confirmed_type, COUNT(*) as total')
                ->groupBy('confirmed_type')
                ->pluck('total', 'confirmed_type'),
            'recentReports' => EmergencyReport::with('user')->latest()->limit(8)->get(),
            'userCount' => User::query()->where('role', 'citizen')->count(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <p class="font-display text-xs font-semibold uppercase tracking-[0.18em] text-emergency">{{ __('Command Center') }}</p>
            <flux:heading size="xl" class="font-display mt-1 text-3xl tracking-wide">{{ __('iRespond Admin') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Monitor emergency reports and data integrity.') }}</flux:text>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <flux:card class="space-y-1">
                <flux:text>{{ __('Total Reports') }}</flux:text>
                <flux:heading size="xl">{{ $totalReports }}</flux:heading>
            </flux:card>
            <flux:card class="space-y-1">
                <flux:text>{{ __('Pending') }}</flux:text>
                <flux:heading size="xl">{{ $pendingCount }}</flux:heading>
            </flux:card>
            <flux:card class="space-y-1">
                <flux:text>{{ __('Flagged') }}</flux:text>
                <flux:heading size="xl">{{ $flaggedCount }}</flux:heading>
            </flux:card>
            <flux:card class="space-y-1">
                <flux:text>{{ __('Citizens') }}</flux:text>
                <flux:heading size="xl">{{ $userCount }}</flux:heading>
            </flux:card>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <flux:card class="space-y-1">
                <flux:text>{{ __('Responding') }}</flux:text>
                <flux:heading>{{ $respondingCount }}</flux:heading>
            </flux:card>
            <flux:card class="space-y-1">
                <flux:text>{{ __('Resolved') }}</flux:text>
                <flux:heading>{{ $resolvedCount }}</flux:heading>
            </flux:card>
            <flux:card class="space-y-1">
                <flux:text>{{ __('Invalid') }}</flux:text>
                <flux:heading>{{ $invalidCount }}</flux:heading>
            </flux:card>
        </div>

        <flux:card>
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="lg">{{ __('By Emergency Type') }}</flux:heading>
                <flux:button size="sm" variant="ghost" :href="route('admin.analytics')" wire:navigate>
                    {{ __('Analytics') }}
                </flux:button>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach (['fire' => 'Fire', 'medical' => 'Medical', 'natural_disaster' => 'Natural Disaster'] as $key => $label)
                    <a href="{{ route('admin.reports', ['type' => $key]) }}" wire:navigate class="rounded-lg border border-zinc-200 p-3 transition hover:bg-zinc-50 dark:border-zinc-700">
                        <flux:text>{{ $label }}</flux:text>
                        <flux:heading>{{ $byType[$key] ?? 0 }}</flux:heading>
                    </a>
                @endforeach
            </div>
        </flux:card>

        <flux:card>
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="lg">{{ __('Recent Reports') }}</flux:heading>
                <div class="flex gap-2">
                    <flux:button size="sm" variant="ghost" :href="route('admin.map')" wire:navigate>
                        {{ __('GIS Map') }}
                    </flux:button>
                    <flux:button size="sm" :href="route('admin.reports')" wire:navigate>
                        {{ __('View all') }}
                    </flux:button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <tr>
                            <th class="px-2 py-2">{{ __('ID') }}</th>
                            <th class="px-2 py-2">{{ __('Date') }}</th>
                            <th class="px-2 py-2">{{ __('Type') }}</th>
                            <th class="px-2 py-2">{{ __('Status') }}</th>
                            <th class="px-2 py-2">{{ __('Reporter') }}</th>
                            <th class="px-2 py-2">{{ __('Integrity') }}</th>
                            <th class="px-2 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentReports as $report)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="px-2 py-2">#{{ $report->id }}</td>
                                <td class="px-2 py-2 whitespace-nowrap">
                                    {{ $report->created_at?->timezone(config('app.timezone'))->format('M d, Y g:i A') }}
                                </td>
                                <td class="px-2 py-2">{{ str_replace('_', ' ', $report->confirmed_type) }}</td>
                                <td class="px-2 py-2">{{ $report->status }}</td>
                                <td class="px-2 py-2">{{ $report->user?->mobile_number ?? '—' }}</td>
                                <td class="px-2 py-2">
                                    @if ($report->hasIntegrityIssues())
                                        <span class="text-red-600">{{ __('Needs review') }}</span>
                                    @else
                                        <span class="text-green-600">{{ __('OK') }}</span>
                                    @endif
                                </td>
                                <td class="px-2 py-2 text-end">
                                    <flux:button size="sm" variant="ghost" :href="route('admin.reports.show', $report)" wire:navigate>
                                        {{ __('Open') }}
                                    </flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-2 py-6 text-center text-zinc-500">{{ __('No reports yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </flux:card>
</div>
