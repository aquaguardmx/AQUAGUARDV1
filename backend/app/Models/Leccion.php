<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Leccion;

class Leccion extends Model
{
    /** @use HasFactory<\Database\Factories\CursoFactory> */
    use HasFactory;

    protected $table = 'lecciones'; 

    protected $primaryKey = 'id_leccion';

    // Es una clave autoincremental entera
    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'modulo_id',
        'titulo',
        'tipo_contenido',
        'contenido_texto',
        'video_url',
        'orden',
    ];
}

