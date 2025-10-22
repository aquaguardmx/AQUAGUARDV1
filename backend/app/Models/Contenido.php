<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contenido extends Model
{
    /** @use HasFactory<\Database\Factories\ContenidoFactory> */
    use HasFactory;

    protected $table = 'leccion';

    // La llave primaria no es `id` sino `id_contenido` según la migración
    protected $primaryKey = 'id_leccion';

    // Es una clave autoincremental entera
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'titulo_leccion',
        'contenido_leccion',
        'video',
        'orden',
        'id_curso',
    ];
}