<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subtipos;

class SubtiposController extends Controller
{
    public function index()
    {
        $subtipos = Subtipos::select('id_subtipo', 'nombre')->get();;

        if($subtipos->isEmpty()) {
            return response()->json(['message' => 'No se encontraron subtipos'], 404);
        }

        return response()->json($subtipos);
    }
}
    