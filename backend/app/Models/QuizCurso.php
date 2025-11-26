<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizCurso extends Model
{
    /** @use HasFactory<\Database\Factories\ContenidoFactory> */
    use HasFactory;

    protected $table = 'quizzes';

    // La llave primaria no es `id` sino `id_contenido` según la migración
    protected $primaryKey = 'id_quiz';

    // Es una clave autoincremental entera
    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'curso_id',
        'titulo',
    ];

    public function preguntas()
    {
        return $this->hasMany(PreguntaQuiz::class, 'quiz_id', 'id_quiz');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'curso_id', 'id_curso');
    }
}