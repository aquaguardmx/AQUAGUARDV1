<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Leccion;

class Leccion extends Model
{
    /** @use HasFactory<\Database\Factories\CursoFactory> */
    use HasFactory;

    protected $table = 'lecciones'; // <-- AÑADE ESTA LÍNEA


    // La llave primaria no es `id` sino `id_curso` según la migración
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

    public function modulo(): BelongsTo
    {
        // 1er arg: Modelo padre (Modulo)
        // 2do arg: Llave foránea en ESTA tabla ('lecciones')
        // 3er arg: Llave primaria en la tabla 'modulos'
        return $this->belongsTo(Modulo::class, 'modulo_id', 'id_modulo');
    }
}

