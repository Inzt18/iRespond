<?php

use App\Models\EmergencyReport;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Report Detail')] class extends Component {
    public EmergencyReport $report;

    public string $status = '';

    public string $admin_note = '';

    public bool $flagged = false;

    public string $flag_reason = '';

    public string $authenticity_status = '';

    public string $authenticity_note = '';

    public function mount(EmergencyReport $report): void
    {
        $this->report = $report->load(['user', 'reviewer', 'assignedResponder', 'authenticityReviewer']);
        $this->status = $report->status;
        $this->admin_note = (string) ($report->admin_note ?? '');
        $this->flagged = (bool) $report->flagged;
        $this->flag_reason = (string) ($report->flag_reason ?? '');
        $this->authenticity_status = (string) ($report->authenticity_status ?? '');
        $this->authenticity_note = (string) ($report->authenticity_note ?? '');
    }

    public function saveReview(): void
    {
        $validated = $this->validate([
            'status' => ['required', 'in:pending,responding,resolved,invalid'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'flagged' => ['boolean'],
            'flag_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->report->update([
            'status' => $validated['status'],
            'admin_note' => $validated['admin_note'] ?: null,
            'flagged' => $validated['flagged'],
            'flag_reason' => $validated['flagged']
                ? ($validated['flag_reason'] ?: 'manual')
                : null,
            'reviewed_at' => now(),
            'reviewed_by' => Auth::id(),
        ]);

        $this->report->refresh()->load(['user', 'reviewer', 'assignedResponder', 'authenticityReviewer']);

        Flux::toast(variant: 'success', text: __('Report review saved.'));
    }

    public function saveAuthenticityReview(): void
    {
        if (! $this->report->resolution_image_path) {
            Flux::toast(variant: 'danger', text: __('No resolution photo to review.'));

            return;
        }

        $validated = $this->validate([
            'authenticity_status' => ['required', 'in:pending,approved,rejected'],
            'authenticity_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->report->update([
            'authenticity_status' => $validated['authenticity_status'],
            'authenticity_note' => $validated['authenticity_note'] ?: null,
            'authenticity_reviewed_at' => now(),
            'authenticity_reviewed_by' => Auth::id(),
        ]);

        $this->report->refresh()->load(['user', 'reviewer', 'assignedResponder', 'authenticityReviewer']);

        Flux::toast(variant: 'success', text: __('Resolution authenticity review saved.'));
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <flux:heading size="xl">{{ __('Report #:id', ['id' => $report->id]) }}</flux:heading>
                <flux:text class="mt-1">
                    {{ __('Submitted :when', [
                        'when' => $report->created_at?->timezone(config('app.timezone'))->format('M d, Y g:i A'),
                    ]) }}
                </flux:text>
            </div>
            <flux:button variant="ghost" :href="route('admin.reports')" wire:navigate>
                {{ __('Back to list') }}
            </flux:button>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <flux:card class="space-y-3">
                <flux:heading size="lg">{{ __('Citizen Evidence Image') }}</flux:heading>
                @if ($report->image_path)
                    <img
                        src="{{ Storage::disk('public')->url($report->image_path) }}"
                        alt="Emergency evidence"
                        class="max-h-96 w-full rounded-lg object-contain bg-zinc-100 dark:bg-zinc-900"
                    />
                @else
                    <flux:text>{{ __('No image attached.') }}</flux:text>
                @endif
            </flux:card>

            <flux:card class="space-y-3">
                <flux:heading size="lg">{{ __('Classification & Location') }}</flux:heading>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4"><dt>{{ __('Confirmed') }}</dt><dd class="font-medium">{{ $report->confirmed_type }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>{{ __('CNN predicted') }}</dt><dd class="font-medium">{{ $report->predicted_type ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>{{ __('Confidence') }}</dt><dd class="font-medium">{{ $report->confidence !== null ? number_format($report->confidence * 100, 1).'%' : '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>{{ __('Reporter') }}</dt><dd class="font-medium">{{ $report->user?->mobile_number ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>{{ __('Assigned responder') }}</dt><dd class="font-medium">{{ $report->assignedResponder?->name ?: ($report->assignedResponder?->mobile_number ?? '—') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>{{ __('Latitude') }}</dt><dd class="font-medium">{{ $report->latitude ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>{{ __('Longitude') }}</dt><dd class="font-medium">{{ $report->longitude ?? '—' }}</dd></div>
                </dl>

                @if ($report->latitude && $report->longitude)
                    <div
                        wire:ignore
                        id="report-detail-map"
                        data-lat="{{ $report->latitude }}"
                        data-lng="{{ $report->longitude }}"
                        data-type="{{ $report->confirmed_type }}"
                        data-image="{{ $report->imageUrl() }}"
                        data-responder-lat="{{ $report->assignedResponder?->last_latitude }}"
                        data-responder-lng="{{ $report->assignedResponder?->last_longitude }}"
                        data-responder-name="{{ $report->assignedResponder?->name ?: ($report->assignedResponder?->mobile_number ?? 'Responder') }}"
                        class="h-56 w-full overflow-hidden rounded-lg bg-zinc-200 dark:bg-zinc-800"
                    ></div>
                    <flux:button
                        size="sm"
                        target="_blank"
                        :href="'https://www.openstreetmap.org/?mlat='.$report->latitude.'&mlon='.$report->longitude.'#map=16/'.$report->latitude.'/'.$report->longitude"
                    >
                        {{ __('Open in OpenStreetMap') }}
                    </flux:button>
                @endif

                @php $issues = $report->integrityIssues(); @endphp
                @if (count($issues))
                    <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300">
                        <strong>{{ __('Auto integrity signals:') }}</strong>
                        {{ implode(', ', $issues) }}
                    </div>
                @endif
            </flux:card>
        </div>

        <flux:card class="space-y-4">
            <flux:heading size="lg">{{ __('Resolution Photo Authenticity') }}</flux:heading>

            @if ($report->resolution_image_path)
                <img
                    src="{{ Storage::disk('public')->url($report->resolution_image_path) }}"
                    alt="Resolution evidence"
                    class="max-h-80 w-full rounded-lg object-contain bg-zinc-100 dark:bg-zinc-900"
                />

                <form wire:submit="saveAuthenticityReview" class="grid gap-4 md:grid-cols-2">
                    <flux:select wire:model="authenticity_status" :label="__('Authenticity status')" required>
                        <option value="pending">pending</option>
                        <option value="approved">approved</option>
                        <option value="rejected">rejected</option>
                    </flux:select>

                    <div class="md:col-span-2">
                        <flux:textarea wire:model="authenticity_note" :label="__('Authenticity note')" rows="3" />
                    </div>

                    <div class="md:col-span-2">
                        <flux:button type="submit" variant="primary">{{ __('Save authenticity review') }}</flux:button>
                    </div>
                </form>

                @if ($report->authenticity_reviewed_at)
                    <flux:text class="text-xs">
                        {{ __('Authenticity reviewed :when by :who', [
                            'when' => $report->authenticity_reviewed_at,
                            'who' => $report->authenticityReviewer?->mobile_number ?? $report->authenticityReviewer?->name ?? 'admin',
                        ]) }}
                    </flux:text>
                @endif
            @else
                <flux:text>{{ __('No resolution photo uploaded yet. Responders must attach one when marking a case resolved.') }}</flux:text>
            @endif
        </flux:card>

        <flux:card class="space-y-4">
            <flux:heading size="lg">{{ __('Admin Review') }}</flux:heading>

            <form wire:submit="saveReview" class="grid gap-4 md:grid-cols-2">
                <flux:select wire:model="status" :label="__('Status')" required>
                    <option value="pending">pending</option>
                    <option value="responding">responding</option>
                    <option value="resolved">resolved</option>
                    <option value="invalid">invalid</option>
                </flux:select>

                <flux:input wire:model="flag_reason" :label="__('Flag reason')" placeholder="manual / low_confidence / ..." />

                <div class="md:col-span-2">
                    <flux:textarea wire:model="admin_note" :label="__('Admin note')" rows="4" />
                </div>

                <div class="md:col-span-2">
                    <flux:checkbox wire:model="flagged" :label="__('Flag for integrity review')" />
                </div>

                <div class="md:col-span-2">
                    <flux:button type="submit" variant="primary">{{ __('Save review') }}</flux:button>
                </div>
            </form>

            @if ($report->reviewed_at)
                <flux:text class="text-xs">
                    {{ __('Last reviewed :when by :who', [
                        'when' => $report->reviewed_at,
                        'who' => $report->reviewer?->mobile_number ?? $report->reviewer?->name ?? 'admin',
                    ]) }}
                </flux:text>
            @endif
        </flux:card>
</div>

@assets
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<style>
    .irespond-photo-pin {
        width: 44px;
        height: 44px;
        border-radius: 9999px;
        border: 3px solid var(--pin-color, #c1121f);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.35);
        overflow: hidden;
        background: #fff center/cover no-repeat;
    }
    .irespond-responder-pin {
        width: 16px;
        height: 16px;
        border-radius: 9999px;
        background: #0f766e;
        border: 2px solid #fff;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.4);
    }
</style>
@endassets

@script
<script>
    (() => {
        const el = document.getElementById('report-detail-map');
        if (!el || typeof L === 'undefined') return;

        const lat = parseFloat(el.dataset.lat);
        const lng = parseFloat(el.dataset.lng);
        const type = el.dataset.type || '';
        const imageUrl = el.dataset.image || '';
        const responderLat = parseFloat(el.dataset.responderLat);
        const responderLng = parseFloat(el.dataset.responderLng);
        const responderName = el.dataset.responderName || 'Responder';
        const colors = {
            fire: '#c1121f',
            medical: '#2563eb',
            natural_disaster: '#ea580c',
        };
        const color = colors[type] || '#c1121f';

        if (window.__iRespondReportMap) {
            window.__iRespondReportMap.remove();
            window.__iRespondReportMap = null;
        }

        const map = L.map(el).setView([lat, lng], 16);
        window.__iRespondReportMap = map;

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        const incidentIcon = L.divIcon({
            className: '',
            iconSize: [44, 44],
            iconAnchor: [22, 22],
            html: imageUrl
                ? `<div class="irespond-photo-pin" style="--pin-color:${color};background-image:url('${imageUrl}')"></div>`
                : `<div class="irespond-photo-pin" style="--pin-color:${color};background:${color}"></div>`,
        });

        const group = L.featureGroup();
        group.addLayer(L.marker([lat, lng], { icon: incidentIcon }));

        if (!Number.isNaN(responderLat) && !Number.isNaN(responderLng)) {
            const responderIcon = L.divIcon({
                className: '',
                iconSize: [16, 16],
                iconAnchor: [8, 8],
                html: '<div class="irespond-responder-pin"></div>',
            });
            const rMarker = L.marker([responderLat, responderLng], { icon: responderIcon });
            rMarker.bindPopup(`<strong>${responderName}</strong>`);
            group.addLayer(rMarker);
            group.addLayer(L.polyline([[responderLat, responderLng], [lat, lng]], {
                color,
                weight: 3,
                opacity: 0.85,
                dashArray: '8 6',
            }));
        }

        group.addTo(map);
        map.fitBounds(group.getBounds().pad(0.35));

        setTimeout(() => map.invalidateSize(), 150);
    })();
</script>
@endscript
