<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Parametros;

class ParametrosController extends Controller
{
    public function index()
    {
        $parametros = Parametros::select('id_parametro', 'nombre', 'unidad', 'descripcion', 'definicion', 'contaminantes_contribuyentes')->get();

        if($parametros->isEmpty()) {
            return response()->json(['message' => 'No se encontraron parámetros'], 404);
        }

        return response()->json($parametros);
    }
}
    