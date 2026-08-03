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
                'CNN classification failed: '.$response->body()
            );
        }

        $data = $response->json();

        if (! isset($data['label'], $data['confidence'])) {
            throw new RuntimeException('Invalid CNN response payload.');
        }

        return [
            'label' => (string) $data['label'],
            'confidence' => (float) $data['confidence'],
            'probabilities' => $data['probabilities'] ?? [],
        ];
    }
}
