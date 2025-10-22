<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    /** @use HasFactory<\Database\Factories\CursoFactory> */
    use HasFactory;

    protected $table = 'cursos'; // <-- AÑADE ESTA LÍNEA

    // La llave primaria no es `id` sino `id_curso` según la migración
    protected $primaryKey = 'id_curso';

    // Es una clave autoincremental entera
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'nombre_curso',
        'descripcion',
        'nivel_dificultad',
    ];
}
