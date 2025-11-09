<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estados;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EstadosController extends Controller
{
    public function index()
    {
        $estados = Estados::select('id_estado', 'nombre')->get();

        if($estados->isEmpty()) {
            return response()->json(['message' => 'No se encontraron estados'], 404);
        }

        return response()->json($estados);
    }
}
    