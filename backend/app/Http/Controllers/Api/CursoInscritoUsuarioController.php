<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CursoInscritoUsuario;
use App\Models\ProgresoLeccion;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class CursoInscritoUsuarioController extends Controller
{
    public function index()
    {
        $cursosInscritos = CursoInscritoUsuario::all();
        if($cursosInscritos->isEmpty()) {
            return response()->json(['message' => 'No se encontraron cursos inscritos'], 404);
        }

        $data = [
            'cursos_inscritos' => $cursosInscritos,
            'status' => 200
        ];

        return response()->json($cursosInscritos);
    } 

    public function store(Request $request)
    {
        $validated = $request->validate([
            'usuario_id' => 'required|integer',
            'curso_id' => 'required|integer',
        ]);

        // Verificar duplicados manualmente
        $existe = CursoInscritoUsuario::where('usuario_id', $validated['usuario_id'])
            ->where('curso_id', $validated['curso_id'])
            ->exists();

        if ($existe) {
            return response()->json([
                'message' => 'El usuario ya está inscrito en este curso'
            ], 409);
        }

        $cursoInscrito = CursoInscritoUsuario::create($validated);

        return response()->json($cursoInscrito, 201);
    }

    public function getCursosPorUsuario($usuarioId)
    {
        // Obtener las inscripciones del usuario con la relación 'curso' cargada
        // También cargamos 'autor', 'categoria' y 'modulos.lecciones' para calcular progreso
        $inscripciones = CursoInscritoUsuario::where('usuario_id', $usuarioId)
            ->with(['curso.autor', 'curso.categoria', 'curso.modulos.lecciones']) 
            ->get();

        if ($inscripciones->isEmpty()) {
            return response()->json([], 200);
        }

        foreach ($inscripciones as $inscripcion) {
            $curso = $inscripcion->curso;
            
            // Aplanar todas las lecciones de todos los módulos para facilitar el conteo
            $todasLasLecciones = $curso->modulos->flatMap(function ($modulo) {
                return $modulo->lecciones;
            });

            $totalLecciones = $todasLasLecciones->count();
            
            // Obtener IDs de estas lecciones
            $leccionesIds = $todasLasLecciones->pluck('id_leccion');

            // Contar lecciones completadas por el usuario para este curso
            $leccionesCompletadas = ProgresoLeccion::where('usuario_id', $usuarioId)
                ->whereIn('leccion_id', $leccionesIds)
                ->count();

            // Calcular porcentaje
            $progreso = $totalLecciones > 0 ? round(($leccionesCompletadas / $totalLecciones) * 100) : 0;

            // Inyectar datos calculados en el objeto curso
            $curso->progreso = $progreso;
            $curso->lecciones_completadas = $leccionesCompletadas;
            $curso->total_lecciones = $totalLecciones;

            // Determinar la siguiente lección (la primera no completada)
            $completadasIds = ProgresoLeccion::where('usuario_id', $usuarioId)
                ->whereIn('leccion_id', $leccionesIds)
                ->pluck('leccion_id')
                ->toArray();

            $siguienteLeccion = $todasLasLecciones->first(function ($leccion) use ($completadasIds) {
                return !in_array($leccion->id_leccion, $completadasIds);
            });

            if ($siguienteLeccion) {
                // Usamos 'ultima_leccion' para mostrar el título de la lección actual/siguiente en el frontend
                $curso->ultima_leccion = $siguienteLeccion->titulo;
                $curso->siguiente_leccion_id = $siguienteLeccion->id_leccion;
            } else {
                $curso->ultima_leccion = 'Curso Completado';
                // Si está completado, podríamos redirigir a la última lección o al certificado
                $curso->siguiente_leccion_id = $todasLasLecciones->last() ? $todasLasLecciones->last()->id_leccion : null;
            }
            
            // Opcional: Ocultar la estructura profunda de módulos si no se necesita en el frontend para aligerar la respuesta
            // $curso->unsetRelation('modulos');
        }

        return response()->json($inscripciones, 200);
    }

    public function verificarInscripcion($usuarioId, $cursoId)
    {
        $inscripcion = CursoInscritoUsuario::where('usuario_id', $usuarioId)
            ->where('curso_id', $cursoId)
            ->exists();

        return response()->json([
            'inscrito' => $inscripcion,
        ]);
    }
}