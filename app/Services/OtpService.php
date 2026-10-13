<?php

namespace App\Services;

use App\Models\RegistrationOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public const CODE_LENGTH = 6;

    public const MAX_ATTEMPTS = 5;

    public function __construct(private SmsService $sms) {}

    /**
     * Generate and send a registration OTP for a new citizen mobile number.
     *
     * @return array{expires_in: int, debug_otp?: string}
     */
    public function sendRegistrationOtp(string $mobileNumber): array
    {
        if (User::where('mobile_number', $mobileNumber)->exists()) {
            throw ValidationException::withMessages([
                'mobile_number' => ['This mobile number is already registered.'],
            ]);
        }

        $code = $this->generateCode();
        $ttl = (int) config('services.otp.ttl_minutes', 10);

        RegistrationOtp::query()
            ->where('mobile_number', $mobileNumber)
            ->whereNull('verified_at')
            ->delete();

        RegistrationOtp::create([
            'mobile_number' => $mobileNumber,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes($ttl),
        ]);

        $sent = $this->sms->send(
            $mobileNumber,
            "Your iRespond verification code is {$code}. Valid for {$ttl} minutes."
        );

        if (! $sent) {
            throw ValidationException::withMessages([
                'mobile_number' => ['Unable to send OTP. Please try again later.'],
            ]);
        }

        $payload = [
            'expires_in' => $ttl * 60,
        ];

        if (config('services.otp.expose_in_response')) {
            $payload['debug_otp'] = $code;
        }

        return $payload;
    }

    /**
     * Verify OTP for registration. Marks the OTP row as verified on success.
     */
    public function verifyRegistrationOtp(string $mobileNumber, string $code): void
    {
        $otp = RegistrationOtp::query()
            ->where('mobile_number', $mobileNumber)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (! $otp) {
            throw ValidationException::withMessages([
                'otp' => ['No OTP found. Please request a new code.'],
            ]);
        }

        if ($otp->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'otp' => ['OTP has expired. Please request a new code.'],
            ]);
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            throw ValidationException::withMessages([
                'otp' => ['Too many invalid attempts. Please request a new code.'],
            ]);
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            throw ValidationException::withMessages([
                'otp' => ['Invalid OTP code.'],
            ]);
        }

        $otp->update(['verified_at' => now()]);
    }

    private function generateCode(): string
    {
        $max = (10 ** self::CODE_LENGTH) - 1;

        return str_pad((string) random_int(0, $max), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }
}
