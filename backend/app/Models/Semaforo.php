<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Semaforo extends Model
{
    protected $table = 'semaforo';

    protected $primaryKey = 'id_semaforo';

    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'fecha_medicion',
        'color',
        'contaminantes',
        'id_estacion',
    ];

    // Relación con Estación
    public function estacion()
    {
        return $this->belongsTo(Estaciones::class, 'id_estacion');
    }
}