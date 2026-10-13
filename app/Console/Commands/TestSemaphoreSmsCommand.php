<?php

namespace App\Console\Commands;

use App\Services\OtpService;
use App\Services\SmsService;
use Illuminate\Console\Command;

class TestSemaphoreSmsCommand extends Command
{
    protected $signature = 'irespond:test-sms
                            {mobile : PH mobile number (09XXXXXXXXX)}
                            {--otp : Also create/send a real registration OTP via OtpService}';

    protected $description = 'Send a test SMS (or registration OTP) through the configured SMS driver — use this on Hostinger without Flutter.';

    public function handle(SmsService $sms, OtpService $otp): int
    {
        $mobile = (string) $this->argument('mobile');
        $driver = (string) config('services.sms.driver', 'log');
        $normalized = $sms->normalizePhNumber($mobile);

        $this->info("SMS driver: {$driver}");
        $this->info("Normalized number: {$normalized}");

        if ($driver === 'semaphore' && blank(config('services.sms.semaphore.api_key'))) {
            $this->error('SEMAPHORE_API_KEY is empty. Add it to Hostinger .env first.');

            return self::FAILURE;
        }

        if ($this->option('otp')) {
            try {
                $result = $otp->sendRegistrationOtp($mobile);
                $this->info('Registration OTP requested successfully.');
                $this->line('expires_in: '.$result['expires_in'].' seconds');
                if (isset($result['debug_otp'])) {
                    $this->warn('debug_otp (should be off in production): '.$result['debug_otp']);
                } else {
                    $this->info('OTP was sent to the phone (not exposed in API response).');
                }

                return self::SUCCESS;
            } catch (\Throwable $e) {
                $this->error('OTP send failed: '.$e->getMessage());

                return self::FAILURE;
            }
        }

        $ok = $sms->send(
            $mobile,
            'iRespond test SMS: Semaphore is working. You can ignore this message.'
        );

        if (! $ok) {
            $this->error('SMS send failed. Check storage/logs/laravel.log for Semaphore errors.');

            return self::FAILURE;
        }

        $this->info($driver === 'log'
            ? 'Logged test SMS (SMS_DRIVER=log). Switch to semaphore for real delivery.'
            : 'Test SMS accepted by Semaphore. Check the phone Messages app.');

        return self::SUCCESS;
    }
}
