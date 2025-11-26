<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estaciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

use App\Models\Mediciones;
use App\Models\Parametros;
use App\Models\Semaforo;
use App\Models\Tipos;
use App\Models\parametroClasificacion;

use Illuminate\Support\Facades\Log;

class EstacionesController extends Controller
{
    // Función auxiliar para determinar clasificación de medición
    private function determinarClasificacion($parametroId, $valor, $tipoCuerpoAgua)
    {
        try {
            $valor = floatval($valor);
            
            // Buscar la clasificacion usando el modelo ParametroClasificacion
            $clasificacion = ParametroClasificacion::clasificar($parametroId, $valor);
            
            if ($clasificacion) {
                return $clasificacion->categoria;
            }
            
            return 'Fuera de rango';
            
        } catch (\Exception $e) {
            \Log::error("Error al determinar clasificación: " . $e->getMessage());
            return 'Sin datos';
        }
    }

    // Función auxiliar para determinar el color del semáforo según CONAGUA
    private function determinarSemaforo($mediciones)
    {
        // Si no hay mediciones es VERDE
        if (empty($mediciones)) {
            return [
                'color' => 'VERDE',
                'contaminantes' => null,
                'razon' => 'Sin mediciones disponibles'
            ];
        }
        $contaminantes = [];
        $incumplimientos = [];
        $clasificacionesContaminacion = ['CONTAMINADA', 'FUERTEMENTE CONTAMINADA'];
        $clasificacionesIncumplimiento = ['ACEPTABLE', 'CONTAMINADA', 'FUERTEMENTE CONTAMINADA'];

        foreach ($mediciones as $medicion) {
            $clasificacion = strtoupper($medicion['clasificacion']);
            // Verificar contaminación (ROJO)
            if (in_array($clasificacion, $clasificacionesContaminacion)) {
                $contaminantes[] = $medicion['parametro_nombre'];
            }
            // Verificar incumplimiento (AMARILLO)
            if (in_array($clasificacion, $clasificacionesIncumplimiento)) {
                $incumplimientos[] = $medicion['parametro_nombre'];
            }
        }
        // Si hay CONTAMINADA y FUERTEMENTE CONTAMINADA es ROJO 
        if (!empty($contaminantes)) {
            return [
                'color' => 'ROJO',
                'contaminantes' => implode(', ', $contaminantes),
                'razon' => 'Presencia de parámetros contaminados o fuertemente contaminados'
            ];
        }
        // AMARILLO si hay incumplimientos ACEPTABLE, CONTAMINADA y FUERTEMENTE CONTAMINADA
        if (!empty($incumplimientos)) {
            return [
                'color' => 'AMARILLO',
                'contaminantes' => implode(', ', $incumplimientos),
                'razon' => 'Incumplimiento en uno o más parámetros (nivel aceptable o inferior)'
            ];
        }
        // VERDE si no hay 
        return [
            'color' => 'VERDE',
            'contaminantes' => null,
            'razon' => 'Todos los parámetros en niveles excelentes o de cumplimiento'
        ];
    }

    // GET /api/estaciones: Todas las estaciones con relaciones para mapa público
    public function mapData(): JsonResponse
    {
        $estaciones = Estaciones::with([
            'municipio.estado',  // Municipio + estado
            'cuenca',
            'tipo',
            'subtipo',
            'mediciones' => function ($query) {
                $query->select('id_medicion', 'id_estacion', 'id_parametro', 'valor', 'clasificacion')
                    ->with(['parametro' => function ($q) {
                        $q->select('id_parametro', 'nombre', 'unidad', 'descripcion', 'definicion', 
                        'contaminantes_contribuyentes');
                    }])
                    ->orderBy('fecha_medicion', 'desc')
                    ->limit(3); // Solo las 3 mediciones más recientes
            },'semaforo' => function ($query) {
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

    // Nueva estación con mediciones y semáforo automáticos para el público
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'clave_sitio' => 'nullable|string|max:50|unique:estaciones,clave_sitio',
            'nombre' => 'required|string|max:255',
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
            'activo' => 'required|string|in:true,false',  
            'id_tipo' => 'required|integer|exists:tipos,id_tipo',
            'id_subtipo' => 'nullable|integer|exists:subtipos,id_subtipo',
            'id_cuenca' => 'nullable|integer|exists:cuencas,id_cuenca',
            'id_municipio' => 'required|integer|exists:municipios,id_municipio',
            'id_usuario' => 'required|integer|exists:usuarios,id_usuario',
            // Mediciones con valores brutos
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
        // Iniciar transacción
        DB::beginTransaction();
        try {
            // Obtener el tipo de cuerpo de agua para las clasificaciones
            $tipoCuerpoAgua = DB::table('tipos')->where('id_tipo', $request->id_tipo)->value('nombre');
            // Generar clave_sitio automáticamente si no se proporciona
            $claveSitio = $request->clave_sitio ?? 'EST-' . time() . '-' . rand(1000, 9999);
            // Convertir activo a boolean (fix para string "false")
            $activo = $request->boolean('activo');
            //$activo en false por defecto para nuevas estaciones desde público
            $activo = false;
            // Convertir IDs a integer (fix para strings)
            $idTipo = (int) $request->id_tipo;
            $idSubtipo = $request->id_subtipo ? (int) $request->id_subtipo : null;
            $idCuenca = $request->id_cuenca ? (int) $request->id_cuenca : null;
            $idMunicipio = (int) $request->id_municipio;
            $idUsuario = (int) $request->id_usuario;
            // Crear la estación principal
            $estacion = Estaciones::create([
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
                    // crear la medición
                    $medicion = Mediciones::create([
                        'id_estacion' => $estacion->id_estacion,
                        'id_parametro' => (int) $medicionData['id_parametro'],
                        'valor' => (float) $medicionData['valor'],
                        'clasificacion' => $clasificacion,
                        'fecha_medicion' => $medicionData['fecha_medicion'],
                        'id_usuario' => $idUsuario,
                    ]);
                    // Preparar datos para semáforo
                    $parametro = DB::table('parametros')->where('id_parametro', $medicionData['id_parametro'])->first();
                    $medicionesConClasificacion[] = [
                        'clasificacion' => $clasificacion,
                        'parametro_nombre' => $parametro->nombre
                    ];
                }
            }
            // Determinar y crear semáforo automáticamente con ayuda de la función
            $semaforoData = $this->determinarSemaforo($medicionesConClasificacion);
            // Crear registro de semáforo
            Semaforo::create([
                'id_estacion' => $estacion->id_estacion,
                'color' => $semaforoData['color'],
                'contaminantes' => $semaforoData['contaminantes'],
                'fecha_medicion' => now(),
            ]);
            // Finalizar transacción
            DB::commit();

            return response()->json([
                'message' => 'Estación creada exitosamente',
            ], 201);
        } catch (\Exception $e) {
            // Revertir transacción en caso de error
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear la estación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Resto de metodos para administración (index, show, update, destroy, storeAdmin)

    //todas las estaciones con paginación y limite
    public function index(): JsonResponse
    {
        try {
            // Obtener el parámetro 'limit' de la solicitud para paginación
            $perPage = request()->get('limit', 10); //10 por defecto si no se proporciona
            $estaciones = Estaciones::with([
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
            // Devolver respuesta con datos de cada pagina
            return response()->json([
                'estaciones' => $estaciones->items(), // Solo los items de la página actual
                'total_count' => $estaciones->total(), // Total de registros
                'total_pages' => $estaciones->lastPage(), // Total de páginas
                'current_page' => $estaciones->currentPage(), // Página actual
                'per_page' => $estaciones->perPage(), // Registros por página
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener estaciones para admin: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al cargar las estaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Obtener una estación específica
    public function show($id): JsonResponse
    {
        try {
            $estacion = Estaciones::with([
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


    //Actualizar estacion (para aprobacion y edicion)
    public function update(Request $request, $id): JsonResponse
    {
        $estacion = Estaciones::find($id);
        
        if (!$estacion) {
            return response()->json([
                'message' => 'Estacion no encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'clave_sitio' => 'sometimes|string|max:50|unique:estaciones,clave_sitio,' . $id . ',id_estacion',
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
        //inicia transacción
        DB::beginTransaction();
        try {
            // Preparar datos para actualizar
            $datosActualizacion = $request->only([
                'clave_sitio', 'nombre', 'latitud', 'longitud',
                'id_tipo', 'id_subtipo', 'id_cuenca', 'id_municipio', 'observaciones'
            ]);

            // Convertir activo a boolean si está presente 
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
                $tipoCuerpoAgua = Tipos::find($request->id_tipo)->nombre;
                
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
            // Finalizar transacción
            DB::commit();

            return response()->json([
                'message' => 'Estación actualizada exitosamente',
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

    //Eliminar estación
    public function destroy($id): JsonResponse
    {
        $estacion = Estaciones::find($id);
        
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

    // Crear estación desde administrador
    public function storeAdmin(Request $request): JsonResponse
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
            $estacion = estaciones::create([
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
            // Finalizar transacción
            DB::commit();

            return response()->json([
                'message' => 'Estación creada exitosamente',
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

    // Función auxiliar para recalcular semáforo
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






