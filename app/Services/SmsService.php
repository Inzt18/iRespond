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
            'to' => $this->normalizePhNumber($mobileNumber),
            'message' => $message,
        ]);

        return true;
    }

    /**
     * Normalize PH numbers for Semaphore: 639XXXXXXXXX.
     */
    public function normalizePhNumber(string $mobileNumber): string
    {
        $digits = preg_replace('/\D+/', '', $mobileNumber) ?? '';

        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            return $digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '63'.substr($digits, 1);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            return '63'.$digits;
        }

        return $digits;
    }

    private function sendViaSemaphore(string $mobileNumber, string $message): bool
    {
        $apiKey = (string) config('services.sms.semaphore.api_key');
        $sender = config('services.sms.semaphore.sender');
        $number = $this->normalizePhNumber($mobileNumber);

        if ($apiKey === '') {
            Log::warning('Semaphore SMS skipped: missing API key.');

            return false;
        }

        if (strlen($number) !== 12 || ! str_starts_with($number, '63')) {
            Log::warning('Semaphore SMS skipped: invalid PH number.', [
                'input' => $mobileNumber,
                'normalized' => $number,
            ]);

            return false;
        }

        $payload = [
            'apikey' => $apiKey,
            'number' => $number,
            'message' => $message,
        ];

        if (filled($sender)) {
            $payload['sendername'] = $sender;
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(20)
                ->post('https://api.semaphore.co/api/v4/messages', $payload);
        } catch (\Throwable $e) {
            Log::error('Semaphore SMS exception', [
                'to' => $number,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        $body = $response->json();

        if (! $response->successful()) {
            Log::error('Semaphore SMS failed', [
                'to' => $number,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        // Semaphore may return HTTP 200 with an error object.
        if (is_array($body) && isset($body['message']) && ! isset($body[0])) {
            Log::error('Semaphore SMS rejected payload', [
                'to' => $number,
                'body' => $body,
            ]);

            return false;
        }

        Log::info('Semaphore SMS accepted', [
            'to' => $number,
            'message_id' => is_array($body) ? ($body[0]['message_id'] ?? null) : null,
        ]);

        return true;
    }
}
