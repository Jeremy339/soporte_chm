<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tipo_dispositivo',
        'marca',
        'modelo',
        'numero_serie',
        'descripcion_problema',
        'tecnico_id',
        'estado_usuario',
        'estado_interno',
        'prioridad',
    ];

    /// Relaciones
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tecnico()
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }
}
