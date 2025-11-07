<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\Contenido;

class Escuela extends Model
{
    /** @use HasFactory<\Database\Factories\CursoFactory> */
    use HasFactory;

    protected $table = 'escuelas'; // <-- AÑADE ESTA LÍNEA

    // La llave primaria no es `id` sino `id_curso` según la migración
    protected $primaryKey = 'id_escuela';

    // Es una clave autoincremental entera
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'nombre_escuela',
        'nivel_educativo',
        'estado',
        'ciudad'
    ];
}
