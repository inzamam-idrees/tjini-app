<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ParentController;
use App\Models\User;

// Public routes for registration and login
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::get('/user/{id}', function ($id) {
    return User::find($id);
});
// Protected route for logout (requires authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    // Unified notification endpoint for parent and dispatcher actions
    Route::post('/notify/users', [NotificationController::class, 'notifyUsers']);
    Route::get('/notifications', [NotificationController::class, 'listNotifications']);
    // Related parents lookup
    Route::post('/parents/related', [ParentController::class, 'relatedParents']);
});

Route::get('/test', function (Request $request) {
    return 'Testing API';
});