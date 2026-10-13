<?php

namespace App\Http\Controllers;

use App\Models\EmergencyReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResponderEmergencyController extends Controller
{
    /**
     * List emergencies for this responder's LGU department.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $request->query('status', 'active');

        $query = EmergencyReport::query()
            ->with('user:id,mobile_number,name')
            ->where('confirmed_type', $user->department_type)
            ->latest();

        if ($status === 'active') {
            $query->whereIn('status', ['pending', 'responding']);
        } elseif (in_array($status, ['pending', 'responding', 'resolved', 'invalid'], true)) {
            $query->where('status', $status);
        }

        $reports = $query->get()->map(function (EmergencyReport $report) {
            return $this->reportPayload($report);
        });

        return response()->json([
            'success' => true,
            'department_type' => $user->department_type,
            'department_name' => $user->department_name,
            'reports' => $reports,
        ]);
    }

    /**
     * Show one department case.
     */
    public function show(Request $request, EmergencyReport $report): JsonResponse
    {
        if ($report->confirmed_type !== $request->user()->department_type) {
            return response()->json([
                'success' => false,
                'message' => 'This case is not under your department.',
            ], 403);
        }

        $report->load('user:id,mobile_number,name');

        return response()->json([
            'success' => true,
            'report' => $this->reportPayload($report, includeAdminNote: true),
        ]);
    }

    /**
     * Update case status (responding / resolved).
     * Resolving requires a resolution evidence image for admin authenticity review.
     */
    public function updateStatus(Request $request, EmergencyReport $report): JsonResponse
    {
        if ($report->confirmed_type !== $request->user()->department_type) {
            return response()->json([
                'success' => false,
                'message' => 'This case is not under your department.',
            ], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:responding,resolved'],
            'resolution_image' => ['required_if:status,resolved', 'nullable', 'image', 'max:10240'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $user = $request->user();
        $updates = [
            'status' => $validated['status'],
        ];

        if ($validated['status'] === 'responding') {
            $updates['assigned_responder_id'] = $user->id;
        }

        if ($validated['status'] === 'resolved') {
            $path = $request->file('resolution_image')->store('emergencies/resolutions', 'public');
            $updates['assigned_responder_id'] = $report->assigned_responder_id ?: $user->id;
            $updates['resolution_image_path'] = $path;
            $updates['authenticity_status'] = 'pending';
            $updates['authenticity_note'] = null;
            $updates['authenticity_reviewed_at'] = null;
            $updates['authenticity_reviewed_by'] = null;
        }

        $report->update($updates);

        if (isset($validated['latitude'], $validated['longitude'])) {
            $user->forceFill([
                'last_latitude' => $validated['latitude'],
                'last_longitude' => $validated['longitude'],
                'last_location_at' => now(),
            ])->save();
        }

        $fresh = $report->fresh()?->load('user:id,mobile_number,name') ?? $report;

        return response()->json([
            'success' => true,
            'message' => $validated['status'] === 'resolved'
                ? 'Case resolved. Resolution photo submitted for admin review.'
                : 'Case status updated.',
            'report' => $this->reportPayload($fresh, includeAdminNote: true),
        ]);
    }

    /**
     * Ping responder GPS for map routing / direction lines.
     */
    public function updateLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $user = $request->user();
        $user->forceFill([
            'last_latitude' => $validated['latitude'],
            'last_longitude' => $validated['longitude'],
            'last_location_at' => now(),
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Location updated.',
            'latitude' => (float) $user->last_latitude,
            'longitude' => (float) $user->last_longitude,
            'updated_at' => $user->last_location_at,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportPayload(EmergencyReport $report, bool $includeAdminNote = false): array
    {
        $payload = [
            'id' => $report->id,
            'confirmed_type' => $report->confirmed_type,
            'predicted_type' => $report->predicted_type,
            'confidence' => $report->confidence,
            'latitude' => $report->latitude,
            'longitude' => $report->longitude,
            'status' => $report->status,
            'image_url' => $report->imageUrl(),
            'resolution_image_url' => $report->resolutionImageUrl(),
            'authenticity_status' => $report->authenticity_status,
            'authenticity_note' => $report->authenticity_note,
            'assigned_responder_id' => $report->assigned_responder_id,
            'reporter_mobile' => $report->user?->mobile_number,
            'reporter_name' => $report->user?->name,
            'created_at' => $report->created_at,
            'updated_at' => $report->updated_at,
        ];

        if ($includeAdminNote) {
            $payload['admin_note'] = $report->admin_note;
            $payload['flagged'] = (bool) $report->flagged;
        }

        return $payload;
    }
}
