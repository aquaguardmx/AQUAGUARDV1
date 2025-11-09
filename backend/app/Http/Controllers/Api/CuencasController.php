<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cuencas;

class CuencasController extends Controller
{
    public function index()
    {
        $cuencas = Cuencas::select('id_cuenca', 'nombre')->get();;

        if($cuencas->isEmpty()) {
            return response()->json(['message' => 'No se encontraron cuencas'], 404);
        }

        return response()->json($cuencas);
    }
}
    