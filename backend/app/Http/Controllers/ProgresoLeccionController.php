<?php

namespace App\Http\Controllers;

use App\Models\ProgresoLeccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProgresoLeccionController extends Controller
{
    /**
     * Store or update the progress of a lesson.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'usuario_id' => 'required|exists:usuarios,id_usuario',
            'leccion_id' => 'required|exists:lecciones,id_leccion',
            'fecha_completado' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $progreso = ProgresoLeccion::updateOrCreate(
            [
                'usuario_id' => $request->usuario_id,
                'leccion_id' => $request->leccion_id,
            ],
            [
                'fecha_completado' => $request->fecha_completado ?? now(),
            ]
        );

        return response()->json([
            'message' => 'Progreso guardado exitosamente',
            'data' => $progreso
        ], 200);
    }

    /**
     * Get progress for a specific user.
     */
    public function index($usuario_id)
    {
        $progreso = ProgresoLeccion::where('usuario_id', $usuario_id)->get();

        return response()->json($progreso);
    }
    
    /**
     * Check if a specific lesson is completed by a user.
     */
    public function show($usuario_id, $leccion_id)
    {
        $progreso = ProgresoLeccion::where('usuario_id', $usuario_id)
            ->where('leccion_id', $leccion_id)
            ->first();

        if (!$progreso) {
            return response()->json(['completed' => false], 200);
        }

        return response()->json(['completed' => true, 'data' => $progreso], 200);
    }
}
