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

    // GET /api/mapa - Todas las estaciones con relaciones (para administrador)
    public function index(): JsonResponse
    {
        $estaciones = Mapa::with([
            'municipio.estado',
            'cuenca',
            'tipo',
            'subtipo',
            'usuario',
            'mediciones.parametro',
            'semaforo' => function($query) {
                $query->orderBy('fecha_medicion', 'desc')->limit(1);
            }
        ])->get();

        return response()->json([
            'estaciones' => $estaciones,
            'total' => $estaciones->count(),
            'status' => 200
        ]);
    }

    // GET /api/mapa: Todas las estaciones con relaciones optimizadas
    public function mapData(): JsonResponse
    {
        $estaciones = Mapa::with([
            'municipio.estado',  // Municipio + estado
            'cuenca',
            'tipo',
            'subtipo',
            'mediciones' => function ($query) {
                $query->select('id_medicion', 'id_estacion', 'id_parametro', 'valor', 'clasificacion')
                    ->with(['parametro' => function ($q) {
                        $q->select('id_parametro', 'nombre', 'unidad', 'descripcion');
                    }])
                    ->orderBy('fecha_medicion', 'desc')
                    ->limit(3); // Solo las 3 mediciones más recientes
            },
            'semaforo' => function ($query) {
                $query->select('id_semaforo', 'id_estacion', 'color', 'contaminantes')
                    ->orderBy('fecha_medicion', 'desc')
                    ->limit(1);
            }
        ])
        ->select('id_estacion', 'nombre', 'clave_sitio', 'latitud', 'longitud', 'activo', 
                'id_municipio', 'id_cuenca', 'id_tipo', 'id_subtipo', 'id_usuario')
        ->where('activo', 'true')
        ->get();

        if ($estaciones->isEmpty()) {
            return response()->json(['message' => 'No se encontraron estaciones'], 404);
        }

        return response()->json([
            'estaciones' => $estaciones,
            'total' => $estaciones->count(),
            'status' => 200
        ]);
    }

    // POST /api/mapa: Crear nueva estación con relaciones
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'clave_sitio' => 'required|string|max:50|unique:estaciones,clave_sitio',
            'nombre' => 'required|string|max:255',
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
            'id_tipo' => 'required|integer|exists:tipos,id_tipo',
            'id_subtipo' => 'nullable|integer|exists:subtipos,id_subtipo',
            'id_cuenca' => 'nullable|integer|exists:cuencas,id_cuenca',
            'id_municipio' => 'required|integer|exists:municipios,id_municipio',
            'id_usuario' => 'required|integer|exists:usuarios,id_usuario',
            
            // Datos para mediciones (opcional al crear)
            'mediciones' => 'nullable|array',
            'mediciones.*.id_parametro' => 'required_with:mediciones|integer|exists:parametros,id_parametro',
            'mediciones.*.valor' => 'required_with:mediciones|numeric',
            'mediciones.*.clasificacion' => 'required_with:mediciones|string|max:50',
            'mediciones.*.fecha_medicion' => 'required_with:mediciones|date',
            
            // Datos para semáforo (opcional al crear)
            'semaforo' => 'nullable|array',
            'semaforo.color' => 'required_with:semaforo|string|in:VERDE,AMARILLO,ROJO',
            'semaforo.contaminantes' => 'nullable|string|max:255',
            'semaforo.fecha_medicion' => 'required_with:semaforo|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Crear la estación principal
            $estacion = Mapa::create($request->only([
                'clave_sitio', 'nombre', 'latitud', 'longitud', 
                'id_tipo', 'id_subtipo', 'id_cuenca', 'id_municipio', 'id_usuario'
            ]));

            // Crear mediciones si se proporcionan
            if ($request->has('mediciones') && is_array($request->mediciones)) {
                foreach ($request->mediciones as $medicionData) {
                    Medicion::create([
                        'id_estacion' => $estacion->id_estacion,
                        'id_parametro' => $medicionData['id_parametro'],
                        'valor' => $medicionData['valor'],
                        'clasificacion' => $medicionData['clasificacion'],
                        'fecha_medicion' => $medicionData['fecha_medicion'],
                    ]);
                }
            }

            // Crear semáforo si se proporciona
            if ($request->has('semaforo')) {
                Semaforo::create([
                    'id_estacion' => $estacion->id_estacion,
                    'color' => $request->semaforo['color'],
                    'contaminantes' => $request->semaforo['contaminantes'] ?? null,
                    'fecha_medicion' => $request->semaforo['fecha_medicion'],
                ]);
            }

            DB::commit();

            // Cargar todas las relaciones para la respuesta
            $estacion->load([
                'municipio.estado', 
                'cuenca', 
                'tipo', 
                'subtipo', 
                'usuario',
                'mediciones.parametro',
                'semaforo' => function($query) {
                    $query->orderBy('fecha_medicion', 'desc')->limit(1);
                }
            ]);

            return response()->json([
                'message' => 'Estación creada exitosamente',
                'estacion' => $estacion
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear la estación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // GET /api/mapa/{id} – Una estación con todas las relaciones
    public function show($id): JsonResponse
    {
        $estacion = Mapa::with([
            'municipio.estado',
            'cuenca',
            'tipo',
            'subtipo',
            'usuario',
            'mediciones.parametro',
            'semaforo' => function($query) {
                $query->orderBy('fecha_medicion', 'desc');
            }
        ])->find($id);

        if (!$estacion) {
            return response()->json(['message' => 'Estación no encontrada'], 404);
        }

        return response()->json([
            'estacion' => $estacion,
            'status' => 200
        ]);
    }

    // PUT /api/mapa/{id} – Actualizar estación y relaciones
    public function update(Request $request, $id): JsonResponse
    {
        $estacion = Mapa::find($id);
        if (!$estacion) {
            return response()->json(['message' => 'Estación no encontrada'], 404);
        }

        $validator = Validator::make($request->all(), [
            'clave_sitio' => 'sometimes|required|string|max:50|unique:estaciones,clave_sitio,' . $id . ',id_estacion',
            'nombre' => 'sometimes|required|string|max:255',
            'latitud' => 'sometimes|required|numeric|between:-90,90',
            'longitud' => 'sometimes|required|numeric|between:-180,180',
            'id_tipo' => 'sometimes|required|integer|exists:tipos,id_tipo',
            'id_subtipo' => 'nullable|integer|exists:subtipos,id_subtipo',
            'id_cuenca' => 'nullable|integer|exists:cuencas,id_cuenca',
            'id_municipio' => 'sometimes|required|integer|exists:municipios,id_municipio',
            'id_usuario' => 'sometimes|required|integer|exists:usuarios,id_usuario',
            'activo' => 'sometimes|boolean',
            
            // Mediciones para actualizar/crear
            'mediciones' => 'nullable|array',
            'mediciones.*.id_medicion' => 'nullable|integer|exists:mediciones,id_medicion', // Para actualizar existentes
            'mediciones.*.id_parametro' => 'required_with:mediciones|integer|exists:parametros,id_parametro',
            'mediciones.*.valor' => 'required_with:mediciones|numeric',
            'mediciones.*.clasificacion' => 'required_with:mediciones|string|max:50',
            'mediciones.*.fecha_medicion' => 'required_with:mediciones|date',
            
            // Semáforo para actualizar/crear
            'semaforo' => 'nullable|array',
            'semaforo.id_semaforo' => 'nullable|integer|exists:semaforos,id_semaforo',
            'semaforo.color' => 'required_with:semaforo|string|in:VERDE,AMARILLO,ROJO',
            'semaforo.contaminantes' => 'nullable|string|max:255',
            'semaforo.fecha_medicion' => 'required_with:semaforo|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->fails()], 422);
        }

        DB::beginTransaction();
        try {
            // Actualizar la estación principal
            $estacion->update($request->only([
                'clave_sitio', 'nombre', 'latitud', 'longitud', 
                'id_tipo', 'id_subtipo', 'id_cuenca', 'id_municipio', 'id_usuario', 'activo'
            ]));

            // Actualizar/Crear mediciones
            if ($request->has('mediciones')) {
                foreach ($request->mediciones as $medicionData) {
                    if (isset($medicionData['id_medicion'])) {
                        // Actualizar medición existente
                        $medicion = Medicion::where('id_estacion', $id)
                                            ->where('id_medicion', $medicionData['id_medicion'])
                                            ->first();
                        if ($medicion) {
                            $medicion->update([
                                'id_parametro' => $medicionData['id_parametro'],
                                'valor' => $medicionData['valor'],
                                'clasificacion' => $medicionData['clasificacion'],
                                'fecha_medicion' => $medicionData['fecha_medicion'],
                            ]);
                        }
                    } else {
                        // Crear nueva medición
                        Medicion::create([
                            'id_estacion' => $id,
                            'id_parametro' => $medicionData['id_parametro'],
                            'valor' => $medicionData['valor'],
                            'clasificacion' => $medicionData['clasificacion'],
                            'fecha_medicion' => $medicionData['fecha_medicion'],
                        ]);
                    }
                }
            }

            // Actualizar/Crear semáforo
            if ($request->has('semaforo')) {
                $semaforoData = $request->semaforo;
                if (isset($semaforoData['id_semaforo'])) {
                    // Actualizar semáforo existente
                    $semaforo = Semaforo::where('id_estacion', $id)
                                        ->where('id_semaforo', $semaforoData['id_semaforo'])
                                        ->first();
                    if ($semaforo) {
                        $semaforo->update([
                            'color' => $semaforoData['color'],
                            'contaminantes' => $semaforoData['contaminantes'] ?? null,
                            'fecha_medicion' => $semaforoData['fecha_medicion'],
                        ]);
                    }
                } else {
                    // Crear nuevo semáforo
                    Semaforo::create([
                        'id_estacion' => $id,
                        'color' => $semaforoData['color'],
                        'contaminantes' => $semaforoData['contaminantes'] ?? null,
                        'fecha_medicion' => $semaforoData['fecha_medicion'],
                    ]);
                }
            }

            DB::commit();

            // Recargar relaciones actualizadas
            $estacion->load([
                'municipio.estado', 
                'cuenca', 
                'tipo', 
                'subtipo', 
                'usuario',
                'mediciones.parametro',
                'semaforo' => function($query) {
                    $query->orderBy('fecha_medicion', 'desc');
                }
            ]);

            return response()->json([
                'message' => 'Estación actualizada exitosamente',
                'estacion' => $estacion
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar la estación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // DELETE /api/mapa/{id} - Eliminar estación y datos relacionados
    public function destroy($id): JsonResponse
    {
        $estacion = Mapa::find($id);
        if (!$estacion) {
            return response()->json(['message' => 'Estación no encontrada'], 404);
        }

        DB::beginTransaction();
        try {
            // Eliminar mediciones relacionadas
            Medicion::where('id_estacion', $id)->delete();
            
            // Eliminar semáforos relacionados
            Semaforo::where('id_estacion', $id)->delete();
            
            // Eliminar la estación
            $estacion->delete();

            DB::commit();

            return response()->json([
                'message' => 'Estación y todos sus datos relacionados eliminados exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar la estación',
                'error' => $e->getMessage()
            ], 500);
        }
    }


}