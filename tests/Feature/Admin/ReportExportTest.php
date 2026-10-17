<?php

use App\Models\EmergencyReport;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function makeReport(User $user, array $overrides = []): EmergencyReport
{
    return EmergencyReport::create(array_merge([
        'user_id' => $user->id,
        'image_path' => 'emergencies/test.jpg',
        'predicted_type' => 'fire',
        'confirmed_type' => 'fire',
        'confidence' => 0.99,
        'latitude' => 15.48,
        'longitude' => 120.97,
        'status' => 'pending',
    ], $overrides));
}

test('guests cannot export reports', function () {
    $this->get(route('admin.reports.export'))->assertRedirect(route('login'));
});

test('non-admins cannot export reports', function () {
    $this->actingAs(User::factory()->create(['role' => 'citizen']))
        ->get(route('admin.reports.export'))
        ->assertForbidden();
});

test('admin downloads a csv that respects the filters', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $citizen = User::factory()->create(['role' => 'citizen', 'mobile_number' => '09166423747']);

    makeReport($citizen, ['confirmed_type' => 'fire', 'status' => 'resolved']);
    makeReport($citizen, ['confirmed_type' => 'medical', 'predicted_type' => 'medical', 'status' => 'pending']);

    $response = $this->actingAs($admin)->get(route('admin.reports.export', ['type' => 'medical']));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
    expect($response->headers->get('content-disposition'))->toContain('emergency-reports-');

    $csv = $response->streamedContent();
    expect($csv)->toContain('Report ID');
    expect($csv)->toContain('medical');
    expect($csv)->not->toContain(',fire,');
    expect($csv)->toContain('="09166423747"');
});

test('export rejects invalid filter values', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.reports.export', ['status' => 'bogus']))
        ->assertSessionHasErrors('status');
});

test('admin can download a pdf report', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    makeReport(User::factory()->create(['role' => 'citizen']));

    $response = $this->actingAs($admin)->get(route('admin.reports.export-pdf'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('non-admins cannot download the pdf report', function () {
    $this->actingAs(User::factory()->create(['role' => 'citizen']))
        ->get(route('admin.reports.export-pdf'))
        ->assertForbidden();
});
