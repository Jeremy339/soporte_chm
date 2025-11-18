<?php

namespace App\Http\Controllers\Api;

// --- Imports de Eventos ---
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TicketController extends Controller
{
    /**
     * Muestra una lista de los tickets.
     * Lógica de Roles:
     * - Admin/Técnico: Ven todos los tickets.
     * - Usuario: Ve solo sus tickets.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Cargar relaciones para evitar N+1 queries
        $query = Ticket::with(['usuario', 'tecnico']);

        if ($user->hasRole(['admin', 'tecnico'])) {
            $tickets = $query->latest()->get();
        } else {
            $tickets = $query->where('user_id', $user->id)
                             ->latest()
                             ->get();
        }

        return response()->json($tickets, 200);
    }

    /**
     * Guarda un nuevo ticket creado.
     * Lógica de Roles:
     * - Cualquiera autenticado puede crear uno.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tipo_dispositivo'      => 'required|string|max:100',
            'marca'                 => 'required|string|max:100',
            'modelo'                => 'required|string|max:100',
            'numero_serie'          => 'nullable|string|max:100',
            'descripcion_problema'  => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ticket = Ticket::create([
            'user_id' => Auth::id(), // ID del usuario autenticado
            ...$validator->validated() // Añade todos los campos validados
        ]);

        return response()->json($ticket, 201);
    }

    /**
     * Muestra un ticket específico.
     * Lógica de Roles:
     * - Admin/Técnico: Ven cualquier ticket.
     * - Usuario: Ve solo su propio ticket.
     */
    public function show(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        // Si el usuario es el dueño del ticket, o es admin/tecnico
        if ($ticket->user_id === $user->id || $user->hasRole(['admin', 'tecnico'])) {
            // Cargar relaciones y devolver
            return response()->json($ticket->load(['usuario', 'tecnico']));
        }

        // Si no, no está autorizado
        return response()->json(['message' => 'No autorizado'], 403);
    }

    /**
     * Actualiza un ticket específico.
     * Lógica de Roles:
     * - Admin/Técnico: Pueden cambiar estados y prioridad.
     * - Admin (Solo): Puede re-asignar un técnico (cambiar 'tecnico_id').
     */
    public function update(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        // 1. Solo Admins o Técnicos pueden actualizar
        if (!$user->hasRole(['admin', 'tecnico'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // 2. Validación
        $validatedData = $request->validate([
            'estado_usuario' => 'sometimes|in:pendiente,en_revision,reparado,cerrado',
            'estado_interno' => 'sometimes|in:sin_iniciar,en_proceso,completado',
            'prioridad'      => 'sometimes|in:baja,media,alta',
            'tecnico_id'     => 'sometimes|integer|exists:users,id' // Validar que el ID exista
        ]);

        // 3. Lógica de Permisos (Re-asignación)
        if ($request->has('tecnico_id')) {
            if (!$user->hasRole('admin')) {
                unset($validatedData['tecnico_id']);
            }
        }
        
        // 4. Actualizar el ticket
        $ticket->update($validatedData);


        return response()->json($ticket->load(['usuario', 'tecnico']));
    }

    /**
     * Elimina un ticket específico.
     * Lógica de Roles:
     * - Admin (Solo): Puede eliminar un ticket.
     */
    public function destroy(Ticket $ticket)
    {
        // Solo el Admin puede borrar
        if (!Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $ticket->delete();

        return response()->json(null, 204);
    }

    // --- Métodos Personalizados ---

    /**
     * Asigna un ticket al técnico autenticado (a sí mismo).
     * Lógica de Roles:
     * - Admin/Técnico: Puede tomar un ticket que no esté asignado.
     */
    public function assign(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        // 1. Solo Admins o Técnicos
        if (!$user->hasRole(['admin', 'tecnico'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // 2. No se puede tomar un ticket ya asignado
        if ($ticket->tecnico_id) {
             return response()->json(['message' => 'Este ticket ya está asignado'], 409); // 409 = Conflict
        }
        
        // 3. Asignar y cambiar estados
        $ticket->tecnico_id = $user->id;
        $ticket->estado_usuario = 'en_revision';
        $ticket->estado_interno = 'en_proceso';
        $ticket->save();

        return response()->json($ticket->load(['usuario', 'tecnico']));
    }

    /**
     * (NUEVO) Muestra solo los tickets asignados al técnico autenticado.
     */
    public function myAssignedTickets(Request $request)
    {
        $user = $request->user();

        // 1. Solo para Técnicos o Admins
        if (!$user->hasRole(['tecnico', 'admin'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // 2. Usamos la relación que definimos en el modelo User.php
        $tickets = $user->ticketsAsignados()
                        ->with(['usuario', 'tecnico']) // Cargar relaciones
                        ->latest() // Ordenar por más nuevo
                        ->get();

        return response()->json($tickets, 200);
    }
}