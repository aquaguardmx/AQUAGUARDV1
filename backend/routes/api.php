<?php

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\RegisterController;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Route;

use App\Models\User;

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\CursoInscritoUsuarioController;
use App\Http\Controllers\Api\SchoolController;

use App\Http\Controllers\Api\EstacionesController;
/*
|--------------------------------------------------------------------------
| Rutas Públicas de la API
|--------------------------------------------------------------------------
|
| Estas rutas son accesibles sin necesidad de un token de autenticación.
|
*/

// Registro y Login
Route::post('/register', [RegisterController::class, 'store']);

Route::post('/login', [LoginController::class, 'login']);

// Ruta para manejar el clic en el enlace de verificación de correo desde el frontend
// (flujo API / SPA). No requiere sesión: la firma del enlace se verifica mediante
// el middleware 'signed' y aquí validamos manualmente el id/hash.
Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    // El middleware 'signed' ya valida la firma y la expiración.

    // Buscar el usuario por su id (usa la clave primaria del modelo).
    $user = User::find($id);

    if (! $user) {
        return redirect('http://localhost:4321/login?verification=not_found');
    }

    // Verificar que el hash concuerde con el email del usuario.
    if (! hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
        return redirect('http://localhost:4321/login?verification=invalid');
    }

    // Marcar como verificado si aún no lo está y disparar el evento Verified.
    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        event(new Verified($user));
    }

    // Redirige al frontend indicando éxito.
    return redirect('http://localhost:4321/login?verified=1');

})->middleware('signed')->name('verification.verify');


/*
|--------------------------------------------------------------------------
| Rutas Protegidas de la API
|--------------------------------------------------------------------------
|
| Estas rutas requieren que el usuario esté autenticado con Sanctum.
|
*/
Route::middleware('auth:sanctum')->group(function () {
    // Cerrar sesión
    Route::post('/logout', [LoginController::class, 'logout']);

    // Reenviar el correo de verificación
    Route::post('/email/verification-notification', function (Request $request) {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'El correo ya ha sido verificado.'], 200);
        }
        
        $request->user()->sendEmailVerificationNotification();
    
        return response()->json(['message' => 'Se ha enviado un nuevo enlace de verificación a tu correo.'], 202);
    })->middleware('throttle:6,1')->name('verification.send');
});

// API PARA USUARIOS
Route::get('/usuarios', [UserController::class, 'index']);;

Route::get('/usuarios/{id}', [UserController::class, 'show']);;

Route::put('/usuarios/{id}', [UserController::class, 'update']);

Route::delete('/usuarios/{id}', [UserController::class, 'destroy']);

// API PARA CATEGORIAS
Route::get('/categorias', [CategoryController::class, 'index']);

Route::get('/categorias/{id}', [CategoryController::class, 'show']);

Route::post('/categorias', [CategoryController::class, 'store']);

Route::put('/categorias/{id}', [CategoryController::class, 'update']);

Route::delete('/categorias/{id}', [CategoryController::class, 'destroy']);

// API PARA CURSOS
Route::get('/cursos', [CourseController::class, 'index']);

Route::post('/cursos', [CourseController::class, 'store']);

Route::get('/cursos/{id}', [CourseController::class, 'show']);

Route::put('/cursos/{id}', [CourseController::class, 'update']);

Route::delete('/cursos/{id}', [CourseController::class, 'destroy']);

Route::post('/subir-portada', [CourseController::class, 'subirPortada']);

Route::get('/cursos-inscritos', [CursoInscritoUsuarioController::class, 'index']);

Route::post('/cursos-inscritos', [CursoInscritoUsuarioController::class, 'store']);

Route::get('/cursos-por-usuario/{usuarioId}', [CourseController::class, 'getCursosPorUsuario']);

// API PARA MODULOS
Route::get('/modulos', [ModuleController::class, 'index']);

Route::get('/modulos/{id}', [ModuleController::class, 'show']);

Route::post('/modulos', [ModuleController::class, 'store']);

Route::put('/modulos/{id}', [ModuleController::class, 'update']);

Route::delete('/modulos/{id}', [ModuleController::class, 'destroy']);

Route::get('/cursos/{curso}/modulos', [CourseController::class, 'getModulosPorCurso']);

Route::get('/lecciones', [LessonController::class, 'index']);

Route::post('/lecciones', [LessonController::class, 'store']);

Route::get('/lecciones/{id}', [LessonController::class, 'show']);

Route::get('/modulos/{moduloId}/lecciones', [ModuleController::class, 'getPorModulo']);

// API PARA ESCUELAS
Route::get('/escuelas', [SchoolController::class, 'index']);

Route::post('/escuelas', [SchoolController::class, 'store']);

//API PARA  MAPA 
Route::get('/mapa', [EstacionesController::class, 'mapData']);

//API para obtener datos geográficos 
Route::get('/estados', [EstacionesController::class, 'getEstados']);
Route::get('/municipios/estado/{estadoId}', [EstacionesController::class, 'getMunicipiosPorEstado']);
Route::get('/cuencas', [EstacionesController::class, 'getCuencas']);
Route::get('/tipos', [EstacionesController::class, 'getTipos']);
Route::get('/subtipos', [EstacionesController::class, 'getSubtipos']);
Route::get('/parametros', [EstacionesController::class, 'getParametros']);

//API para agregar estaciones/cuerpos de agua por usuario normal
Route::post('/estaciones', [EstacionesController::class, 'store']);

//API para admin sobre estaciones/cuerpos de agua

// Obtener todas las estaciones (para admin) con paginación y limite 
Route::get('/admin/estaciones', [EstacionesController::class, 'index']);

// Obtener una estación específica
Route::get('/admin/estacion/{id}', [EstacionesController::class, 'show']);
    
// Actualizar estación (aprobación y edicion)
Route::put('/admin/estacion/{id}', [EstacionesController::class, 'update']);
    
// Eliminar estación
Route::delete('/admin/estacion/{id}', [EstacionesController::class, 'destroy']);
    
// Crear estación (admin)
Route::post('/admin/estaciones', [EstacionesController::class, 'storeAdmin']);
