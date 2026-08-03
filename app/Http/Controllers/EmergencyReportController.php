<?php

namespace App\Http\Controllers;

use App\Models\EmergencyReport;
use App\Services\CnnClassificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class EmergencyReportController extends Controller
{
    private const TYPES = ['fire', 'medical', 'natural_disaster'];

    public function __construct(
        private readonly CnnClassificationService $cnn
    ) {}

    /**
     * Classify an uploaded emergency image.
     */
    public function classify(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:10240'],
        ]);

        $image = $request->file('image');
        $path = $image->store('emergencies/temp', 'public');

        try {
            $result = $this->cnn->classify($image);
        } catch (Throwable $e) {
            Storage::disk('public')->delete($path);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Classification failed.',
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Image classified successfully.',
            'temp_image_path' => $path,
            'suggested_type' => $result['label'],
            'confidence' => $result['confidence'],
            'probabilities' => $result['probabilities'],
        ]);
    }

    /**
     * Submit a confirmed emergency report.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'confirmed_type' => ['required', 'in:'.implode(',', self::TYPES)],
            'predicted_type' => ['nullable', 'in:'.implode(',', self::TYPES)],
            'confidence' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'temp_image_path' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:10240'],
        ]);

        $imagePath = null;

        if (! empty($validated['temp_image_path'])) {
            $tempPath = $validated['temp_image_path'];

            if (! Storage::disk('public')->exists($tempPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Temporary image not found. Please re-upload.',
                ], 422);
            }

            $filename = basename($tempPath);
            $finalPath = 'emergencies/'.$filename;
            Storage::disk('public')->move($tempPath, $finalPath);
            $imagePath = $finalPath;
        } elseif ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('emergencies', 'public');
        } else {
            return response()->json([
                'success' => false,
                'message' => 'An emergency image is required.',
            ], 422);
        }

        $report = EmergencyReport::create([
            'user_id' => $request->user()->id,
            'image_path' => $imagePath,
            'predicted_type' => $validated['predicted_type'] ?? null,
            'confirmed_type' => $validated['confirmed_type'],
            'confidence' => $validated['confidence'] ?? null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Emergency report submitted successfully.',
            'report' => $report,
        ], 201);
    }

    /**
     * List the authenticated user's emergency reports.
     */
    public function index(Request $request): JsonResponse
    {
        $reports = EmergencyReport::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(function (EmergencyReport $report) {
                return [
                    'id' => $report->id,
                    'confirmed_type' => $report->confirmed_type,
                    'predicted_type' => $report->predicted_type,
                    'confidence' => $report->confidence,
                    'latitude' => $report->latitude,
                    'longitude' => $report->longitude,
                    'status' => $report->status,
                    'image_url' => Storage::disk('public')->url($report->image_path),
                    'created_at' => $report->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'reports' => $reports,
        ]);
    }
}
