<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmergencyReportController;
use App\Http\Controllers\ResponderEmergencyController;
use App\Http\Controllers\ResponderHotlineController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Citizen endpoints
    Route::post('/emergencies/classify', [EmergencyReportController::class, 'classify']);
    Route::post('/emergencies', [EmergencyReportController::class, 'store']);
    Route::get('/emergencies', [EmergencyReportController::class, 'index']);
    Route::get('/responder-hotlines', [ResponderHotlineController::class, 'index']);

    // LGU responder endpoints
    Route::middleware('responder')->prefix('responder')->group(function () {
        Route::get('/emergencies', [ResponderEmergencyController::class, 'index']);
        Route::get('/emergencies/{report}', [ResponderEmergencyController::class, 'show']);
        Route::patch('/emergencies/{report}/status', [ResponderEmergencyController::class, 'updateStatus']);
    });
});
