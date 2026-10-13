<?php

use App\Models\EmergencyReport;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('GIS Map')] class extends Component {
    #[Url]
    public string $status = 'active';

    #[Url]
    public string $type = '';

    #[Url]
    public bool $flaggedOnly = false;

    /**
     * @return list<array<string, mixed>>
     */
    public function markers(): array
    {
        $query = EmergencyReport::query()
            ->with(['user', 'assignedResponder'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->latest();

        if ($this->status === 'active') {
            $query->whereIn('status', ['pending', 'responding']);
        } elseif ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if ($this->type !== '') {
            $query->where('confirmed_type', $this->type);
        }

        if ($this->flaggedOnly) {
            $query->where('flagged', true);
        }

        $colors = [
            'fire' => '#c1121f',
            'medical' => '#2563eb',
            'natural_disaster' => '#ea580c',
        ];

        return $query->limit(500)->get()->map(function (EmergencyReport $report) use ($colors) {
            $responder = $report->assignedResponder;
            $hasResponderLocation = $responder
                && $responder->last_latitude !== null
                && $responder->last_longitude !== null;

            return [
                'id' => $report->id,
                'lat' => (float) $report->latitude,
                'lng' => (float) $report->longitude,
                'type' => $report->confirmed_type,
                'status' => $report->status,
                'color' => $colors[$report->confirmed_type] ?? '#737373',
                'label' => str_replace('_', ' ', $report->confirmed_type),
                'url' => route('admin.reports.show', $report),
                'flagged' => (bool) $report->flagged || $report->hasIntegrityIssues(),
                'mobile' => $report->user?->mobile_number ?? '—',
                'image_url' => $report->imageUrl(),
                'responder' => $hasResponderLocation ? [
                    'lat' => (float) $responder->last_latitude,
                    'lng' => (float) $responder->last_longitude,
                    'name' => $responder->name ?: ($responder->mobile_number ?? 'Responder'),
                    'updated_at' => $responder->last_location_at?->toIso8601String(),
                ] : null,
            ];
        })->values()->all();
    }

    public function with(): array
    {
        $markers = $this->markers();

        return [
            'markers' => $markers,
            'markerCount' => count($markers),
            'routeCount' => collect($markers)->whereNotNull('responder')->count(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-4 md:p-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="font-display text-xs font-semibold uppercase tracking-[0.18em] text-emergency">{{ __('GIS Overview') }}</p>
            <flux:heading size="xl" class="font-display mt-1 text-3xl tracking-wide">{{ __('Active Emergency Map') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Incident photo pins with responder direction lines when GPS is available.') }}</flux:text>
        </div>
        <div class="text-right text-sm">
            <flux:text>{{ __(':count mapped cases', ['count' => $markerCount]) }}</flux:text>
            <flux:text class="block">{{ __(':count active responder routes', ['count' => $routeCount]) }}</flux:text>
        </div>
    </div>

    <flux:card class="grid gap-3 md:grid-cols-4">
        <flux:select wire:model.live="status" :label="__('Status')">
            <option value="active">{{ __('Active (pending + responding)') }}</option>
            <option value="">{{ __('All statuses') }}</option>
            <option value="pending">pending</option>
            <option value="responding">responding</option>
            <option value="resolved">resolved</option>
            <option value="invalid">invalid</option>
        </flux:select>

        <flux:select wire:model.live="type" :label="__('Type')">
            <option value="">{{ __('All types') }}</option>
            <option value="fire">fire</option>
            <option value="medical">medical</option>
            <option value="natural_disaster">natural_disaster</option>
        </flux:select>

        <div class="flex items-end">
            <flux:checkbox wire:model.live="flaggedOnly" :label="__('Flagged / integrity issues only')" />
        </div>

        <div class="flex flex-wrap items-end gap-3 text-xs">
            <span class="inline-flex items-center gap-1"><span class="size-2.5 rounded-full bg-[#c1121f]"></span> Fire</span>
            <span class="inline-flex items-center gap-1"><span class="size-2.5 rounded-full bg-[#2563eb]"></span> Medical</span>
            <span class="inline-flex items-center gap-1"><span class="size-2.5 rounded-full bg-[#ea580c]"></span> Disaster</span>
            <span class="inline-flex items-center gap-1"><span class="size-2.5 rounded-full bg-[#0f766e]"></span> Responder</span>
        </div>
    </flux:card>

    <flux:card class="overflow-hidden p-0">
        <div
            wire:key="admin-gis-{{ md5(json_encode($markers)) }}"
            id="admin-gis-map"
            wire:ignore
            data-markers='@json($markers)'
            class="h-[32rem] w-full bg-zinc-200 dark:bg-zinc-800"
        ></div>
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
    .irespond-photo-pin.is-flagged {
        box-shadow: 0 0 0 3px #facc15, 0 2px 8px rgba(0, 0, 0, 0.35);
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
        const el = document.getElementById('admin-gis-map');
        if (!el || typeof L === 'undefined') return;

        if (window.__iRespondAdminMap) {
            window.__iRespondAdminMap.remove();
            window.__iRespondAdminMap = null;
        }

        const markers = JSON.parse(el.dataset.markers || '[]');
        const map = L.map(el).setView([14.5995, 120.9842], 11);
        window.__iRespondAdminMap = map;

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        const group = L.featureGroup();

        const photoIcon = (m) => L.divIcon({
            className: '',
            iconSize: [44, 44],
            iconAnchor: [22, 22],
            popupAnchor: [0, -22],
            html: m.image_url
                ? `<div class="irespond-photo-pin${m.flagged ? ' is-flagged' : ''}" style="--pin-color:${m.color};background-image:url('${m.image_url}')"></div>`
                : `<div class="irespond-photo-pin${m.flagged ? ' is-flagged' : ''}" style="--pin-color:${m.color};background:${m.color}"></div>`,
        });

        const responderIcon = L.divIcon({
            className: '',
            iconSize: [16, 16],
            iconAnchor: [8, 8],
            html: '<div class="irespond-responder-pin"></div>',
        });

        markers.forEach((m) => {
            const marker = L.marker([m.lat, m.lng], { icon: photoIcon(m) });

            marker.bindPopup(
                `<strong>#${m.id} ${m.label}</strong><br>` +
                `Status: ${m.status}<br>` +
                `Reporter: ${m.mobile}<br>` +
                (m.image_url ? `<img src="${m.image_url}" alt="" style="margin-top:6px;max-width:160px;border-radius:8px;" /><br>` : '') +
                `<a href="${m.url}">Open report</a>`
            );

            group.addLayer(marker);

            if (m.responder) {
                const r = m.responder;
                const responderMarker = L.marker([r.lat, r.lng], { icon: responderIcon });
                responderMarker.bindPopup(
                    `<strong>Responder</strong><br>${r.name}<br>Toward case #${m.id}`
                );
                group.addLayer(responderMarker);

                const line = L.polyline([[r.lat, r.lng], [m.lat, m.lng]], {
                    color: m.color,
                    weight: 3,
                    opacity: 0.85,
                    dashArray: '8 6',
                });
                group.addLayer(line);
            }
        });

        group.addTo(map);

        if (markers.length > 0) {
            map.fitBounds(group.getBounds().pad(0.25));
        }

        setTimeout(() => map.invalidateSize(), 150);
    })();
</script>
@endscript
