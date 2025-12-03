<?php

namespace App\Http\Controllers;

use App\Models\QuizResultado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QuizResultadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $resultados = QuizResultado::with(['quiz', 'usuario'])
            ->orderBy('fecha_intento', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $resultados
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'quiz_id' => 'required|integer|exists:quizzes,id_quiz',
            'usuario_id' => 'required|integer|exists:usuarios,id_usuario',
            'calificacion' => 'required|numeric|min:0|max:100',
            'fecha_intento' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        
        // Si no se envía fecha_intento, usar la fecha actual
        if (!isset($data['fecha_intento'])) {
            $data['fecha_intento'] = now();
        }

        $resultado = QuizResultado::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Resultado de quiz creado exitosamente',
            'data' => $resultado->load(['quiz', 'usuario'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $resultado = QuizResultado::with(['quiz', 'usuario'])->find($id);

        if (!$resultado) {
            return response()->json([
                'success' => false,
                'message' => 'Resultado de quiz no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $resultado
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $resultado = QuizResultado::find($id);

        if (!$resultado) {
            return response()->json([
                'success' => false,
                'message' => 'Resultado de quiz no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'quiz_id' => 'sometimes|required|integer|exists:quizzes,id_quiz',
            'usuario_id' => 'sometimes|required|integer|exists:usuarios,id_usuario',
            'calificacion' => 'sometimes|required|numeric|min:0|max:100',
            'fecha_intento' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $resultado->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Resultado de quiz actualizado exitosamente',
            'data' => $resultado->fresh(['quiz', 'usuario'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $resultado = QuizResultado::find($id);

        if (!$resultado) {
            return response()->json([
                'success' => false,
                'message' => 'Resultado de quiz no encontrado'
            ], 404);
        }

        $resultado->delete();

        return response()->json([
            'success' => true,
            'message' => 'Resultado de quiz eliminado exitosamente'
        ]);
    }

    /**
     * Obtener resultados por usuario
     */
    public function resultadosPorUsuario($usuarioId)
    {
        $resultados = QuizResultado::with(['quiz'])
            ->where('usuario_id', $usuarioId)
            ->orderBy('fecha_intento', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $resultados
        ]);
    }

    /**
     * Obtener resultados por quiz
     */
    public function resultadosPorQuiz($quizId)
    {
        $resultados = QuizResultado::with(['usuario'])
            ->where('quiz_id', $quizId)
            ->orderBy('calificacion', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $resultados
        ]);
    }

    /**
     * Obtener estadísticas de un usuario
     */
    public function estadisticasUsuario($usuarioId)
    {
        $estadisticas = QuizResultado::where('usuario_id', $usuarioId)
            ->selectRaw('COUNT(*) as total_quizzes')
            ->selectRaw('AVG(calificacion) as promedio_calificacion')
            ->selectRaw('MAX(calificacion) as mejor_calificacion')
            ->selectRaw('MIN(calificacion) as peor_calificacion')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $estadisticas
        ]);
    }

    /**
     * Obtener el último intento de un usuario para un quiz específico
     */
    public function ultimoIntento($usuarioId, $quizId)
    {
        $ultimoIntento = QuizResultado::with(['quiz'])
            ->where('usuario_id', $usuarioId)
            ->where('quiz_id', $quizId)
            ->orderBy('fecha_intento', 'desc')
            ->first();

        if (!$ultimoIntento) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron intentos para este quiz'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $ultimoIntento
        ]);
    }
}