<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

// Ruta pública para manejar el clic en el enlace de verificación enviado por email
// (mismo objetivo que la ruta API). Usamos un handler sin sesión para evitar
// errores cuando la petición no está autenticada.
Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    // El middleware 'signed' valida la firma y la expiración.

    $user = User::find($id);

    if (! $user) {
        return redirect('http://localhost:4321/login?verification=not_found');
    }

    if (! hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
        return redirect('http://localhost:4321/login?verification=invalid');
    }

    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        event(new Verified($user));
    }

    return redirect('http://localhost:4321/login?verified=1');

})->middleware('signed')->name('verification.verify');
