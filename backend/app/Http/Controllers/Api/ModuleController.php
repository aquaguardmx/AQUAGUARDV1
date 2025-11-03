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
        //    Quitamos 'orden' de los campos requeridos.
        $validator = Validator::make($request->all(), [
            'curso_id' => 'required|integer|exists:cursos,id_curso',
            'titulo'   => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 2. OBTENER DATOS
        //    Obtenemos solo los datos validados del request.
        $curso_id = $request->input('curso_id');
        $titulo = $request->input('titulo');

        // 3. --- LÓGICA CLAVE (MÉTODO 1) ---
        //  Contamos cuántos módulos existen para este curso_id
        $conteoActual = Modulo::where('curso_id', $curso_id)->count();

        //    Calculamos el nuevo orden (conteo + 1)
        $nuevoOrden = $conteoActual + 1;

        // 4. CREAR EL MÓDULO
        //    Usamos las variables, incluyendo nuestro 'nuevoOrden' calculado
        $modulo = Modulo::create([
            'curso_id' => $curso_id,
            'titulo'   => $titulo,
            'orden'    => $nuevoOrden, // <-- Aquí usamos el orden calculado
        ]);

        // 5. RESPUESTA
        //  Devolvemos el módulo recién creado
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

    public function getPorModulo($moduloId)
    {
        try {
            // 1. Encuentra el módulo
            $modulo = Modulo::findOrFail($moduloId);

            $lecciones = $modulo->lecciones()->orderBy('orden', 'asc')->get();

            // 3. Devuelve las lecciones como JSON
            return response()->json($lecciones);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Manejo de error si el módulo no se encuentra
            return response()->json(['message' => 'Módulo no encontrado'], 404);
        }
    }
}