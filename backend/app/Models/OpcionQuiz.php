<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpcionQuiz extends Model
{
    /** @use HasFactory<\Database\Factories\ContenidoFactory> */
    use HasFactory;

    protected $table = 'opciones';

    // La llave primaria no es `id` sino `id_contenido` según la migración
    protected $primaryKey = 'id_opcion';

    // Es una clave autoincremental entera
    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'pregunta_id',
        'texto_opcion',
        'es_correcta',
    ];

    public function pregunta()
    {
        return $this->belongsTo(PreguntaQuiz::class, 'pregunta_id', 'id_pregunta');
    }
}