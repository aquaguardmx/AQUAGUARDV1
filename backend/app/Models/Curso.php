<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\Contenido;

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
        'titulo',
        'descripcion',
        'portada_url',
        'tiempo_estimado_min',
        'autor_id',
        'categoria_id',
        'publicado',
    ];

    public function contenidos()
    {
        return $this->hasMany(Contenido::class, 'id_curso');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    /**
     * Obtiene el autor (usuario) que creó el curso.
     */
    public function autor(): BelongsTo
    {
        // Esta función se conecta con el modelo 'User' (o 'Usuario')
        // usando la llave foránea 'autor_id'
        
        // ¡IMPORTANTE! Asegúrate de que 'User::class' sea el nombre
        // de tu modelo de usuarios. Si es 'Usuario::class', cámbialo.
        return $this->belongsTo(User::class, 'autor_id');
    }

    /**
     * Define la relación "un curso tiene muchos módulos".
     */
    public function modulos()
    {
        // 1er arg: El modelo relacionado (Modulo)
        // 2do arg: El nombre de la llave foránea en la tabla 'modulos' (curso_id)
        // 3er arg: El nombre de la llave primaria en ESTA tabla 'cursos' (id_curso)
        
        // ¡BONUS! Añadimos orderBy para que siempre los regrese en el orden correcto.
        return $this->hasMany(Modulo::class, 'curso_id', 'id_curso')
                    ->orderBy('orden', 'asc');
    }
}
