<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; 
use Illuminate\Validation\Rule; 

class UserController extends Controller
{
    public function index()
    {
        $usuarios = User::all();
        if($usuarios->isEmpty()) {
            return response()->json(['message' => 'No se encontraron usuarios'], 404);
        }
        $data = [
            'usuarios' => $usuarios,
            'status' => 200
        ];
        return response()->json($usuarios, 200);
    }

    public function show($id)
    {
        $usuario = User::find($id);
        if (!$usuario) {
            $data = [
                'message' => 'Usuario no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        $data = [
            'usuario' => $usuario,
            'status' => 200
        ];
        return response()->json($data, 200);
    }

    public function destroy($id)
    {
        $usuario = User::find($id);
        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }
        $usuario->delete();
        return response()->json(['message' => 'Usuario eliminado con éxito'], 200);
    }

    public function update(Request $request, $id)
    {
        $usuario = User::find($id);
        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // Validaciones corregidas: Separa reglas en array para evitar parsing error
        $validator = Validator::make($request->all(), [
            'nombre' => ['sometimes', 'required', 'string', 'max:50'],  // Separado por elementos
            'ap_paterno' => ['sometimes', 'required', 'string', 'max:50'],
            'ap_materno' => ['sometimes', 'nullable', 'string', 'max:50'],
            'correo_electronico' => [
                'sometimes',
                'required',
                'email',  // Removí 'string' (redundante para email)
                'max:255',
                Rule::unique('usuarios', 'correo_electronico')->ignore($id, 'id_usuario'),  // Fix para PK id_usuario
            ],
            'contrasena' => ['sometimes', 'min:8'],  // Removí 'required' (opcional) y 'string' (implícito)
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Prepara datos manteniendo valores existentes si no se envían
        $data = [
            'nombre' => $request->input('nombre', $usuario->nombre),
            'ap_paterno' => $request->input('ap_paterno', $usuario->ap_paterno),
            'ap_materno' => $request->input('ap_materno', $usuario->ap_materno),
            'correo_electronico' => $request->input('correo_electronico', $usuario->correo_electronico),
        ];

        // Hash contrasena SOLO si se proporciona una nueva (seguridad clave)
        if ($request->filled('contrasena')) {
            $data['contrasena'] = Hash::make($request->contrasena);
        }

        $usuario->update($data);

        return response()->json([
            'message' => 'Usuario actualizado con éxito',
            'usuario' => $usuario->makeHidden(['contrasena'])  // Oculta contrasena en respuesta
        ], 200);
    }
} 