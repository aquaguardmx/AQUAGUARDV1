<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizResultado extends Model
{
    /** @use HasFactory<\Database\Factories\ContenidoFactory> */
    use HasFactory;

    protected $table = 'quiz_resultados';

    // La llave primaria no es `id` sino `id_contenido` según la migración
    protected $primaryKey = 'id_resultado';

    // Es una clave autoincremental entera
    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'quiz_id',
        'usuario_id',
        'calificacion',
        'fecha_intento',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class, 'quiz_id', 'id_quiz');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'id_usuario');
    }
}