<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leccion;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LessonController extends Controller
{
    public function index()
    {
        $lecciones = Leccion::all();

        if($lecciones->isEmpty()) {
            return response()->json(['message' => 'No se encontraron lecciones'], 404);
        }

        $data = [
            'lecciones' => $lecciones,
            'status' => 200
        ];

        return response()->json($data);
    } 

    public function show($id)
    {
        $leccion = Leccion::find($id);

        if (!$leccion) {
            return response()->json(['message' => 'Lección no encontrada'], 404);
        }

        return response()->json($leccion);
    }

    public function store(Request $request)
    {
        // 1. Validar la entrada sin orden
        $validator = Validator::make($request->all(), [
            'modulo_id' => 'required|integer|exists:modulos,id_modulo',
            'titulo' => 'required|string|max:255',
            'tipo_contenido' => 'required|string|in:texto,video,video-texto',
            'contenido_texto' => 'nullable|string',
            'video_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 2. Obtener los datos validados
        $validatedData = $validator->validated();

        // 3. CALCULAR EL ORDEN (Esta es la lógica 'max + 1')
        // Buscamos el valor máximo (más alto) de 'orden' en este módulo
        $maxOrder = Leccion::where('modulo_id', $validatedData['modulo_id'])
                        ->max('orden');
    
        // El nuevo orden será (el máximo encontrado ?? 0) + 1.
        // Si no hay lecciones, $maxOrder será NULL. (NULL ?? 0) da 0.
        // Por lo tanto, la primera lección será 0 + 1 = 1.
        $validatedData['orden'] = ($maxOrder ?? 0) + 1;

        // 4. Crear la lección
        // Esto solo funciona si 'orden' está en el $fillable de tu modelo Leccion.php
        $leccion = Leccion::create($validatedData);

        // 5. Devolver la respuesta
        return response()->json([
            'message' => 'Lección creada exitosamente', 
            'leccion' => $leccion
        ], 201);
    }

}