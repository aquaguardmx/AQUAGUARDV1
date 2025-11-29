<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProgresoLeccion;

class ProgresoLeccionController extends Controller
{
    public function index($usuario_id, $curso_id)
    {
        $progreso = ProgresoLeccion::where('usuario_id', $usuario_id)
            ->whereHas('leccion.modulo', function ($query) use ($curso_id) {
                $query->where('curso_id', $curso_id);
            })
            ->get();

        return response()->json($progreso);
    }
}