<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mapa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MapController extends Controller
{
    // Listar todas las estaciones (básico, sin JOINs)
    public function index(): JsonResponse
    {
        $estaciones = Mapa::all();

        if ($estaciones->isEmpty()) {
            return response()->json(['message' => 'No se encontraron estaciones'], 404);
        }

        return response()->json([
            'estaciones' => $estaciones,
            'status' => 200
        ]);
    }

   // GET /api/mapa: Todas las estaciones con TODAS las relaciones (JOINs via Eloquent with)
    public function mapData(): JsonResponse
    {
        $estaciones = Mapa::with([
            'municipio.estado',  // Municipio + estado
            'cuenca',
            'tipo',
            'subtipo',
            'usuario',  // Propietario
            'mediciones.parametro',  // Mediciones con parámetros (carga todas; agrega ->limit(10) si muchas)
            'semaforo' => function ($query) {  // Solo el más reciente
                $query->orderBy('fecha_medicion', 'desc')->limit(1);
            }
        ])->where('activo', 'true')->get();

        if ($estaciones->isEmpty()) {
            return response()->json(['message' => 'No se encontraron estaciones'], 404);
        }

        return response()->json([
            'estaciones' => $estaciones,  // Cada estación con relaciones cargadas (e.g., $estacion->municipio->nombre)
            'status' => 200
        ]);
    }

    // POST /api/mapa: Crear nueva estación con relaciones
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'clave_sitio' => 'required|string|max:50|unique:estaciones',
            'nombre' => 'required|string|max:255',
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
            'id_tipo' => 'required|integer|exists:tipos,id_tipo',
            'id_subtipo' => 'nullable|integer|exists:subtipos,id_subtipo',
            'id_cuenca' => 'nullable|integer|exists:cuencas,id_cuenca',
            'id_municipio' => 'required|integer|exists:municipios,id_municipio',
            'id_usuario' => 'required|integer|exists:usuarios,id_usuario',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $estacion = Mapa::create($request->only([
            'clave_sitio', 'nombre', 'latitud', 'longitud', 'id_tipo', 'id_subtipo', 'id_cuenca', 'id_municipio', 'id_usuario'
        ]));

        // Carga relaciones para la respuesta
        $estacion->load(['municipio.estado', 'tipo', 'cuenca', 'usuario']);

        return response()->json([
            'message' => 'Estación creada exitosamente',
            'estacion' => $estacion  // Con relaciones cargadas
        ], 201);
    }

    // Opcional: GET /api/mapa/{id} – Una estación con relaciones
    public function show($id): JsonResponse
    {
        $estacion = Mapa::with([
            'municipio.estado',
            'cuenca',
            'tipo',
            'subtipo',
            'usuario',
            'mediciones.parametro',
            'semaforo' => fn ($query) => $query->orderBy('fecha_medicion', 'desc')->limit(1)
        ])->find($id);

        if (!$estacion) {
            return response()->json(['message' => 'Estación no encontrada'], 404);
        }

        return response()->json([
            'estacion' => $estacion,  // Con todas las relaciones
            'status' => 200
        ]);
    }

    // Opcional: PUT /api/mapa/{id} – Actualizar
    public function update(Request $request, $id): JsonResponse
    {
        $estacion = Mapa::find($id);
        if (!$estacion) {
            return response()->json(['message' => 'Estación no encontrada'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:255',
            'latitud' => 'sometimes|required|numeric|between:-90,90',
            'longitud' => 'sometimes|required|numeric|between:-180,180',
            'id_tipo' => 'sometimes|required|integer|exists:tipos,id_tipo',
            'id_municipio' => 'sometimes|required|integer|exists:municipios,id_municipio',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $estacion->update($request->only(['nombre', 'latitud', 'longitud', 'id_tipo', 'id_municipio']));
        $estacion->load(['municipio.estado', 'tipo']);  // Recarga relaciones

        return response()->json([
            'message' => 'Estación actualizada exitosamente',
            'estacion' => $estacion  // Con relaciones
        ]);
    }

    // Opcional: DELETE /api/mapa/{id}
    public function destroy($id): JsonResponse
    {
        $estacion = Mapa::find($id);
        if (!$estacion) {
            return response()->json(['message' => 'Estación no encontrada'], 404);
        }
        $estacion->delete();
        return response()->json(['message' => 'Estación eliminada exitosamente']);
    }
}