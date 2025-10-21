<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    /**
     * Login API: valida correo_electronico + contrasena y devuelve token Sanctum
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'correo_electronico' => 'required|email',
            'contrasena' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $validator->validated();

        $user = User::where('correo_electronico', $data['correo_electronico'])->first();

        if (! $user || ! Hash::check($data['contrasena'], $user->contrasena)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        // Crear token usuario SAnctum
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Autenticado',
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    /**
     * Logout: elimina tokens actuales del usuario autenticado
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            // Revocar todos los tokens personales
            $user->tokens()->delete();
        }

        return response()->json(['message' => 'Sesión cerrada'], 200);
    }
}
