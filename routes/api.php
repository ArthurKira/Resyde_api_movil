<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HouseController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ResidenciaController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rutas públicas de autenticación
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    // Autenticación
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // Usuarios
    Route::apiResource('users', UserController::class);

    // Residencias
    Route::apiResource('residencias', ResidenciaController::class);

    // Recibos (Invoices)
    Route::prefix('recibos')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('recibos.index');
        Route::get('/{id}', [InvoiceController::class, 'show'])->name('recibos.show')->where('id', '[0-9]+');
        Route::post('/{id}/medidor', [InvoiceController::class, 'updateMedidor'])->name('recibos.medidor')->where('id', '[0-9]+');
    });

    // Departamentos (Houses)
    Route::prefix('departamentos')->group(function () {
        Route::get('/', [HouseController::class, 'index'])->name('departamentos.index');
        Route::get('/{id}', [HouseController::class, 'show'])->name('departamentos.show')->where('id', '[0-9]+');
    });

    // Residentes (Tenants)
    Route::prefix('residentes')->group(function () {
        Route::get('/', [TenantController::class, 'index'])->name('residentes.index');
        Route::get('/{id}', [TenantController::class, 'show'])->name('residentes.show')->where('id', '[0-9]+');
    });
});

// ============================================
// API REST PARA MÓVIL - SISTEMA DE ASISTENCIA
// ============================================
// Nota: Usa el login original /api/auth/login

// Rutas protegidas (requieren token Sanctum)
Route::middleware('auth:sanctum')->prefix('mobile')->group(function() {
    // Datos del usuario móvil (con información de personal y horario)
    Route::get('/user', [App\Http\Controllers\Api\MobileAuthController::class, 'user']);
    
    // Asistencia
    Route::get('/asistencia/estado', [App\Http\Controllers\Api\MobileAsistenciaController::class, 'estado']);
    Route::post('/asistencia/marcar-entrada', [App\Http\Controllers\Api\MobileAsistenciaController::class, 'marcarEntrada']);
    Route::post('/asistencia/marcar-salida', [App\Http\Controllers\Api\MobileAsistenciaController::class, 'marcarSalida']);
    Route::get('/asistencia/historial', [App\Http\Controllers\Api\MobileAsistenciaController::class, 'historial']);
});

