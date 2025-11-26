<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

Route::get('/', function () {
    return view('welcome');
});

// Ruta pública para manejar el clic en el enlace de verificación enviado por email
Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
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

// Ruta CORREGIDA para servir imágenes de portadas
Route::get('/storage/portadas/{filename}', function ($filename) {
    $path = storage_path('app/public/portadas/' . $filename);
    
    if (!file_exists($path)) {
        abort(404);
    }
    
    $file = file_get_contents($path);
    $type = mime_content_type($path);
    
    return response($file, 200)->header('Content-Type', $type);
})->where('filename', '.*'); // <- CORREGIDO: 'filename' en una sola palabra

// En routes/web.php - Ruta para debug de la API de cursos
Route::get('/debug-cursos', function () {
    $cursos = \App\Models\Curso::with(['autor', 'categoria'])
        ->where('publicado', true)
        ->get();
    
    $cursosConPortadas = [];
    
    foreach ($cursos as $curso) {
        $cursosConPortadas[] = [
            'id_curso' => $curso->id_curso,
            'titulo' => $curso->titulo,
            'portada_url' => $curso->portada_url,
            'portada_filename' => $curso->portada_url ? basename($curso->portada_url) : null,
            'file_exists' => $curso->portada_url ? file_exists(storage_path('app/public/portadas/' . basename($curso->portada_url))) : false,
        ];
    }
    
    return response()->json([
        'total_cursos' => $cursos->count(),
        'cursos' => $cursosConPortadas,
        'available_images' => array_values(array_diff(scandir(storage_path('app/public/portadas/')), ['.', '..']))
    ]);
});