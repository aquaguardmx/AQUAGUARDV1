<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use Illuminate\Validation\Rules;
use Illuminate\Auth\Events\Registered;

class RegisterController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:50',
            'ap_paterno' => 'required|string|max:50',
            'ap_materno' => 'nullable|string|max:50',
            'correo_electronico' => 'required|string|email|max:255|unique:usuarios,correo_electronico',
            'contrasena' => 'required|string|min:8',
        ]);
 
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422); 
        }

        $data = $validator->validated();

        $create = [
            'nombre' => $data['nombre'],
            'ap_paterno' => $data['ap_paterno'],
            'ap_materno' => $data['ap_materno'] ?? null,
            'correo_electronico' => $data['correo_electronico'],
            // Dejamos que el cast 'contrasena' => 'hashed' en el modelo haga el hash
            'contrasena' => $data['contrasena'],
        ];

        // Forzar rol por defecto = 2 (ignorar valor enviado desde el frontend)
        $create['id_rol'] = 2;

        // Forzar id_escuela siempre a NULL (ignorar cualquier valor enviado)
        $create['id_escuela'] = null;
        

        $user = User::create($create);

        // Disparar evento Registered para que se envíe el email de verificación
        event(new Registered($user));

        // 3. Responder con éxito
        return response()->json([
            'message' => '¡Usuario registrado con éxito!',
            'user' => $user
        ], 201); // 201 = Creado
    }
}