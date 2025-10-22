<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        $cursos = Curso::all();

        if($cursos->isEmpty()) {
            return response()->json(['message' => 'No se encontraron cursos'], 404);
        }

        $data = [
            'cursos' => $cursos,
            'status' => 200
        ];

        return response()->json($cursos);
    } 

    public function show(string $id)
    {
        // Usamos with() para cargar la relación 'contenidos'
        // El nombre 'contenidos' DEBE COINCIDIR con la función del Paso 1.
        $curso = Curso::with('contenidos')->find($id); // O findOrFail($id)

        if (!$curso) {
            return response()->json(['message' => 'Curso no encontrado'], 404);
        }
    
        return $curso; // O return new CursoResource($curso);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre_curso' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'nivel_dificultad' => 'required|string|in:principiante,intermedio,avanzado',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $curso = Curso::create([
            'nombre_curso' => $request->input('nombre_curso'),
            'descripcion' => $request->input('descripcion'),
            'nivel_dificultad' => $request->input('nivel_dificultad'),
        ]);

        return response()->json(['message' => 'Curso creado exitosamente', 'curso' => $curso], 201);
    }

    public function destroy($id)
    {
        $curso = Curso::find($id);

        if (!$curso) {
            return response()->json(['message' => 'Curso no encontrado'], 404);
        }

        $curso->delete();

        return response()->json(['message' => 'Curso eliminado exitosamente'], 200);
    }

    public function showContents() 
    {
        
    }
}