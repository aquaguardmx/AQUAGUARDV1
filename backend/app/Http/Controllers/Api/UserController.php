<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

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

        if(!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:50',
            'ap_paterno' => 'sometimes|required|string|max:50',
            'ap_materno' => 'sometimes|nullable|string|max:50',
            'correo_electronico' => 'sometimes|required|string|email|max:255|unique:usuarios,correo_electronico,'.$id,
            'contrasena' => 'sometimes|required|string|min:8',
        ]);

        if($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $usuario->nombre = $request->input('nombre', $usuario->nombre);
        $usuario->ap_paterno = $request->input('ap_paterno', $usuario->ap_paterno);
        $usuario->ap_materno = $request->input('ap_materno', $usuario->ap_materno);
        $usuario->correo_electronico = $request->input('correo_electronico', $usuario->correo_electronico);
        $usuario->contrasena = $request->input('contrasena', $usuario->contrasena);

        $usuario->save();

        return response()->json(['message' => 'Usuario actualizado con éxito'], 200);
    }
} 