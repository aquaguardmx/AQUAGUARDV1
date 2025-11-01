<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Modulo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ModuleController extends Controller
{
    public function index()
    {
        $modulos = Modulo::all();

        if($modulos->isEmpty()) {
            return response()->json(['message' => 'No se encontraron módulos'], 404);
        }

        $data = [
            'modulos' => $modulos,
            'status' => 200
        ];

        return response()->json($modulos);
    } 

    public function show($id)
    {
        $modulo = Modulo::find($id);

        if (!$modulo) {
            return response()->json(['message' => 'Módulo no encontrado'], 404);
        }

        return response()->json($modulo);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'curso_id' => 'required|integer|exists:cursos,id_curso',
            'titulo' => 'required|string|max:255',
            'orden' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $modulo = Modulo::create([
            'curso_id' => $request->input('curso_id'),
            'titulo' => $request->input('titulo'),
            'orden' => $request->input('orden'),
        ]);

        return response()->json($modulo, 201);
    }

    public function update(Request $request, $id) {
        $modulo = Modulo::find($id);

        if (!$modulo) {
            return response()->json(['message' => 'Módulo no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'curso_id' => 'sometimes|required|integer|exists:cursos,id_curso',
            'titulo' => 'sometimes|required|string|max:255',
            'orden' => 'sometimes|required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $modulo->update($request->only(['curso_id', 'titulo', 'orden']));

        return response()->json($modulo);
    }

    public function destroy($id)
    {
        $modulo = Modulo::find($id);

        if (!$modulo) {
            return response()->json(['message' => 'Módulo no encontrado'], 404);
        }

        $modulo->delete();

        return response()->json(['message' => 'Módulo eliminado correctamente']);
    }
}