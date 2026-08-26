<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::view('/dashboardcall', 'dashboardcall')->name('dashboardcall');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::livewire('dashboard', 'pages::admin.dashboard')->name('dashboard');
    Route::livewire('admin/reports', 'pages::admin.reports')->name('admin.reports');
    Route::livewire('admin/reports/{report}', 'pages::admin.report-show')->name('admin.reports.show');
    Route::livewire('admin/map', 'pages::admin.map')->name('admin.map');
    Route::livewire('admin/responders', 'pages::admin.responders')->name('admin.responders');
    Route::livewire('admin/hotlines', 'pages::admin.hotlines')->name('admin.hotlines');
    Route::livewire('admin/users', 'pages::admin.users')->name('admin.users');
    Route::livewire('admin/non-emergencies', 'pages::admin.non-emergencies')->name('admin.non-emergencies');
});

require __DIR__.'/settings.php';
