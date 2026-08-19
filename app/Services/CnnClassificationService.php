<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CnnClassificationService
{
    /**
     * Classify an emergency image via the Python CNN service.
     *
     * @return array{label: string, confidence: float, probabilities: array<string, float>}
     */
    public function classify(UploadedFile $image): array
    {
        $baseUrl = rtrim((string) config('services.cnn.url'), '/');

        if ($baseUrl === '') {
            throw new RuntimeException('CNN_SERVICE_URL is not configured.');
        }

        try {
            $response = Http::timeout(60)
                ->attach(
                    'file',
                    file_get_contents($image->getRealPath()),
                    $image->getClientOriginalName() ?: 'emergency.jpg'
                )
                ->post("{$baseUrl}/predict");
        } catch (ConnectionException $e) {
            throw new RuntimeException('Unable to reach CNN classification service.', 0, $e);
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                'CNN classification failed (HTTP '.$response->status().'). Is the CNN service running on port 8001?'
            );
        }

        $data = $response->json();

        if (! isset($data['label'], $data['confidence'])) {
            throw new RuntimeException('Invalid CNN response payload.');
        }

        return [
            'label' => (string) $data['label'],
            'confidence' => (float) $data['confidence'],
            'margin' => isset($data['margin']) ? (float) $data['margin'] : null,
            'is_emergency' => array_key_exists('is_emergency', $data)
                ? (bool) $data['is_emergency']
                : in_array($data['label'], ['fire', 'medical', 'natural_disaster'], true),
            'probabilities' => $data['probabilities'] ?? [],
        ];
    }
}
