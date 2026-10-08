<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VittlesController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.attempt');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:3,1')->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [VittlesController::class, 'index'])->name('vittles.order');
    Route::get('/locations', [VittlesController::class, 'locations'])->name('vittles.locations');
    Route::post('/orders', [VittlesController::class, 'place'])->name('vittles.place');
    Route::get('/result', [VittlesController::class, 'result'])->name('vittles.result');
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
    Route::post('/admin/probe', [AdminController::class, 'probe'])->name('admin.probe');
});
