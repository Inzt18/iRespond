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

        $confidence = (float) $result['confidence'];
        // 80% is a better demo/phone-photo balance than 85% (floods often land 70–84%).
        $minConfidence = 0.80;
        $label = (string) $result['label'];
        $isEmergency = (bool) ($result['is_emergency'] ?? in_array($label, self::TYPES, true));
        $margin = $result['margin'] ?? null;
        $minMargin = 0.10;
        $confidencePct = number_format($confidence * 100, 1);
        $minPct = number_format($minConfidence * 100, 0);

        // Reject unrelated / non-emergency photos (4th CNN class).
        if (! $isEmergency || $label === 'non_emergency') {
            Storage::disk('public')->delete($path);

            return response()->json([
                'success' => false,
                'message' => "Image does not appear to be an emergency (CNN top: {$label}, {$confidencePct}%). Please upload a clear emergency-related photo.",
                'confidence' => $confidence,
                'suggested_type' => $label,
                'probabilities' => $result['probabilities'],
            ], 422);
        }

        // Reject uncertain predictions (top classes too close).
        if ($margin !== null && (float) $margin < $minMargin) {
            Storage::disk('public')->delete($path);
            $marginPct = number_format(((float) $margin) * 100, 1);

            return response()->json([
                'success' => false,
                'message' => "Image classification is uncertain (top: {$label} {$confidencePct}%, margin: {$marginPct}%). Please upload a clearer emergency photo.",
                'confidence' => $confidence,
                'margin' => $margin,
                'suggested_type' => $label,
                'probabilities' => $result['probabilities'],
            ], 422);
        }

        if ($confidence < $minConfidence) {
            Storage::disk('public')->delete($path);

            return response()->json([
                'success' => false,
                'message' => "Image did not meet standard Confidence Level Percentage ({$confidencePct}% < {$minPct}% required). Please try again.",
                'confidence' => $confidence,
                'min_confidence' => $minConfidence,
                'suggested_type' => $label,
                'probabilities' => $result['probabilities'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Image classified successfully.',
            'temp_image_path' => $path,
            'suggested_type' => $label,
            'confidence' => $confidence,
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
            'confidence' => ['nullable', 'numeric', 'min:0.80', 'max:1'],
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
