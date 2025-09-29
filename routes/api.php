<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NotificationController;

// Public routes for registration and login
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected route for logout (requires authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    // Parent response endpoint: authenticated parents call this to notify viewers/dispatchers
    Route::post('/parent/respond', [NotificationController::class, 'parentRespond']);
});

Route::get('/test', function (Request $request) {
    return 'Testing API';
});