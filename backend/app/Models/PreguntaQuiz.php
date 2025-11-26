<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreguntaQuiz extends Model
{
    /** @use HasFactory<\Database\Factories\ContenidoFactory> */
    use HasFactory;

    protected $table = 'preguntas';

    // La llave primaria no es `id` sino `id_contenido` según la migración
    protected $primaryKey = 'id_pregunta';

    // Es una clave autoincremental entera
    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'quiz_id',
        'texto_pregunta',
        'tipo_pregunta',
    ];

    public function quiz()
    {
        return $this->belongsTo(QuizCurso::class, 'quiz_id', 'id_quiz');
    }

    public function opciones()
    {
        return $this->hasMany(OpcionQuiz::class, 'pregunta_id', 'id_pregunta');
    }
}