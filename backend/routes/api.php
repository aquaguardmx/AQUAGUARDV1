<?php

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\RegisterController;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\UserController;

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

Route::post('/usuarios', function() {
    return 'Crear Usuario';
});

Route::put('/usuarios/{id}', [UserController::class, 'update']);

Route::delete('/usuarios/{id}', [UserController::class, 'destroy']);

