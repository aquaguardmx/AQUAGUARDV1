<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObjetivoDeAprendizaje extends Model
{
    use HasFactory;

    /**
     * Define el nombre de la tabla si no sigue la convención de pluralización de Laravel (learning_objectives).
     * Si tu tabla se llama 'objetivo_de_aprendizajes', esta línea es opcional.
     * Si se llama 'objetivo_de_aprendizaje', debes usarla.
     * @var string
     */
    protected $table = 'objetivos_aprendizaje'; 

    public $timestamps = false;
    // Por ejemplo: protected $table = 'objetivo_de_aprendizaje';

    /**
     * Los atributos que son asignables masivamente (Mass Assignable).
     * Esto es crucial para que el método `insert()` del controlador funcione.
     * @var array
     */
    protected $fillable = [
        'descripcion',
        'curso_id', // ¡Crucial para la llave foránea!
    ];
    
    protected $primaryKey = 'id_objetivo';

    // --- Relaciones ---

    /**
     * Obtiene el curso al que pertenece este objetivo.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function curso()
    {
        return $this->belongsTo(Curso::class, 'curso_id', 'id_curso');
    }
}