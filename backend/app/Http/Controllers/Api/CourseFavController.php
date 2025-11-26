<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CursosFavoritosUsuarios;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class CourseFavController extends Controller
{
    public function index()
    {
        $cursosfavs = CursosFavoritosUsuarios::all();

        if($cursosfavs->isEmpty()) {
            return response()->json(['message' => 'No se encontraron cursos favoritos...'], 404);
        }

        $data = [
            'cursos' => $cursosfavs,
            'status' => 200
        ];

        return response()->json($data);
    } 
}