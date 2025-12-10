<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// RFID Module Routes
// TODO: Replace 'throttle:60,1' with appropriate middleware for API Key if Sanctum fails
Route::prefix('v1')->middleware(['throttle:60,1'])->group(function () {
    Route::post('/checkin/member', [\App\Http\Controllers\Api\RfidController::class, 'checkInMember']);
    Route::post('/checkin/staff', [\App\Http\Controllers\Api\RfidController::class, 'checkInStaff']);
    Route::get('/checkin/history/member/{id}', [\App\Http\Controllers\Api\RfidController::class, 'memberHistory']);
    Route::get('/checkin/history/staff/{id}', [\App\Http\Controllers\Api\RfidController::class, 'staffHistory']);
});
