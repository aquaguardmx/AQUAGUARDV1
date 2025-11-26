<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Contenido;

class Modulo extends Model
{
    /** @use HasFactory<\Database\Factories\CursoFactory> */
    use HasFactory;

    protected $table = 'modulos';

    public $timestamps = false;

    protected $primaryKey = 'id_modulo';

    // Es una clave autoincremental entera
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'curso_id',
        'titulo',
        'orden',
    ];

    //Relacion de un módulo pertenece a un curso
    public function curso()
    {
        // 1er arg: El modelo relacionado (Curso)
        // 2do arg: La llave foránea en ESTA tabla 'modulos' (curso_id)
        // 3er arg: La llave primaria en la tabla 'cursos' (id_curso)
        return $this->belongsTo(Curso::class, 'curso_id', 'id_curso');
    }

    //Define la relación "un Módulo tiene muchas Lecciones"
    public function lecciones()
    {
        return $this->hasMany(Leccion::class, 'modulo_id');
    }
}

