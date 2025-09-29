<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserImportController;

// If someone visits the root URL, redirect to the login page or dashboard.
Route::get('/', function () {
    // If the user is authenticated, redirect to dashboard, otherwise show login.
    /** @var \Illuminate\Contracts\Auth\Guard $auth */
    $auth = auth();
    return $auth->check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('login');
});

// Routes for guests (not logged in)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

// Routes for authenticated users (dashboard)
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('schools')->name('schools.')->group(function () {
        Route::get('/', [SchoolController::class, 'index'])->name('index');
        Route::get('/create', [SchoolController::class, 'create'])->name('create');
        Route::post('/', [SchoolController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [SchoolController::class, 'edit'])->name('edit');
        Route::put('/{id}', [SchoolController::class, 'update'])->name('update');
        Route::delete('/{id}', [SchoolController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/{role}', [UserController::class, 'index'])->name('index');
        Route::get('/{role}/create', [UserController::class, 'create'])->name('create');
        Route::post('/{role}', [UserController::class, 'store'])->name('store');
        Route::get('/{role}/{id}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{role}/{id}', [UserController::class, 'update'])->name('update');
        Route::delete('/{role}/{id}', [UserController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('user')->name('user.')->group(function () {
        // Import routes
        Route::get('/import', [UserImportController::class, 'showImportForm'])->name('import.form');
        Route::post('/import', [UserImportController::class, 'import'])->name('import');
        Route::get('/import/template', [UserImportController::class, 'downloadTemplate'])->name('import.template');
        Route::get('/import/progress/{batchId}', [UserImportController::class, 'checkProgress'])->name('import.progress');
    });

    // Logout route
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
});
