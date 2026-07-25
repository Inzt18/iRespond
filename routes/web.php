<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::view('/dashboardcall', 'dashboardcall')->name('dashboardcall');
});

require __DIR__.'/settings.php';