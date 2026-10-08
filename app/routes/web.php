<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VittlesController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // GET /login: muestra el acceso a las cuentas locales de la demo.
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    // POST /login: valida credenciales con límite de intentos y abre la sesión web.
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.attempt');
    // GET /register: muestra el alta, que el controller restringe a loopback local.
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    // POST /register: crea una cuenta local con límite de intentos; no registra usuarios POS.
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:3,1')->name('register.store');
});

Route::middleware('auth')->group(function () {
    // POST /logout: invalida la sesión actual antes de volver al login.
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    // GET /: formulario de nueva orden y catálogo para elegir sede y productos.
    Route::get('/', [VittlesController::class, 'index'])->name('vittles.order');
    // GET /locations: muestra todas las sedes y el estado del menú de cada una.
    Route::get('/locations', [VittlesController::class, 'locations'])->name('vittles.locations');
    // GET /orders: historial local filtrable; no equivale a listar todo Vittles.
    Route::get('/orders', [VittlesController::class, 'orders'])->name('vittles.orders');
    // POST /orders: valida y envía una intención de compra al flujo idempotente compartido.
    Route::post('/orders', [VittlesController::class, 'place'])->name('vittles.place');
    // GET /orders/{clientRef}: detalle persistido de una compra confirmada por esta integración.
    Route::get('/orders/{clientRef}', [VittlesController::class, 'orderDetail'])->name('vittles.order-detail');
    // GET /result: resultado temporal de la última compra de esta sesión.
    Route::get('/result', [VittlesController::class, 'result'])->name('vittles.result');
    // GET /readme: explica el alcance del ejercicio y las exclusiones deliberadas.
    Route::get('/readme', [VittlesController::class, 'readme'])->name('vittles.readme');
    // GET /audit: recorre las discrepancias verificadas sin enviar requests al mock.
    Route::get('/audit', [VittlesController::class, 'audit'])->name('vittles.audit');
    // GET /admin: trazas redactadas; el controller exige administrador y mock local.
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
    // POST /admin/probe: prueba manual de endpoints del mock para auditar su contrato.
    Route::post('/admin/probe', [AdminController::class, 'probe'])->name('admin.probe');
});
