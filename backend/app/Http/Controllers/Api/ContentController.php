<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contenido;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function index()
    {
        $contenidos = Contenido::all();

        if($contenidos->isEmpty()) {
            return response()->json(['message' => 'No se encontraron contenidos'], 404);
        }

        $data = [
            'contenidos' => $contenidos,
            'status' => 200
        ];

        return response()->json($data);
    } 

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titulo_leccion' => 'required|string|max:255',
            'contenido_leccion' => 'required|string',
            'video' => 'nullable|url',
            'orden' => 'required|integer',
            'id_curso' => 'required|integer|exists:cursos,id_curso',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $contenido = Contenido::create([
            'titulo_leccion' => $request->input('titulo_leccion'),
            'contenido_leccion' => $request->input('contenido_leccion'),
            'video' => $request->input('video'),
            'orden' => $request->input('orden'),
            'id_curso' => $request->input('id_curso'),
        ]);

        return response()->json(['message' => 'Contenido creado exitosamente', 'contenido' => $contenido], 201);
    }
}