<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mediciones extends Model
{
    protected $table = 'mediciones';

    protected $primaryKey = 'id_medicion';

    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'fecha_medicion',
        'valor',
        'clasificacion',
        'observaciones',
        'id_estacion',
        'id_parametro',
        'id_usuario',
    ];

    // Relaciones
    public function estacion()
    {
        return $this->belongsTo(Estaciones::class, 'id_estacion');
    }

    public function parametro()
    {
        return $this->belongsTo(Parametros::class, 'id_parametro');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}