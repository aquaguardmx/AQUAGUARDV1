<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\ObjetivoDeAprendizaje;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    public function index()
    {
        $cursos = Curso::with('autor', 'categoria', 'modulos')->get();

        if($cursos->isEmpty()) {
            return response()->json(['message' => 'No se encontraron cursos'], 404);
        }
        $data = [
            'cursos' => $cursos,
            'status' => 200
        ];
        return response()->json($cursos);
    } 

   public function store(Request $request)
    {
        // 1. VALIDACIÓN
        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            // Cambié 'file' por 'image' para mayor seguridad
            'portada_url' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // Max 5MB
            'tiempo_estimado_min' => 'required|integer|min:1',
            'autor_id' => 'required|integer|exists:usuarios,id_usuario',
            'categoria_id' => 'required|integer|exists:categorias,id',
            'publicado' => 'boolean',
            'objetivos_aprendizaje' => 'required|array|min:1',
            'objetivos_aprendizaje.*.descripcion' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación de datos de entrada.',
                'errors' => $validator->errors()
            ], 422);
        }

        $rutaImagen = null; // Variable para guardar la ruta relativa

        // 2. LÓGICA DE SUBIDA DEL ARCHIVO
        if ($request->hasFile('portada_url')) {
            try {
                // Guardamos en el disco 'public', dentro de la carpeta 'portadas'
                // Esto devuelve algo como: "portadas/hashname.jpg"
                $rutaImagen = $request->file('portada_url')->store('portadas', 'public');

            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al subir la imagen al servidor.',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        // 3. TRANSACCIÓN DE BASE DE DATOS
        try {
            DB::beginTransaction();

            $curso = Curso::create([
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
                // Guardamos la ruta relativa. 
                // El frontend luego le añadirá '/storage/' al principio.
                'portada_url' => $rutaImagen, 
                'tiempo_estimado_min' => $request->tiempo_estimado_min,
                'autor_id' => $request->autor_id,
                'categoria_id' => $request->categoria_id,
                // Conversión segura a booleano
                'publicado' => filter_var($request->publicado, FILTER_VALIDATE_BOOLEAN),
            ]);

            // Procesar Objetivos de Aprendizaje
            // Aseguramos que sea un array válido
            if ($request->has('objetivos_aprendizaje')) {
                $objetivosData = [];
                foreach ($request->objetivos_aprendizaje as $objetivo) {
                    // Verificamos que tenga descripción para evitar errores
                    if (!empty($objetivo['descripcion'])) {
                        $objetivosData[] = [
                            'descripcion' => $objetivo['descripcion']
                        ];
                    }
                }
                // createMany maneja automáticamente el array asociativo
                $curso->objetivosAprendizaje()->createMany($objetivosData);
            }

            DB::commit();

            // Cargamos la relación para devolverla en la respuesta (opcional)
            $curso->load('objetivosAprendizaje');

            return response()->json([
                'success' => true,
                'message' => 'Curso creado exitosamente.',
                'data' => [
                    // Usamos id o id_curso según tu modelo
                    'curso_id' => $curso->id ?? $curso->id_curso, 
                    'curso' => $curso
                ]
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            // 4. ROLLBACK DE ARCHIVO (Limpieza)
            // Si falló la BD, borramos la imagen que acabamos de subir
            if ($rutaImagen && Storage::disk('public')->exists($rutaImagen)) {
                Storage::disk('public')->delete($rutaImagen);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar en la base de datos. Transacción revertida.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        // Carga ansiosa (Eager Loading) de 'autor', 'categoria', y ahora 'modulos.lecciones'
        $curso = Curso::with('autor', 'categoria', 'modulos.lecciones', 'objetivosAprendizaje')->find($id);
    
        if (!$curso) {
            return response()->json(['message' => 'Curso no encontrado'], 404);
        }
        
        return response()->json($curso);
    }

    public function update(Request $request, $id)
    {
        $curso = Curso::find($id);
        
        if (!$curso) {
            return response()->json(['message' => 'Curso no encontrado'], 404);
        }
        $validator = Validator::make($request->all(), [
            'titulo' => 'sometimes|string|max:255',
            'portada_url' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'descripcion' => 'sometimes|string',
            'tiempo_estimado_min' => 'sometimes|nullable|integer',
            'autor_id' => 'sometimes|integer|exists:usuarios,id_usuario',
            'categoria_id' => 'sometimes|integer|exists:categorias,id',
            'publicado' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $curso->update($request->only([
            'titulo',
            'descripcion',
            'portada_url',
            'tiempo_estimado_min',
            'autor_id',
            'categoria_id',
            'publicado',
        ]));
        return response()->json([
            'message' => 'Curso actualizado correctamente',
            'curso' => $curso
        ]);
    }

    /**
     * Actualiza solo el estado de publicación de un curso.
     * Espera un campo 'publicado' (boolean) en el cuerpo de la solicitud (e.g., PUT, PATCH).
     *
     * @param Request $request
     * @param int $id El ID del curso a actualizar.
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePublicadoStatus(Request $request, $id)
    {
        // 1. Validar la solicitud: solo necesitamos el campo 'publicado' y debe ser un booleano.
        $validator = Validator::make($request->all(), [
            'publicado' => 'required|boolean',
        ], [
            'publicado.required' => 'El estado de publicación es obligatorio.',
            'publicado.boolean' => 'El estado de publicación debe ser verdadero o falso (boolean).',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // 2. Buscar el curso por ID.
        $curso = Curso::find($id);
        
        // 3. Verificar si el curso existe (Manejo de 404).
        if (!$curso) {
            return response()->json(['message' => 'Curso no encontrado'], 404);
        }
        
        // 4. Obtener el nuevo estado de la solicitud.
        $nuevoEstado = $request->input('publicado');
        
        // 5. Actualizar solo el campo 'publicado'.
        $curso->publicado = $nuevoEstado;
        $curso->save();
        
        // 6. Devolver una respuesta exitosa.
        $estadoTexto = $nuevoEstado ? 'Publicado' : 'No Publicado';
        
        return response()->json([
            'message' => "Estado del curso actualizado a '{$estadoTexto}' correctamente.",
            'publicado' => $curso->publicado,
            'curso_id' => $curso->id,
        ]);
    }

    public function destroy($id)
    {
        $curso = Curso::find($id);

        if (!$curso) {
            return response()->json(['message' => 'Curso no encontrado'], 404);
        }
        $curso->delete();
        return response()->json(['message' => 'Curso eliminado correctamente']);
    }

    public function getModulosPorCurso(Curso $curso)
    {
        // 1. Laravel ya encontró el curso y lo inyectó en la variable $curso.
        //    Si no lo encuentra, automáticamente devuelve un 404. ¡Magia!

        // 2. Gracias a la relación "modulos()" que definimos en el Modelo,
        //    podemos acceder a ellos como una propiedad.
        //    Eloquent/Laravel se encarga de hacer la consulta.
        $modulos = $curso->modulos;

        // 3. Devolvemos los módulos como JSON.
        return response()->json($modulos, 200);
    }

    public function getCursosPorUsuario($usuarioId)
    {
        try {
            //Encontrar al usuario
            $user = User::find($usuarioId);

            if (!$user) {
                return response()->json(['message' => 'Usuario no encontrado'], 404);
            }
            // hace relacion usuarios con cursos
            $cursos = $user->cursos;

            // Devolver la respuesta
            return response()->json($cursos, 200);

        } catch (Exception $e) {
            // Manejo de cualquier error inesperado
            return response()->json([
                'message' => 'Error al obtener los cursos del usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}