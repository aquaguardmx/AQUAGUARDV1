<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Municipios;

class MunicipiosController extends Controller
{
    public function getMunicipiosPorEstado($estadoId)
    {
        $municipios = Municipios::where('id_estado', $estadoId)
            ->select('id_municipio', 'nombre')
            ->get();

        if($municipios->isEmpty()) {
            return response()->json(['message' => 'No se encontraron municipios'], 404);
        }

        return response()->json($municipios);
    }
}
    