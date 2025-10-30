<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categorias = Categoria::all();

        if($categorias->isEmpty()) {
            return response()->json(['message' => 'No se encontraron categorías'], 404);
        }

        $data = [
            'categorias' => $categorias,
            'status' => 200
        ];

        return response()->json($categorias);
    } 
}