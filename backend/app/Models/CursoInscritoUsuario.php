<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User; // <-- AÑADE ESTA LÍNEA
use App\Models\Curso; // <-- AÑADE ESTA LÍNEA

class CursoInscritoUsuario extends Model
{
    use HasFactory;

    protected $table = 'cursos_inscritos';

    // Desactivar la funcionalidad de llave primaria
    protected $primaryKey = null;
    public $incrementing = false;

    protected $fillable = [
        'usuario_id',
        'curso_id',
    ];

    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->fecha_inscripcion = now();
        });
    }

    // Sobrescribir el método para evitar usar llave primaria
    public function getKeyName()
    {
        return null;
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'curso_id', 'id_curso');
    }
}