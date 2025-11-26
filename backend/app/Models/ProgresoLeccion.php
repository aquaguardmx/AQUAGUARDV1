<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgresoLeccion extends Model
{
    use HasFactory;

    protected $table = 'progreso_lecciones';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = null;

    protected $fillable = [
        'usuario_id',
        'leccion_id',
        'fecha_completado',
    ];

    /**
     * Get the user that owns the progress.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Get the lesson associated with the progress.
     */
    public function leccion()
    {
        return $this->belongsTo(Leccion::class, 'leccion_id');
    }
}
