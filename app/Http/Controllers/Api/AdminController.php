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

        // 3. Evitar que el admin se quite su propio rol de admin (seguridad básica)
        if ($user->id === $request->user()->id && $request->role !== 'admin') {
             return response()->json(['message' => 'No puedes quitarte tu propio rol de administrador'], 400);
        }

        // 4. Sincronizar rol (quita los anteriores y pone el nuevo)
        // Si quieres que tenga múltiples roles, usa assignRole en vez de syncRoles
        $user->syncRoles([$request->role]);

        return response()->json([
            'message' => "Rol actualizado a {$request->role} correctamente",
            'user' => $user->load('roles')
        ]);
    }
}