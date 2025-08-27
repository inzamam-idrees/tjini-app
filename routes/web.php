<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('admin.home');
});

Route::get('/login', function () {
    return view('admin.auth.login');
});

Route::get('/dashboard', function () {
    return view('admin.home');
})->name('dashboard');