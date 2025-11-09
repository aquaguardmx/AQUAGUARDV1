<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tipos;

class TiposController extends Controller
{
    public function index()
    {
        $tipos = Tipos::select('id_tipo', 'nombre')->get();;

        if($tipos->isEmpty()) {
            return response()->json(['message' => 'No se encontraron tipos'], 404);
        }

        return response()->json($tipos);
    }
}
    