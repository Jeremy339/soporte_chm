<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf; // Importar la fachada de PDF
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    /**
     * Devuelve estadísticas JSON para mostrar gráficos en el Frontend (Expo).
     * Filtros: ?start_date=2024-01-01&end_date=2024-12-31
     */
    public function stats(Request $request)
    {
        if (!$request->user()->hasRole('admin')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Query base con filtros de fecha si existen
        $query = Ticket::query();
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        }

        // Contar por estado
        $stats = [
            'total' => $query->count(),
            'pendientes' => (clone $query)->where('estado_usuario', 'pendiente')->count(),
            'en_revision' => (clone $query)->where('estado_usuario', 'en_revision')->count(),
            'reparados' => (clone $query)->where('estado_usuario', 'reparado')->count(),
            'cerrados' => (clone $query)->where('estado_usuario', 'cerrado')->count(),
            'rango' => [
                'inicio' => $startDate,
                'fin' => $endDate
            ]
        ];

        return response()->json($stats);
    }

    /**
     * Genera y descarga un PDF con el reporte.
     */
    public function downloadPdf(Request $request)
    {
        if (!$request->user()->hasRole('admin')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Ticket::with(['usuario', 'tecnico']);

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        }
        
        $tickets = $query->latest()->get();
        
        // Datos para la vista
        $data = [
            'tickets' => $tickets,
            'start_date' => $startDate ?? 'Inicio',
            'end_date' => $endDate ?? 'Actualidad',
            'generated_at' => Carbon::now()->format('d/m/Y H:i')
        ];

        // Cargar vista y renderizar PDF
        // NOTA: Debes crear la vista resources/views/reports/tickets_pdf.blade.php
        $pdf = Pdf::loadView('reports.tickets_pdf', $data);

        // Opción A: Descargar directamente (stream)
        return $pdf->stream('reporte_tickets.pdf');
        
        // Opción B: Si Expo tiene problemas con stream, puedes devolver base64
        // return response()->json(['pdf_base64' => base64_encode($pdf->output())]);
    }
}