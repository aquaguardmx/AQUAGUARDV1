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
        // 1. VALIDACIÓN
        $validator = Validator::make($request->all(), [
            'curso_id' => 'required|integer|exists:cursos,id_curso',
            'titulo'   => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 2. OBTENER DATOS
        $curso_id = $request->input('curso_id');
        $titulo = $request->input('titulo');

        // 3. --- LÓGICA CLAVE (MÉTODO 1) ---
        // Contamos cuántos módulos existen para este curso_id
        $conteoActual = Modulo::where('curso_id', $curso_id)->count();

        // Calculamos el nuevo orden (conteo + 1)
        $nuevoOrden = $conteoActual + 1;

        // 4. CREAR EL MÓDULO
        $modulo = Modulo::create([
            'curso_id' => $curso_id,
            'titulo'   => $titulo,
            'orden'    => $nuevoOrden, // <-- Aquí usamos el orden calculado
        ]);

        // 5. RESPUESTA
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

    /**
     * NUEVO MÉTODO: Obtener todos los módulos y sus lecciones por ID de curso.
     */
    public function showByCurso($cursoId)
    {
        // 1. Buscamos todos los módulos para el curso_id dado.
        // 2. Los ordenamos por la columna 'orden'.
        // 3. Usamos 'with' para cargar la relación 'lecciones' (Eager Loading).
        // 4. Dentro de 'with', también ordenamos las lecciones anidadas por su 'orden'.
        $modulos = Modulo::where('curso_id', $cursoId)
                         ->orderBy('orden', 'asc')
                         ->with(['lecciones' => function($query) {
                             $query->orderBy('orden', 'asc');
                         }])
                         ->get();

        // Verificamos si encontramos módulos.
        // Si la colección está vacía, puede ser que el curso no exista
        // o simplemente no tenga módulos asignados.
        if ($modulos->isEmpty()) {
            return response()->json(['message' => 'No se encontraron módulos para este curso'], 404);
        }

        // Devolvemos la colección completa de módulos con sus lecciones anidadas.
        return response()->json($modulos);
    }

    public function getPorModulo($moduloId)
    {
        try {
            // 1. Encuentra el módulo
            $modulo = Modulo::findOrFail($moduloId);

            // 2. Obtiene las lecciones de ese módulo, ordenadas
            $lecciones = $modulo->lecciones()->orderBy('orden', 'asc')->get();

            // 3. Devuelve las lecciones como JSON
            return response()->json($lecciones);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Manejo de error si el módulo no se encuentra
            return response()->json(['message' => 'Módulo no encontrado'], 404);
        }
    }
}