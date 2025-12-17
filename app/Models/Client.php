<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
   protected $fillable = ['nombre', 'cedula', 'celular', 'direccion'];

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
