<?php

use App\Models\EmergencyReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Report Analytics')] class extends Component {
    #[Url]
    public string $period = 'month';

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function range(): array
    {
        $now = now();

        return match ($this->period) {
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek(), 'day'],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear(), 'month'],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), 'day'],
        };
    }

    private function bucketExpression(string $granularity): string
    {
        $driver = DB::connection()->getDriverName();

        if ($granularity === 'month') {
            return $driver === 'sqlite'
                ? "strftime('%Y-%m', created_at)"
                : "DATE_FORMAT(created_at, '%Y-%m')";
        }

        return $driver === 'sqlite'
            ? "strftime('%Y-%m-%d', created_at)"
            : 'DATE(created_at)';
    }

    public function with(): array
    {
        [$start, $end, $granularity] = $this->range();

        $base = EmergencyReport::query()
            ->whereBetween('created_at', [$start, $end]);

        $total = (clone $base)->count();

        $byType = (clone $base)
            ->selectRaw('confirmed_type, COUNT(*) as total')
            ->groupBy('confirmed_type')
            ->pluck('total', 'confirmed_type');

        $typeMeta = [
            'fire' => ['label' => 'Fire', 'color' => '#c1121f'],
            'medical' => ['label' => 'Medical', 'color' => '#2563eb'],
            'natural_disaster' => ['label' => 'Natural Disaster', 'color' => '#ea580c'],
        ];

        $typeStats = [];
        foreach ($typeMeta as $key => $meta) {
            $count = (int) ($byType[$key] ?? 0);
            $typeStats[$key] = [
                'label' => $meta['label'],
                'color' => $meta['color'],
                'count' => $count,
                'percent' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
            ];
        }

        $sorted = collect($typeStats)->sortByDesc('count');
        $topType = $sorted->first();
        $topTypeKey = $sorted->keys()->first();
        $hasLeader = $total > 0 && ($topType['count'] ?? 0) > 0;

        $bucketExpr = $this->bucketExpression($granularity);
        $trendRows = (clone $base)
            ->selectRaw("{$bucketExpr} as bucket, confirmed_type, COUNT(*) as total")
            ->groupBy('bucket', 'confirmed_type')
            ->orderBy('bucket')
            ->get();

        $countsByBucket = [];
        foreach ($trendRows as $item) {
            $bucket = (string) $item->bucket;
            $type = (string) $item->confirmed_type;
            $countsByBucket[$bucket][$type] = (int) $item->total;
        }

        // App uses CarbonImmutable — addDay/addMonth return new instances.
        $buckets = [];
        if ($granularity === 'day') {
            $cursor = $start->toMutable()->startOfDay();
            $last = $end->toMutable()->startOfDay();
            $guard = 0;
            while ($cursor->lte($last) && $guard < 400) {
                $buckets[] = $cursor->format('Y-m-d');
                $cursor->addDay();
                $guard++;
            }
        } else {
            $cursor = $start->toMutable()->startOfMonth();
            $last = $end->toMutable()->startOfMonth();
            $guard = 0;
            while ($cursor->lte($last) && $guard < 24) {
                $buckets[] = $cursor->format('Y-m');
                $cursor->addMonth();
                $guard++;
            }
        }

        $trend = [];
        foreach ($buckets as $bucket) {
            $types = [
                'fire' => (int) ($countsByBucket[$bucket]['fire'] ?? 0),
                'medical' => (int) ($countsByBucket[$bucket]['medical'] ?? 0),
                'natural_disaster' => (int) ($countsByBucket[$bucket]['natural_disaster'] ?? 0),
            ];

            $trend[] = [
                'key' => $bucket,
                'label' => $granularity === 'day'
                    ? Carbon::parse($bucket)->format('M j')
                    : Carbon::createFromFormat('Y-m', $bucket)->format('M Y'),
                'total' => array_sum($types),
                'types' => $types,
            ];
        }

        $maxTrend = max(1, collect($trend)->max('total') ?? 1);

        $periodLabel = match ($this->period) {
            'week' => 'This week',
            'year' => 'This year',
            default => 'This month',
        };

        return [
            'total' => $total,
            'typeStats' => $typeStats,
            'topType' => $topType,
            'topTypeKey' => $topTypeKey,
            'hasLeader' => $hasLeader,
            'trend' => $trend,
            'maxTrend' => $maxTrend,
            'periodLabel' => $periodLabel,
            'rangeLabel' => $start->timezone(config('app.timezone'))->format('M j, Y')
                .' – '.$end->timezone(config('app.timezone'))->format('M j, Y'),
            'typeMeta' => $typeMeta,
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-4 md:p-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="font-display text-xs font-semibold uppercase tracking-[0.18em] text-emergency">{{ __('Insights') }}</p>
            <flux:heading size="xl" class="font-display mt-1 text-3xl tracking-wide">{{ __('Report Analytics') }}</flux:heading>
            <flux:text class="mt-1">
                {{ __('Volume and emergency-type breakdown for :period.', ['period' => strtolower($periodLabel)]) }}
            </flux:text>
        </div>

        <div class="w-full max-w-xs">
            <flux:select wire:model.live="period" :label="__('Period')">
                <option value="week">{{ __('This week') }}</option>
                <option value="month">{{ __('This month') }}</option>
                <option value="year">{{ __('This year') }}</option>
            </flux:select>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <flux:card class="space-y-1">
            <flux:text>{{ __('Reports · :period', ['period' => $periodLabel]) }}</flux:text>
            <flux:heading size="xl">{{ $total }}</flux:heading>
            <flux:text class="text-xs">{{ $rangeLabel }}</flux:text>
        </flux:card>

        <flux:card class="space-y-1 sm:col-span-2 xl:col-span-3">
            <flux:text>{{ __('Most reported type') }}</flux:text>
            @if ($hasLeader)
                <flux:heading size="xl" class="capitalize">
                    {{ $topType['label'] }}
                </flux:heading>
                <flux:text>
                    {{ __(':count of :total reports (:percent%)', [
                        'count' => $topType['count'],
                        'total' => $total,
                        'percent' => $topType['percent'],
                    ]) }}
                </flux:text>
            @else
                <flux:heading size="xl">{{ __('No data yet') }}</flux:heading>
                <flux:text>{{ __('No emergency reports in this period.') }}</flux:text>
            @endif
        </flux:card>
    </div>

    <flux:card>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <flux:heading size="lg">{{ __('By Emergency Type') }}</flux:heading>
            <flux:button size="sm" variant="ghost" :href="route('admin.reports')" wire:navigate>
                {{ __('Open reports') }}
            </flux:button>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            @foreach ($typeStats as $key => $stat)
                <a
                    href="{{ route('admin.reports', ['type' => $key]) }}"
                    wire:navigate
                    class="block rounded-xl border p-4 transition hover:bg-zinc-50 {{ $hasLeader && $topTypeKey === $key ? 'border-emergency/40 bg-emergency/5 ring-1 ring-emergency/20' : 'border-zinc-200' }}"
                >
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="inline-block size-2.5 rounded-full" style="background: {{ $stat['color'] }}"></span>
                            <flux:text class="font-medium text-zinc-900">{{ $stat['label'] }}</flux:text>
                        </div>
                        @if ($hasLeader && $topTypeKey === $key)
                            <span class="rounded-full bg-emergency/10 px-2 py-0.5 text-xs font-semibold text-emergency">
                                {{ __('Most') }}
                            </span>
                        @endif
                    </div>

                    <flux:heading size="xl">{{ $stat['count'] }}</flux:heading>
                    <flux:text class="mt-1 text-xs">{{ __(':percent% of period', ['percent' => $stat['percent']]) }}</flux:text>

                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-zinc-100">
                        <div
                            class="h-full rounded-full transition-all"
                            style="width: {{ $stat['percent'] }}%; background: {{ $stat['color'] }}"
                        ></div>
                    </div>

                    <flux:text class="mt-3 text-xs text-emergency">{{ __('View :type reports →', ['type' => strtolower($stat['label'])]) }}</flux:text>
                </a>
            @endforeach
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg" class="mb-1">{{ __('Report volume over time') }}</flux:heading>
        <flux:text class="mb-4 text-sm">{{ __('Stacked by emergency type for :period.', ['period' => strtolower($periodLabel)]) }}</flux:text>

        @if ($total === 0)
            <div class="py-10 text-center text-sm text-zinc-500">{{ __('No reports to chart in this period.') }}</div>
        @else
            <div class="flex items-end gap-2 overflow-x-auto pb-2" style="min-height: 220px">
                @foreach ($trend as $point)
                    @php
                        $columnHeight = $maxTrend > 0 ? ($point['total'] / $maxTrend) * 100 : 0;
                    @endphp
                    <div class="flex min-w-10 flex-1 flex-col items-center gap-2">
                        <div class="flex h-44 w-full max-w-14 flex-col justify-end">
                            @if ($point['total'] > 0)
                                <div
                                    class="flex w-full flex-col justify-end overflow-hidden rounded-sm"
                                    style="height: {{ max($columnHeight, 6) }}%"
                                    title="{{ __('Total: :count', ['count' => $point['total']]) }}"
                                >
                                    @foreach (['fire', 'medical', 'natural_disaster'] as $typeKey)
                                        @php $segment = $point['types'][$typeKey]; @endphp
                                        @if ($segment > 0)
                                            <div
                                                class="w-full"
                                                title="{{ $typeMeta[$typeKey]['label'] }}: {{ $segment }}"
                                                style="height: {{ ($segment / $point['total']) * 100 }}%; background: {{ $typeMeta[$typeKey]['color'] }}"
                                            ></div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="text-center">
                            <div class="text-[10px] font-medium text-zinc-600">{{ $point['label'] }}</div>
                            <div class="text-[10px] text-zinc-400">{{ $point['total'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 flex flex-wrap gap-4 text-xs text-zinc-600">
                @foreach ($typeMeta as $meta)
                    <div class="flex items-center gap-2">
                        <span class="inline-block size-2.5 rounded-full" style="background: {{ $meta['color'] }}"></span>
                        {{ $meta['label'] }}
                    </div>
                @endforeach
            </div>
        @endif
    </flux:card>
</div>
