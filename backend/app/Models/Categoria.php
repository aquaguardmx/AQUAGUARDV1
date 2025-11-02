<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';

    public $timestamps = false; // Desactiva el manejo automático de ambos

    const CREATED_AT = 'created_at'; // Solo dejamos activo created_at

    protected $fillable = [
        'nombre',
        'slug',
        'created_at',
    ];

    // Si quieres seguir usando created_at automáticamente:
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Solo se establecerá created_at al crear, no se tocará updated_at
            if (! $model->created_at) {
                $model->created_at = now();
            }
        });
    }
}
