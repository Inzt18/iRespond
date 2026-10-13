<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send an SMS message. Returns true when accepted by the provider (or logged in local mode).
     */
    public function send(string $mobileNumber, string $message): bool
    {
        $driver = (string) config('services.sms.driver', 'log');

        return match ($driver) {
            'semaphore' => $this->sendViaSemaphore($mobileNumber, $message),
            default => $this->sendViaLog($mobileNumber, $message),
        };
    }

    private function sendViaLog(string $mobileNumber, string $message): bool
    {
        Log::info('SMS (log driver)', [
            'to' => $mobileNumber,
            'message' => $message,
        ]);

        return true;
    }

    private function sendViaSemaphore(string $mobileNumber, string $message): bool
    {
        $apiKey = (string) config('services.sms.semaphore.api_key');
        $sender = config('services.sms.semaphore.sender');

        if ($apiKey === '') {
            Log::warning('Semaphore SMS skipped: missing API key.');

            return false;
        }

        $payload = [
            'apikey' => $apiKey,
            'number' => $mobileNumber,
            'message' => $message,
        ];

        if (filled($sender)) {
            $payload['sendername'] = $sender;
        }

        $response = Http::asForm()
            ->timeout(15)
            ->post('https://api.semaphore.co/api/v4/messages', $payload);

        if (! $response->successful()) {
            Log::error('Semaphore SMS failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }
}
