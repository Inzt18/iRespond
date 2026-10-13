<?php

use App\Models\RegistrationOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('citizen can request registration otp', function () {
    $response = $this->postJson('/api/register/send-otp', [
        'mobile_number' => '09171234567',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['expires_in', 'debug_otp']);

    expect(RegistrationOtp::where('mobile_number', '09171234567')->exists())->toBeTrue();
});

test('citizen cannot register without valid otp', function () {
    $this->postJson('/api/register', [
        'mobile_number' => '09171234567',
        'otp' => '123456',
    ])->assertStatus(422);
});

test('citizen can register after verifying otp', function () {
    $otp = '482913';

    RegistrationOtp::create([
        'mobile_number' => '09171234567',
        'code_hash' => Hash::make($otp),
        'attempts' => 0,
        'expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->postJson('/api/register', [
        'mobile_number' => '09171234567',
        'otp' => $otp,
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['token', 'user']);

    expect(User::where('mobile_number', '09171234567')->where('role', 'citizen')->exists())->toBeTrue();
});

test('registered mobile cannot request another registration otp', function () {
    User::factory()->create([
        'mobile_number' => '09171234567',
        'role' => 'citizen',
    ]);

    $this->postJson('/api/register/send-otp', [
        'mobile_number' => '09171234567',
    ])->assertStatus(422);
});
