<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;

// Route::get('/', function () {
//     return view('admin.home');
// });

// Route::get('/login', function () {
//     return view('admin.auth.login');
// });

// Route::get('/dashboard', function () {
//     return view('admin.home');
// })->name('dashboard');


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
    Route::get('/schools', [DashboardController::class, 'schools'])->name('schools');
    Route::get('/parents', [DashboardController::class, 'parents'])->name('parents');
    Route::get('/staff', [DashboardController::class, 'staff'])->name('staff');

    // Put the rest of your admin routes here.  For example:
    // Route::resource('/students', StudentController::class);
    // Route::resource('/teachers', TeacherController::class);

    // Logout route
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
});
