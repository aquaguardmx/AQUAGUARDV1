<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mapa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

use App\Models\Mediciones;
use App\Models\Parametros;
use App\Models\Semaforo;
use App\Models\Tipos;

use Illuminate\Support\Facades\Log;

class MapController extends Controller
{

    // GET /api/mapa: Todas las estaciones con relaciones optimizadas para mapa público
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
                        $q->select('id_parametro', 'nombre', 'unidad', 'descripcion', 'definicion', 'contaminantes_contribuyentes');
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

    // Función para determinar clasificación de medición
    private function determinarClasificacion($parametroId, $valor, $tipoCuerpoAgua)
    {
        // Rangos según el tipo de cuerpo de agua y parámetro
        $rangos = $this->obtenerRangosCalidad($tipoCuerpoAgua);
        
        if (!isset($rangos[$parametroId])) {
            return 'Sin datos';
        }

        $rangoParametro = $rangos[$parametroId];
        $valor = floatval($valor);

        foreach ($rangoParametro as $clasificacion => $limites) {
            if ($valor >= $limites['min'] && $valor <= $limites['max']) {
                return $clasificacion;
            }
        }

        return 'Fuera de rango';
    }

    // Función para obtener rangos de calidad (ejemplo - ajustar con tus datos reales)
    private function obtenerRangosCalidad($tipoCuerpoAgua)
    {
        // Estructura: [id_parametro => [clasificacion => [min, max]]]
        // Estos son ejemplos - debes reemplazar con tus rangos reales
        $rangos = [
            // DBO (Demanda Bioquímica de Oxígeno) - mg/L
            1 => [
                'Excelente' => ['min' => 0, 'max' => 3],
                'Buena calidad' => ['min' => 3.1, 'max' => 5],
                'Aceptable' => ['min' => 5.1, 'max' => 8],
                'Contaminada' => ['min' => 8.1, 'max' => 15],
                'Fuertemente contaminada' => ['min' => 15.1, 'max' => 100]
            ],
            // DQO (Demanda Química de Oxígeno) - mg/L
            2 => [
                'Excelente' => ['min' => 0, 'max' => 10],
                'Buena calidad' => ['min' => 10.1, 'max' => 20],
                'Aceptable' => ['min' => 20.1, 'max' => 30],
                'Contaminada' => ['min' => 30.1, 'max' => 50],
                'Fuertemente contaminada' => ['min' => 50.1, 'max' => 200]
            ],
            // SST (Sólidos Suspendidos Totales) - mg/L
            3 => [
                'Excelente' => ['min' => 0, 'max' => 20],
                'Buena calidad' => ['min' => 20.1, 'max' => 40],
                'Aceptable' => ['min' => 40.1, 'max' => 60],
                'Contaminada' => ['min' => 60.1, 'max' => 100],
                'Fuertemente contaminada' => ['min' => 100.1, 'max' => 500]
            ],
            // OD (Oxígeno Disuelto) - mg/L (invertido - mayor es mejor)
            6 => [
                'Excelente' => ['min' => 7, 'max' => 20],
                'Buena calidad' => ['min' => 5, 'max' => 6.9],
                'Aceptable' => ['min' => 4, 'max' => 4.9],
                'Contaminada' => ['min' => 2, 'max' => 3.9],
                'Fuertemente contaminada' => ['min' => 0, 'max' => 1.9]
            ],
            // Coliformes Fecales - NMP/100ml
            4 => [
                'Excelente' => ['min' => 0, 'max' => 100],
                'Buena calidad' => ['min' => 101, 'max' => 500],
                'Aceptable' => ['min' => 501, 'max' => 1000],
                'Contaminada' => ['min' => 1001, 'max' => 5000],
                'Fuertemente contaminada' => ['min' => 5001, 'max' => 100000]
            ]
        ];

        // Ajustar rangos según tipo de cuerpo de agua si es necesario
        if ($tipoCuerpoAgua === 'costero') {
            // Rangos más estrictos para agua costera
            $rangos[1]['Excelente']['max'] = 2; // DBO más bajo para costas
        }

        return $rangos;
    }

    // Función para determinar el color del semáforo
    private function determinarSemaforo($mediciones)
    {
        if (empty($mediciones)) {
            return [
                'color' => 'VERDE',
                'contaminantes' => null,
                'razon' => 'Sin mediciones disponibles'
            ];
        }

        $contaminantes = [];
        $clasificacionesProblema = ['Contaminada', 'Fuertemente contaminada'];

        foreach ($mediciones as $medicion) {
            if (in_array($medicion['clasificacion'], $clasificacionesProblema)) {
                $contaminantes[] = $medicion['parametro_nombre'];
            }
        }

        if (!empty($contaminantes)) {
            return [
                'color' => 'ROJO',
                'contaminantes' => implode(', ', $contaminantes),
                'razon' => 'Presencia de parámetros contaminados'
            ];
        }

        // Verificar si hay algún parámetro que no sea "Excelente" o "Buena calidad"
        $clasificacionesAmarillo = ['Aceptable'];
        $parametrosAmarillo = [];

        foreach ($mediciones as $medicion) {
            if (in_array($medicion['clasificacion'], $clasificacionesAmarillo)) {
                $parametrosAmarillo[] = $medicion['parametro_nombre'];
            }
        }

        if (!empty($parametrosAmarillo)) {
            return [
                'color' => 'AMARILLO',
                'contaminantes' => implode(', ', $parametrosAmarillo),
                'razon' => 'Parámetros en nivel aceptable que requieren atención'
            ];
        }

        return [
            'color' => 'VERDE',
            'contaminantes' => null,
            'razon' => 'Todos los parámetros en niveles excelentes o de buena calidad'
        ];
    }

    // POST /api/mapa – Crear nueva estación con mediciones y semáforo automáticos para el público
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'clave_sitio' => 'nullable|string|max:50|unique:estaciones,clave_sitio',
            'nombre' => 'required|string|max:255',
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
            'activo' => 'required|string|in:true,false',  // String para "true"/"false"
            'id_tipo' => 'required|integer|exists:tipos,id_tipo',
            'id_subtipo' => 'nullable|integer|exists:subtipos,id_subtipo',
            'id_cuenca' => 'nullable|integer|exists:cuencas,id_cuenca',
            'id_municipio' => 'required|integer|exists:municipios,id_municipio',
            'id_usuario' => 'required|integer|exists:usuarios,id_usuario',
            
            // Mediciones con valores brutos (sin clasificación)
            'mediciones' => 'nullable|array',
            'mediciones.*.id_parametro' => 'required_with:mediciones|integer|exists:parametros,id_parametro',
            'mediciones.*.valor' => 'required_with:mediciones|numeric',
            'mediciones.*.fecha_medicion' => 'required_with:mediciones|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Obtener el tipo de cuerpo de agua para las clasificaciones
            $tipoCuerpoAgua = DB::table('tipos')->where('id_tipo', $request->id_tipo)->value('nombre');

            // Generar clave_sitio automáticamente si no se proporciona
            $claveSitio = $request->clave_sitio ?? 'EST-' . time() . '-' . rand(1000, 9999);

            // Convertir activo a boolean (fix para string "false")
            $activo = $request->boolean('activo');

            // Convertir IDs a integer (fix para strings)
            $idTipo = (int) $request->id_tipo;
            $idSubtipo = $request->id_subtipo ? (int) $request->id_subtipo : null;
            $idCuenca = $request->id_cuenca ? (int) $request->id_cuenca : null;
            $idMunicipio = (int) $request->id_municipio;
            $idUsuario = (int) $request->id_usuario;

            // Crear la estación principal
            $estacion = Mapa::create([
                'clave_sitio' => $claveSitio,
                'nombre' => $request->nombre,
                'latitud' => (float) $request->latitud,
                'longitud' => (float) $request->longitud,
                'activo' => $activo,
                'id_tipo' => $idTipo,
                'id_subtipo' => $idSubtipo,
                'id_cuenca' => $idCuenca,
                'id_municipio' => $idMunicipio,
                'id_usuario' => $idUsuario,
            ]);

            $medicionesConClasificacion = [];
            
            // Crear mediciones si se proporcionan
            if ($request->has('mediciones') && is_array($request->mediciones)) {
                foreach ($request->mediciones as $medicionData) {
                    // Determinar clasificación automáticamente
                    $clasificacion = $this->determinarClasificacion(
                        (int) $medicionData['id_parametro'],
                        (float) $medicionData['valor'],
                        $tipoCuerpoAgua
                    );

                    // Usar el modelo correcto para Mediciones
                    $medicion = \App\Models\Mediciones::create([
                        'id_estacion' => $estacion->id_estacion,
                        'id_parametro' => (int) $medicionData['id_parametro'],
                        'valor' => (float) $medicionData['valor'],
                        'clasificacion' => $clasificacion,
                        'fecha_medicion' => $medicionData['fecha_medicion'],
                        'id_usuario' => $idUsuario,
                    ]);

                    // Guardar para determinar semáforo
                    $parametro = DB::table('parametros')->where('id_parametro', $medicionData['id_parametro'])->first();
                    $medicionesConClasificacion[] = [
                        'clasificacion' => $clasificacion,
                        'parametro_nombre' => $parametro->nombre
                    ];
                }
            }

            // Determinar y crear semáforo automáticamente
            $semaforoData = $this->determinarSemaforo($medicionesConClasificacion);
            
            \App\Models\Semaforo::create([
                'id_estacion' => $estacion->id_estacion,
                'color' => $semaforoData['color'],
                'contaminantes' => $semaforoData['contaminantes'],
                'fecha_medicion' => now(),
            ]);

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
                'estacion' => $estacion,
                'semaforo_automatico' => $semaforoData
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al crear estación: ' . $e->getMessage());
            \Log::error('Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'message' => 'Error al crear la estación',
                'error' => $e->getMessage(),
                'trace' => env('APP_DEBUG') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    // GET APIs para los selects
    public function getEstados(): JsonResponse
    {
        $estados = \App\Models\Estados::select('id_estado', 'nombre')->get();
        return response()->json($estados);
    }

    public function getMunicipiosPorEstado($estadoId): JsonResponse
    {
        $municipios = \App\Models\Municipios::where('id_estado', $estadoId)
            ->select('id_municipio', 'nombre')
            ->get();
        return response()->json($municipios);
    }

    public function getCuencas(): JsonResponse
    {
        $cuencas = \App\Models\Cuencas::select('id_cuenca', 'nombre')->get();
        return response()->json($cuencas);
    }

    public function getTipos(): JsonResponse
    {
        $tipos = \App\Models\Tipos::select('id_tipo', 'nombre')->get();
        return response()->json($tipos);
    }

    public function getSubtipos(): JsonResponse
    {
        $subtipos = \App\Models\Subtipos::select('id_subtipo', 'nombre')->get();
        return response()->json($subtipos);
    }

    public function getParametros(): JsonResponse
    {
        $parametros = \App\Models\Parametros::select('id_parametro', 'nombre', 'unidad', 'descripcion', 'definicion', 'contaminantes_contribuyentes')->get();
        return response()->json($parametros);
    }


    // Resto de metodos para administración (index, show, update, destroy, storeAdmin)
    /*public function index(): JsonResponse
    {
        try {
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
            ])
            ->orderBy('created_at', 'desc')
            ->get();

            return response()->json([
                'estaciones' => $estaciones,
                'total' => $estaciones->count(),
                'status' => 200
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener estaciones para admin: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al cargar las estaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }*/

    //todas las estaciones con paginación y limite
    public function index(): JsonResponse
    {
        try {
            $perPage = request()->get('limit', 10); // Por defecto 10, se puede sobrescribir con ?limit=N en la URL
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
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

            return response()->json([
                'estaciones' => $estaciones->items(), // Solo los items de la página actual
                'total_count' => $estaciones->total(), // Total de registros
                'total_pages' => $estaciones->lastPage(), // Total de páginas
                'current_page' => $estaciones->currentPage(), // Página actual
                'per_page' => $estaciones->perPage(), // Registros por página
                'status' => 200
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener estaciones para admin: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al cargar las estaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener una estación específica
     */
    public function show($id): JsonResponse
    {
        try {
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
                return response()->json([
                    'message' => 'Estación no encontrada'
                ], 404);
            }

            return response()->json([
                'estacion' => $estacion,
                'status' => 200
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener estación: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al cargar la estación',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Actualizar estacion (para aprobacion y edicion)
     */
    public function update(Request $request, $id): JsonResponse
    {
        $estacion = Mapa::find($id);
        
        if (!$estacion) {
            return response()->json([
                'message' => 'Estacion no encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'clave_sitio' => 'sometimes|string|max:50|unique:mapa,clave_sitio,' . $id . ',id_estacion',
            'nombre' => 'sometimes|string|max:255',
            'latitud' => 'sometimes|numeric|between:-90,90',
            'longitud' => 'sometimes|numeric|between:-180,180',
            'activo' => 'sometimes|string|in:true,false',  // Cambiado a string para "true"/"false"
            'id_tipo' => 'sometimes|integer|exists:tipos,id_tipo',
            'id_subtipo' => 'nullable|integer|exists:subtipos,id_subtipo',
            'id_cuenca' => 'nullable|integer|exists:cuencas,id_cuenca',
            'id_municipio' => 'sometimes|integer|exists:municipios,id_municipio',
            'observaciones' => 'nullable|string',
            
            // Mediciones para actualizar/crear
            'mediciones' => 'nullable|array',
            'mediciones.*.id_medicion' => 'nullable|integer|exists:mediciones,id_medicion',
            'mediciones.*.id_parametro' => 'required_with:mediciones|integer|exists:parametros,id_parametro',
            'mediciones.*.valor' => 'required_with:mediciones|numeric',
            'mediciones.*.fecha_medicion' => 'required_with:mediciones|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Preparar datos para actualizar
            $datosActualizacion = $request->only([
                'clave_sitio', 'nombre', 'latitud', 'longitud',
                'id_tipo', 'id_subtipo', 'id_cuenca', 'id_municipio', 'observaciones'
            ]);

            // Convertir activo a boolean si está presente (fix para string "false")
            if ($request->has('activo')) {
                $datosActualizacion['activo'] = $request->boolean('activo');
            }

            // Convertir IDs a integer (fix para strings)
            if (isset($datosActualizacion['id_tipo'])) {
                $datosActualizacion['id_tipo'] = (int) $datosActualizacion['id_tipo'];
            }
            if (isset($datosActualizacion['id_subtipo'])) {
                $datosActualizacion['id_subtipo'] = (int) $datosActualizacion['id_subtipo'];
            }
            if (isset($datosActualizacion['id_cuenca'])) {
                $datosActualizacion['id_cuenca'] = (int) $datosActualizacion['id_cuenca'];
            }
            if (isset($datosActualizacion['id_municipio'])) {
                $datosActualizacion['id_municipio'] = (int) $datosActualizacion['id_municipio'];
            }

            // Convertir coordenadas a float
            if (isset($datosActualizacion['latitud'])) {
                $datosActualizacion['latitud'] = (float) $datosActualizacion['latitud'];
            }
            if (isset($datosActualizacion['longitud'])) {
                $datosActualizacion['longitud'] = (float) $datosActualizacion['longitud'];
            }

            // Actualizar la estación principal
            $estacion->update($datosActualizacion);

            // Si se cambió el tipo, recalcular clasificaciones
            if ($request->has('id_tipo')) {
                $tipoCuerpoAgua = \App\Models\Tipos::find($request->id_tipo)->nombre;
                
                // Recalcular clasificaciones de mediciones existentes
                if ($estacion->mediciones->count() > 0) {
                    foreach ($estacion->mediciones as $medicion) {
                        $clasificacion = $this->determinarClasificacion(
                            $medicion->id_parametro,
                            $medicion->valor,
                            $tipoCuerpoAgua
                        );
                        
                        $medicion->update([
                            'clasificacion' => $clasificacion
                        ]);
                    }
                    
                    // Recalcular semáforo
                    $this->recalcularSemaforo($estacion->id_estacion);
                }
            }

            // Actualizar/Crear mediciones si se proporcionan
            if ($request->has('mediciones') && is_array($request->mediciones)) {
                $tipoCuerpoAgua = $estacion->tipo->nombre;
                $medicionesConClasificacion = [];
                
                foreach ($request->mediciones as $medicionData) {
                    // Convertir valores a tipos correctos
                    $idParametro = (int) $medicionData['id_parametro'];
                    $valor = (float) $medicionData['valor'];

                    // Determinar clasificación automáticamente
                    $clasificacion = $this->determinarClasificacion(
                        $idParametro,
                        $valor,
                        $tipoCuerpoAgua
                    );

                    if (isset($medicionData['id_medicion'])) {
                        // Actualizar medición existente
                        $medicion = Mediciones::where('id_estacion', $id)
                                            ->where('id_medicion', $medicionData['id_medicion'])
                                            ->first();
                        if ($medicion) {
                            $medicion->update([
                                'id_parametro' => $idParametro,
                                'valor' => $valor,
                                'clasificacion' => $clasificacion,
                                'fecha_medicion' => $medicionData['fecha_medicion'],
                            ]);
                        }
                    } else {
                        // Crear nueva medición
                        $medicion = Mediciones::create([
                            'id_estacion' => $id,
                            'id_parametro' => $idParametro,
                            'valor' => $valor,
                            'clasificacion' => $clasificacion,
                            'fecha_medicion' => $medicionData['fecha_medicion'],
                            'id_usuario' => $request->user()->id_usuario ?? $estacion->id_usuario,
                        ]);
                    }

                    // Guardar para determinar semáforo
                    $parametro = \App\Models\Parametros::find($idParametro);
                    $medicionesConClasificacion[] = [
                        'clasificacion' => $clasificacion,
                        'parametro_nombre' => $parametro->nombre
                    ];
                }
                
                // Recalcular semáforo si hay nuevas mediciones
                if (!empty($medicionesConClasificacion)) {
                    $this->recalcularSemaforo($estacion->id_estacion);
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
                    $query->orderBy('fecha_medicion', 'desc')->limit(1);
                }
            ]);

            return response()->json([
                'message' => 'Estación actualizada exitosamente',
                'estacion' => $estacion
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar estación: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Error al actualizar la estación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar estación
     */
    public function destroy($id): JsonResponse
    {
        $estacion = Mapa::find($id);
        
        if (!$estacion) {
            return response()->json([
                'message' => 'Estación no encontrada'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Eliminar mediciones relacionadas
            Mediciones::where('id_estacion', $id)->delete();
            
            // Eliminar semáforos relacionados
            Semaforo::where('id_estacion', $id)->delete();
            
            // Eliminar la estación
            $estacion->delete();

            DB::commit();

            return response()->json([
                'message' => 'Estación eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar estación: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Error al eliminar la estación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear estación desde administrador
     */
    public function storeAdmin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'clave_sitio' => 'nullable|string|max:50|unique:mapa,clave_sitio',
            'nombre' => 'required|string|max:255',
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
            'activo' => 'required|string|in:true,false',  // String para "true"/"false"
            'id_tipo' => 'required|integer|exists:tipos,id_tipo',
            'id_subtipo' => 'nullable|integer|exists:subtipos,id_subtipo',
            'id_cuenca' => 'nullable|integer|exists:cuencas,id_cuenca',
            'id_municipio' => 'required|integer|exists:municipios,id_municipio',
            'id_usuario' => 'required|integer|exists:usuarios,id_usuario',
            'observaciones' => 'nullable|string',
            
            // Mediciones con valores brutos (sin clasificación)
            'mediciones' => 'nullable|array',
            'mediciones.*.id_parametro' => 'required_with:mediciones|integer|exists:parametros,id_parametro',
            'mediciones.*.valor' => 'required_with:mediciones|numeric',
            'mediciones.*.fecha_medicion' => 'required_with:mediciones|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Obtener el tipo de cuerpo de agua para las clasificaciones
            $tipoCuerpoAgua = \App\Models\Tipos::find($request->id_tipo)->nombre;

            // Generar clave_sitio automáticamente si no se proporciona
            $claveSitio = $request->clave_sitio ?? 'EST-' . time() . '-' . rand(1000, 9999);

            // Convertir activo a boolean (fix para string "false")
            $activo = $request->boolean('activo');

            // Convertir IDs a integer (fix para strings)
            $idTipo = (int) $request->id_tipo;
            $idSubtipo = $request->id_subtipo ? (int) $request->id_subtipo : null;
            $idCuenca = $request->id_cuenca ? (int) $request->id_cuenca : null;
            $idMunicipio = (int) $request->id_municipio;
            $idUsuario = (int) $request->id_usuario;

            // Crear la estación principal
            $estacion = Mapa::create([
                'clave_sitio' => $claveSitio,
                'nombre' => $request->nombre,
                'latitud' => (float) $request->latitud,
                'longitud' => (float) $request->longitud,
                'activo' => $activo,
                'id_tipo' => $idTipo,
                'id_subtipo' => $idSubtipo,
                'id_cuenca' => $idCuenca,
                'id_municipio' => $idMunicipio,
                'id_usuario' => $idUsuario,
                'observaciones' => $request->observaciones,
            ]);

            $medicionesConClasificacion = [];
            
            // Crear mediciones si se proporcionan
            if ($request->has('mediciones') && is_array($request->mediciones)) {
                foreach ($request->mediciones as $medicionData) {
                    // Convertir valores a tipos correctos
                    $idParametro = (int) $medicionData['id_parametro'];
                    $valor = (float) $medicionData['valor'];

                    // Determinar clasificación automáticamente
                    $clasificacion = $this->determinarClasificacion(
                        $idParametro,
                        $valor,
                        $tipoCuerpoAgua
                    );

                    // Crear medición
                    $medicion = Mediciones::create([
                        'id_estacion' => $estacion->id_estacion,
                        'id_parametro' => $idParametro,
                        'valor' => $valor,
                        'clasificacion' => $clasificacion,
                        'fecha_medicion' => $medicionData['fecha_medicion'],
                        'id_usuario' => $idUsuario,
                    ]);

                    // Guardar para determinar semáforo
                    $parametro = \App\Models\Parametros::find($idParametro);
                    $medicionesConClasificacion[] = [
                        'clasificacion' => $clasificacion,
                        'parametro_nombre' => $parametro->nombre
                    ];
                }
            }

            // Determinar y crear semáforo automáticamente
            $semaforoData = $this->determinarSemaforo($medicionesConClasificacion);
            
            Semaforo::create([
                'id_estacion' => $estacion->id_estacion,
                'color' => $semaforoData['color'],
                'contaminantes' => $semaforoData['contaminantes'],
                'fecha_medicion' => now(),
            ]);

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
                'estacion' => $estacion,
                'semaforo_automatico' => $semaforoData
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear estación (admin): ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Error al crear la estación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Función auxiliar para recalcular semáforo
     */
    private function recalcularSemaforo($idEstacion)
    {
        $mediciones = Mediciones::where('id_estacion', $idEstacion)
            ->with('parametro')
            ->get();

        $medicionesConClasificacion = [];
        
        foreach ($mediciones as $medicion) {
            $medicionesConClasificacion[] = [
                'clasificacion' => $medicion->clasificacion,
                'parametro_nombre' => $medicion->parametro->nombre
            ];
        }

        $semaforoData = $this->determinarSemaforo($medicionesConClasificacion);
        
        // Crear nuevo registro de semáforo
        Semaforo::create([
            'id_estacion' => $idEstacion,
            'color' => $semaforoData['color'],
            'contaminantes' => $semaforoData['contaminantes'],
            'fecha_medicion' => now(),
        ]);
    }





}






