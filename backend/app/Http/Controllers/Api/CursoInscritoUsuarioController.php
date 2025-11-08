<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CursoInscritoUsuario;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CursoInscritoUsuarioController extends Controller
{
    public function index()
    {
        $cursosInscritos = CursoInscritoUsuario::all();

        if($cursosInscritos->isEmpty()) {
            return response()->json(['message' => 'No se encontraron cursos inscritos'], 404);
        }

        $data = [
            'cursos_inscritos' => $cursosInscritos,
            'status' => 200
        ];

        return response()->json($cursosInscritos);
    } 

    public function store(Request $request)
    {
        $validated = $request->validate([
            'usuario_id' => 'required|integer',
            'curso_id' => 'required|integer',
        ]);

        // Verificar duplicados manualmente
        $existe = CursoInscritoUsuario::where('usuario_id', $validated['usuario_id'])
            ->where('curso_id', $validated['curso_id'])
            ->exists();

        if ($existe) {
            return response()->json([
                'message' => 'El usuario ya está inscrito en este curso'
            ], 409);
        }

        $cursoInscrito = CursoInscritoUsuario::create($validated);

        return response()->json($cursoInscrito, 201);
    }
}