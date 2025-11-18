<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController; // <-- 1. Importa el controlador
use App\Http\Controllers\Api\TicketController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- Rutas Públicas de Autenticación ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/signup', [AuthController::class, 'login']);

// --- Rutas Protegidas ---
// Todo lo que esté aquí dentro requerirá un token válido
Route::middleware('auth:sanctum')->group(function () {
    
    Route::post('/logout', [AuthController::class, 'logout']);

    // Ruta de ejemplo para probar que el token funciona
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        $user->role = $user->getRoleNames()->first(); 
        return $user;
    });

    /**
     * (NUEVO) Ruta para que un técnico vea SUS tickets asignados
     */
    Route::get('/my-tickets', [
        \App\Http\Controllers\Api\TicketController::class, 'myAssignedTickets'
    ]);

    // --- RUTAS DEL CRUD DE TICKETS ---
    
    // Ruta personalizada: POST /api/tickets/{ticket}/assign
    // Para que un técnico "tome" un ticket.
    Route::post('/tickets/{ticket}/assign', [
        \App\Http\Controllers\Api\TicketController::class, 'assign'
    ]);

    // Rutas de Recursos (CRUD estándar)
    // Esto crea automáticamente las rutas para:
    // index, store, show, update, destroy
    Route::apiResource('tickets', \App\Http\Controllers\Api\TicketController::class);
});