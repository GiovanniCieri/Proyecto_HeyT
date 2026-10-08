<?php

use App\Http\Controllers\VittlesController;
use Illuminate\Support\Facades\Route;

Route::get('/', [VittlesController::class, 'index'])->name('vittles.order');
Route::get('/locations', [VittlesController::class, 'locations'])->name('vittles.locations');
Route::post('/orders', [VittlesController::class, 'place'])->name('vittles.place');
Route::get('/result', [VittlesController::class, 'result'])->name('vittles.result');
