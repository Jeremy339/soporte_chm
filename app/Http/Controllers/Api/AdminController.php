<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    /**
     * Lista todos los usuarios con sus roles.
     * Se puede filtrar por rol ?role=tecnico
     */
    public function index(Request $request)
    {
        // Verificar permiso (Solo Admin)
        if (!$request->user()->hasRole('admin')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $query = User::with('roles');

        // Filtro opcional por rol
        if ($request->has('role')) {
            $query->role($request->role);
        }

        $users = $query->get();

        return response()->json($users);
    }

    /**
     * Cambia el rol de un usuario específico.
     * Body: { "role": "tecnico" }
     */
    public function changeRole(Request $request, User $user)
    {
        // 1. Verificar permiso (Solo Admin)
        if (!$request->user()->hasRole('admin')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // 2. Validar que el rol exista
        $request->validate([
            'role' => 'required|exists:roles,name',
        ]);

        // --- INICIO DE PROTECCIÓN SUPER ADMIN ---
        
        // A. Nadie puede cambiarle el rol al Usuario ID 1 (El Dueño)
        // (Ni siquiera otro admin)
        if ($user->id === 1) {
            return response()->json(['message' => 'ACCIÓN DENEGADA: No se puede modificar al Administrador Principal.'], 403);
        }

        // B. Evitar que un admin se quite su propio rol de admin por error
        if ($user->id === $request->user()->id && $request->role !== 'admin') {
             return response()->json(['message' => 'No puedes quitarte tu propio rol de administrador. Pídele a otro admin que lo haga.'], 400);
        }
        
        // --- FIN DE PROTECCIÓN ---

        // 3. Sincronizar rol (quita los anteriores y pone el nuevo)
        $user->syncRoles([$request->role]);

        return response()->json([
            'message' => "Rol actualizado a {$request->role} correctamente",
            'user' => $user->load('roles')
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        // 1. Verificar permiso
        if (!$request->user()->hasRole('admin')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // 2. PROTECCIÓN SUPER ADMIN (ID 1)
        if ($user->id === 1) {
            return response()->json(['message' => 'No se puede eliminar al usuario principal del sistema.'], 403);
        }

        // 3. PROTECCIÓN SUICIDIO (No borrarse a sí mismo)
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'No puedes eliminar tu propia cuenta mientras estás logueado.'], 400);
        }

        // 4. Eliminar
        $user->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente']);
    }
}