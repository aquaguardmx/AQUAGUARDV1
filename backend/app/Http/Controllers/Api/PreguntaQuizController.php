<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PreguntaQuiz;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PreguntaQuizController extends Controller
{
    public function index()
    {
        $preguntas = PreguntaQuiz::with(['quiz', 'opciones'])->get();

        if($preguntas->isEmpty()) {
            return response()->json(['message' => 'No se encontraron preguntas'], 404);
        }
        $data = [
            'preguntas' => $preguntas,
            'status' => 200
        ];
        return response()->json($preguntas);
    } 

    public function show($id)
    {
        $pregunta = PreguntaQuiz::find($id);

        if(!$pregunta) {
            return response()->json(['message' => 'Pregunta no encontrada'], 404);
        }
        $data = [
            'pregunta' => $pregunta,
            'status' => 200
        ];
        return response()->json($pregunta);
    }
}