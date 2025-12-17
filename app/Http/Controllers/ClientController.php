<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function searchByCedula($cedula)
    {
        // Busca el primer cliente que coincida con la cédula
        $client = Client::where('cedula', $cedula)->first();

        if ($client) {
            return response()->json($client, 200);
        }

        // Si no existe, retornamos 404 para que el frontend sepa que debe habilitar los campos para escribir
        return response()->json(['message' => 'Cliente no encontrado'], 404);
    }
}
