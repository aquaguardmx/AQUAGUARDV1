<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QuizCurso;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $quizzes = QuizCurso::with('curso')->get();
        return response()->json($quizzes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'curso_id' => 'required|exists:cursos,id_curso',
            'titulo' => 'required|string|max:255',
            'preguntas' => 'array',
            'preguntas.*.texto_pregunta' => 'required|string',
            'preguntas.*.opciones' => 'array',
            'preguntas.*.opciones.*.texto_opcion' => 'required|string',
            'preguntas.*.opciones.*.es_correcta' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        DB::beginTransaction();

        try {
            $quiz = QuizCurso::create([
                'curso_id' => $request->curso_id,
                'titulo' => $request->titulo,
            ]);

            if ($request->has('preguntas')) {
                foreach ($request->preguntas as $preguntaData) {
                    $pregunta = $quiz->preguntas()->create([
                        'texto_pregunta' => $preguntaData['texto_pregunta'],
                        'tipo_pregunta' => $preguntaData['tipo_pregunta'] ?? 'opcion_multiple',
                    ]);

                    if (isset($preguntaData['opciones'])) {
                        foreach ($preguntaData['opciones'] as $opcionData) {
                            $pregunta->opciones()->create([
                                'texto_opcion' => $opcionData['texto_opcion'],
                                'es_correcta' => $opcionData['es_correcta'] ?? false,
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json($quiz->load('preguntas.opciones'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al crear el quiz: ' . $e->getMessage()], 500);
        }
    }

    // App/Http/Controllers/QuizController.php
public function showByCourse($curso_id)
{
    $quiz = QuizCurso::with(['preguntas.opciones'])
        ->where('curso_id', $curso_id)
        ->get();
    
    if ($quiz->isEmpty()) {
        return response()->json(['message' => 'No se encontró quiz para este curso'], 404);
    }
    
    return response()->json($quiz);
}

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $quiz = QuizCurso::with(['curso', 'preguntas.opciones'])->find($id);

        if (!$quiz) {
            return response()->json(['message' => 'Quiz no encontrado'], 404);
        }

        return response()->json($quiz);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $quiz = QuizCurso::find($id);

        if (!$quiz) {
            return response()->json(['message' => 'Quiz no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'curso_id' => 'exists:cursos,id_curso',
            'titulo' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $quiz->update($request->all());

        return response()->json($quiz);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $quiz = QuizCurso::find($id);

        if (!$quiz) {
            return response()->json(['message' => 'Quiz no encontrado'], 404);
        }

        $quiz->delete();

        return response()->json(['message' => 'Quiz eliminado correctamente']);
    }
}
