<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CourseController extends Controller
{
    public function index()
    {
        $cursos = Curso::with('autor', 'categoria')->get();

        if($cursos->isEmpty()) {
            return response()->json(['message' => 'No se encontraron cursos'], 404);
        }

        $data = [
            'cursos' => $cursos,
            'status' => 200
        ];

        return response()->json($cursos);
    } 

    public function show($id)
    {
        $curso = Curso::find($id);

        if (!$curso) {
            return response()->json(['message' => 'Curso no encontrado'], 404);
        }

        return response()->json($curso);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string|max:255',
            // 'portada_url' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048', // Correcto
            'portada_url' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'tiempo_estimado_min' => 'nullable|integer',
            'autor_id' => 'required|integer|exists:usuarios,id_usuario', // Correcto
            'categoria_id' => 'required|integer|exists:categorias,id',
            'publicado' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // --- ¡AQUÍ ESTÁ LA CORRECCIÓN! ---
        
        $urlDeLaPortada = null; // 1. Inicializa la variable

        if ($request->hasFile('portada_url')) {
            // 2. Guarda el archivo en 'storage/app/public/portadas'
            $path = $request->file('portada_url')->store('portadas', 'public');

            // 3. Obtiene la URL pública
            $urlDeLaPortada = Storage::url($path);
        }
        
        // --- FIN DE LA CORRECCIÓN ---

        $curso = Curso::create([
            'titulo' => $request->input('titulo'),
            'descripcion' => $request->input('descripcion'),
            
            // 4. Asigna la URL generada
            'portada_url' => $urlDeLaPortada, 
            
            'tiempo_estimado_min' => $request->input('tiempo_estimado_min'),
            'autor_id' => $request->input('autor_id'),
            'categoria_id' => $request->input('categoria_id'),
            'publicado' => $request->input('publicado'),
        ]);

        return response()->json($curso, 201);
    }

    public function update(Request $request, $id)
    {
        $curso = Curso::find($id);
        
        if (!$curso) {
            return response()->json(['message' => 'Curso no encontrado'], 404);
        }
    
        // 🔹 Validaciones: todos los campos son opcionales ("sometimes")
        $validator = Validator::make($request->all(), [
            'titulo' => 'sometimes|string|max:255',
            'portada_url' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'descripcion' => 'sometimes|string',
            'tiempo_estimado_min' => 'sometimes|nullable|integer',
            'autor_id' => 'sometimes|integer|exists:usuarios,id_usuario',
            'categoria_id' => 'sometimes|integer|exists:categorias,id',
            'publicado' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 🔹 Solo actualiza los campos enviados en la request
        $curso->update($request->only([
            'titulo',
            'descripcion',
            'portada_url',
            'tiempo_estimado_min',
            'autor_id',
            'categoria_id',
            'publicado',
        ]));

        return response()->json([
            'message' => 'Curso actualizado correctamente',
            'curso' => $curso
        ]);
    }

    public function destroy($id)
    {
        $curso = Curso::find($id);

        if (!$curso) {
            return response()->json(['message' => 'Curso no encontrado'], 404);
        }

        $curso->delete();

        return response()->json(['message' => 'Curso eliminado correctamente']);
    }
}