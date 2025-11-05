<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Contenido;

class Modulo extends Model
{
    /** @use HasFactory<\Database\Factories\CursoFactory> */
    use HasFactory;

    protected $table = 'modulos'; // <-- AÑADE ESTA LÍNEA

    public $timestamps = false;

    // La llave primaria no es `id` sino `id_curso` según la migración
    protected $primaryKey = 'id_modulo';

    // Es una clave autoincremental entera
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'curso_id',
        'titulo',
        'orden',
    ];

    public function contenidos()
    {
        return $this->hasMany(Contenido::class, 'id_modulo');
    }

    /**
     * Define la relación inversa "un módulo pertenece a un curso".
     */
    public function curso()
    {
        // 1er arg: El modelo relacionado (Curso)
        // 2do arg: La llave foránea en ESTA tabla 'modulos' (curso_id)
        // 3er arg: La llave primaria en la tabla 'cursos' (id_curso)
        return $this->belongsTo(Curso::class, 'curso_id', 'id_curso');
    }

    /**
     * Define la relación "un Módulo tiene muchas Lecciones".
     */
    public function lecciones()
    {
        // Asume que tu modelo de lección se llama 'Leccion'
        // y que la tabla 'lecciones' tiene una columna 'modulo_id'.
        return $this->hasMany(Leccion::class, 'modulo_id');
    }
}

