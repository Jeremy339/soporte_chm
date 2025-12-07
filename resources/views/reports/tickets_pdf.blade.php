<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte de Tickets</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; color: #1e293b; }
        .header p { color: #64748b; margin: 5px 0; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e2e8f0; padding: 8px; text-align: left; }
        th { background-color: #f8fafc; color: #334155; font-weight: bold; }
        tr:nth-child(even) { background-color: #fdfdfc; }
        
        .badge { padding: 2px 6px; border-radius: 4px; font-size: 10px; color: white; display: inline-block; }
        .bg-pendiente { background-color: #ef4444; color: white; }
        .bg-revision { background-color: #f59e0b; color: white; }
        .bg-reparado { background-color: #10b981; color: white; }
        .bg-cerrado { background-color: #64748b; color: white; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Reporte de Tickets</h2>
        <p>Periodo: {{ $start_date }} al {{ $end_date }}</p>
        <p>Generado el: {{ $generated_at }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Dispositivo</th>
                <th>Usuario</th>
                <th>Estado</th>
                <th>Técnico</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tickets as $ticket)
            <tr>
                <td>#{{ $ticket->id }}</td>
                <td>
                    <b>{{ $ticket->tipo_dispositivo }}</b><br>
                    {{ $ticket->marca }} {{ $ticket->modelo }}
                </td>
                <td>
                    {{ $ticket->usuario->name ?? 'N/A' }}<br>
                    <small>{{ $ticket->usuario->email ?? '' }}</small>
                </td>
                <td>
                    @php
                        $color = match($ticket->estado_usuario) {
                            'pendiente' => 'bg-pendiente',
                            'en_revision' => 'bg-revision',
                            'reparado' => 'bg-reparado',
                            'cerrado' => 'bg-cerrado',
                            default => 'bg-cerrado'
                        };
                    @endphp
                    <span class="badge {{ $color }}">
                        {{ strtoupper(str_replace('_', ' ', $ticket->estado_usuario)) }}
                    </span>
                </td>
                <td>{{ $ticket->tecnico->name ?? 'Sin asignar' }}</td>
                <td>{{ $ticket->created_at->format('d/m/Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>