<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SchoolController;


// If someone visits the root URL, redirect to the login page or dashboard.
Route::get('/', function () {
    // If the user is authenticated, redirect to dashboard, otherwise show login.
    /** @var \Illuminate\Contracts\Auth\Guard $auth */
    $auth = auth();
    return $auth->check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('admin.login');
});

// Routes for guests (not logged in)
Route::middleware('guest')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

// Routes for authenticated users (dashboard)
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/schools', [SchoolController::class, 'index'])->name('schools');
    Route::get('/schools/create', [SchoolController::class, 'create'])->name('schools.create');
    Route::post('/schools', [SchoolController::class, 'store'])->name('schools.store');
    Route::get('/schools/{id}/edit', [SchoolController::class, 'edit'])->name('schools.edit');
    Route::put('/schools/{id}', [SchoolController::class, 'update'])->name('schools.update');
    Route::delete('/schools/{id}', [SchoolController::class, 'destroy'])->name('schools.destroy');

    Route::get('/parents', [DashboardController::class, 'parents'])->name('parents');
    Route::get('/staff', [DashboardController::class, 'staff'])->name('staff');

    // Put the rest of your admin routes here.  For example:
    // Route::resource('/students', StudentController::class);
    // Route::resource('/teachers', TeacherController::class);

    // Logout route
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
});
