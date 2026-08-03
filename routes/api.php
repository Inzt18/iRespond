<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmergencyReportController;
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

    Route::post('/emergencies/classify', [EmergencyReportController::class, 'classify']);
    Route::post('/emergencies', [EmergencyReportController::class, 'store']);
    Route::get('/emergencies', [EmergencyReportController::class, 'index']);
    Route::get('/responder-hotlines', [ResponderHotlineController::class, 'index']);
});
