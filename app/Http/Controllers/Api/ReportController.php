<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    /**
     * Devuelve estadísticas JSON para mostrar gráficos en el Frontend (Expo).
     * Filtros: ?start_date=2024-01-01&end_date=2024-12-31&status=pendiente
     */
    public function stats(Request $request)
    {
        if (!$request->user()->hasRole('admin')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $status = $request->input('status'); // <--- Nuevo filtro

        // Query base
        $query = Ticket::query();

        // 1. Filtro de Fecha
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        }

        // 2. Filtro de Estado (Si se envía y no está vacío)
        if ($status && $status !== '') {
            $query->where('estado_usuario', $status);
        }

        // Contar por estado (Nota: si filtras por estado, los otros contadores darán 0, lo cual es correcto)
        $stats = [
            'total' => $query->count(),
            'pendientes' => (clone $query)->where('estado_usuario', 'pendiente')->count(),
            'en_revision' => (clone $query)->where('estado_usuario', 'en_revision')->count(),
            'reparados' => (clone $query)->where('estado_usuario', 'reparado')->count(),
            'cerrados' => (clone $query)->where('estado_usuario', 'cerrado')->count(),
            'rango' => [
                'inicio' => $startDate,
                'fin' => $endDate
            ],
            'filtro_estado' => $status ?? 'todos'
        ];

        return response()->json($stats);
    }

    /**
     * Genera y descarga un PDF con el reporte filtrado.
     */
    public function downloadPdf(Request $request)
    {
        if (!$request->user()->hasRole('admin')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $status = $request->input('status'); // <--- Nuevo filtro

        $query = Ticket::with(['cliente', 'tecnico']);

        // 1. Filtro de Fecha
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        }

        // 2. Filtro de Estado
        if ($status && $status !== '') {
            $query->where('estado_usuario', $status);
        }
        
        $tickets = $query->latest()->get();
        
        // Datos para la vista
        $data = [
            'tickets' => $tickets,
            'start_date' => $startDate ?? 'Inicio',
            'end_date' => $endDate ?? 'Actualidad',
            'status_filter' => $status ? ucfirst(str_replace('_', ' ', $status)) : 'Todos', // Para mostrar en el título del PDF
            'generated_at' => Carbon::now()->format('d/m/Y H:i')
        ];

        $pdf = Pdf::loadView('reports.tickets_pdf', $data);

        return $pdf->stream('reporte_tickets.pdf');
    }
}