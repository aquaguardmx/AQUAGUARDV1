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

        return response()->json($cursosInscritos);
    } 

    public function store(Request $request)
    {
        $validated = $request->validate([
            'usuario_id' => 'required|integer',
            'curso_id' => 'required|integer',
        ]);

        // Verificar duplicados
        $existe = CursoInscritoUsuario::where('usuario_id', $validated['usuario_id'])
            ->where('curso_id', $validated['curso_id'])
            ->exists();

        if ($existe) {
            return response()->json([
                'message' => 'El usuario ya está inscrito en este curso',
                'inscrito' => true
            ], 409);
        }

        $cursoInscrito = CursoInscritoUsuario::create($validated);

        return response()->json([
            'message' => 'Inscripción exitosa',
            'data' => $cursoInscrito,
            'inscrito' => true
        ], 201);
    }

    // NUEVO: Endpoint para inscribirse desde el frontend
    public function inscribirUsuario($usuarioId, $cursoId)
    {
        // Verificar si ya está inscrito
        $existe = CursoInscritoUsuario::where('usuario_id', $usuarioId)
            ->where('curso_id', $cursoId)
            ->exists();

        if ($existe) {
            return response()->json([
                'message' => 'Ya estás inscrito en este curso',
                'inscrito' => true
            ], 200);
        }

        // Crear nueva inscripción
        $inscripcion = CursoInscritoUsuario::create([
            'usuario_id' => $usuarioId,
            'curso_id' => $cursoId
        ]);

        return response()->json([
            'message' => 'Inscripción exitosa',
            'data' => $inscripcion,
            'inscrito' => true
        ], 201);
    }

    public function getCursosPorUsuario($usuarioId)
    {
        // Obtener las inscripciones del usuario
        $inscripciones = CursoInscritoUsuario::where('usuario_id', $usuarioId)
            ->with(['curso.autor', 'curso.categoria', 'curso.modulos.lecciones']) 
            ->get();

        if ($inscripciones->isEmpty()) {
            return response()->json([], 200);
        }

        foreach ($inscripciones as $inscripcion) {
            $curso = $inscripcion->curso;
            
            $todasLasLecciones = $curso->modulos->flatMap(function ($modulo) {
                return $modulo->lecciones;
            });

            $totalLecciones = $todasLasLecciones->count();
            $leccionesIds = $todasLasLecciones->pluck('id_leccion');

            // Contar lecciones completadas
            $leccionesCompletadas = ProgresoLeccion::where('usuario_id', $usuarioId)
                ->whereIn('leccion_id', $leccionesIds)
                ->count();

            // Calcular porcentaje
            $progreso = $totalLecciones > 0 ? round(($leccionesCompletadas / $totalLecciones) * 100) : 0;

            // Inyectar datos calculados
            $curso->progreso = $progreso;
            $curso->lecciones_completadas = $leccionesCompletadas;
            $curso->total_lecciones = $totalLecciones;

            // Determinar siguiente lección
            $completadasIds = ProgresoLeccion::where('usuario_id', $usuarioId)
                ->whereIn('leccion_id', $leccionesIds)
                ->pluck('leccion_id')
                ->toArray();

            $siguienteLeccion = $todasLasLecciones->first(function ($leccion) use ($completadasIds) {
                return !in_array($leccion->id_leccion, $completadasIds);
            });

            if ($siguienteLeccion) {
                $curso->ultima_leccion = $siguienteLeccion->titulo;
                $curso->siguiente_leccion_id = $siguienteLeccion->id_leccion;
            } else {
                $curso->ultima_leccion = 'Curso Completado';
                $curso->siguiente_leccion_id = $todasLasLecciones->last() ? $todasLasLecciones->last()->id_leccion : null;
            }
        }

        return response()->json($inscripciones, 200);
    }

    // Endpoint mejorado para verificar inscripción
    public function verificarInscripcion($usuarioId, $cursoId)
    {
        $inscripcion = CursoInscritoUsuario::where('usuario_id', $usuarioId)
            ->where('curso_id', $cursoId)
            ->first();

        return response()->json([
            'inscrito' => !is_null($inscripcion),
            'data' => $inscripcion
        ]);
    }

    // NUEVO: Obtener inscripción específica (si existe)
    public function getInscripcion($usuarioId, $cursoId)
    {
        $inscripcion = CursoInscritoUsuario::where('usuario_id', $usuarioId)
            ->where('curso_id', $cursoId)
            ->with(['curso'])
            ->first();

        if (!$inscripcion) {
            return response()->json([
                'message' => 'No estás inscrito en este curso',
                'inscrito' => false
            ], 404);
        }

        return response()->json([
            'inscrito' => true,
            'data' => $inscripcion
        ]);
    }
}