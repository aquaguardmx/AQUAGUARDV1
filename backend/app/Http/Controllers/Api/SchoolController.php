<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Escuela;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SchoolController extends Controller
{
    public function index()
    {
        $escuelas = Escuela::all();

        if($escuelas->isEmpty()) {
            return response()->json(['message' => 'No se encontraron escuelas'], 404);
        }

        $data = [
            'escuela' => $escuelas,
            'status' => 200
        ];

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre_escuela' => 'required|string|max:255',
            'nivel_educativo' => 'required|string',
            'estado' => 'required|string|max:255',
            'ciudad' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $escuelas = Escuela::create([
            'nombre_escuela' => $request->input('nombre_escuela'),
            'nivel_educativo' => $request->input('nivel_educativo'),
            'estado' => $request->input('estado'),
            'ciudad' => $request->input('ciudad'),
        ]);

        return response()->json($escuelas, 201);
    }
}