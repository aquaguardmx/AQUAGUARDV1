<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

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
        $curso = Curso::with('autor', 'categoria')->find($id);

        if (!$curso) {
            return response()->json(['message' => 'Curso no encontrado'], 404);
        }

        return response()->json($curso);
    }


    public function store(Request $request)
{
    $validated = $request->validate([
        'titulo' => 'required|string|max:255',
        'descripcion' => 'required|string',
        'autor_id' => 'required|integer',
        'categoria_id' => 'required|integer',
        'tiempo_estimado_min' => 'nullable|integer',
        'portada_url' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        'publicado' => 'boolean'
    ]);

    // Procesar la imagen si existe
    if ($request->hasFile('portada_url')) {
        $image = $request->file('portada_url');
        $imageName = time() . '_' . $image->getClientOriginalName();
        $imagePath = $image->storeAs('portadas', $imageName, 'public');
        $validated['portada_url'] = 'storage/' . $imagePath;
    }

    $curso = Curso::create($validated);

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

    public function getModulosPorCurso(Curso $curso)
    {
        // 1. Laravel ya encontró el curso y lo inyectó en la variable $curso.
        //    Si no lo encuentra, automáticamente devuelve un 404. ¡Magia!

        // 2. Gracias a la relación "modulos()" que definimos en el Modelo,
        //    podemos acceder a ellos como una propiedad.
        //    Eloquent/Laravel se encarga de hacer la consulta.
        $modulos = $curso->modulos;

        // 3. Devolvemos los módulos como JSON.
        return response()->json($modulos, 200);
    }

    public function getCursosPorUsuario($usuarioId)
    {
        try {
            // 1. Encontrar al usuario
            $user = User::find($usuarioId);

            if (!$user) {
                return response()->json(['message' => 'Usuario no encontrado'], 404);
            }

            // 2. Obtener los cursos
            // --- OPCIÓN A: Usando una relación (Recomendado) ---
            // Esto asume que en tu modelo User tienes una relación 'cursos()'
            $cursos = $user->cursos;

            /*
            // --- OPCIÓN B: Consultando la tabla pivote directamente ---
            // Úsala si no tienes la relación definida en el modelo.
            // (Basado en el modelo 'CursoInscritoUsuario' que has estado usando)

            // Obtenemos los IDs de los cursos en los que el usuario está inscrito
            $cursoIds = CursoInscritoUsuario::where('usuario_id', $usuarioId)
                                            ->pluck('curso_id');

            // Buscamos todos los cursos que coincidan con esos IDs
            $cursos = Curso::whereIn('id', $cursoIds)->get();
            */


            // 3. Devolver la respuesta
            return response()->json($cursos, 200);

        } catch (Exception $e) {
            // Manejo de cualquier error inesperado
            return response()->json([
                'message' => 'Error al obtener los cursos del usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}