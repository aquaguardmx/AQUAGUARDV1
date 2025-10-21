<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Muestra el panel principal de la aplicación.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index() // <--- ESTE ES EL MÉTODO 'index'
    {
        // Aquí va la lógica: buscar datos en la base de datos, etc.
        
        // Al final, devuelve la vista que el usuario verá.
        return view('home'); // Carga el archivo resources/views/home.blade.php
    }
}