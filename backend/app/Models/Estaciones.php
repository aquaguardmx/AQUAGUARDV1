<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estaciones extends Model
{
    protected $table = 'estaciones';

    protected $primaryKey = 'id_estacion'; 

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'clave_sitio',
        'nombre',
        'latitud',
        'longitud',
        'activo',
        'id_tipo',
        'id_subtipo',
        'id_cuenca',
        'id_municipio',
        'id_usuario',
    ];

    // Relaciones con tablas relacionadas
    public function tipo()
    {
        return $this->belongsTo(Tipos::class, 'id_tipo');  
    }
    public function subtipo()
    {
        return $this->belongsTo(Subtipos::class, 'id_subtipo');  
    }
    public function cuenca()
    {
        return $this->belongsTo(Cuencas::class, 'id_cuenca');  
    }
    public function municipio()
    {
        return $this->belongsTo(Municipios::class, 'id_municipio');  
    }
    public function estado()
    { 
        // Indirecta via municipio
        return $this->belongsToThrough(Estados::class, Municipio::class, 'id_municipio', 'id_estado');  
    }
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');  
    }
    public function mediciones()
    {
        return $this->hasMany(Mediciones::class, 'id_estacion');  
    }
    public function semaforo()
    {
        // Ordenamos por fecha_medicion descendente para obtener el más reciente primero
        return $this->hasMany(Semaforo::class, 'id_estacion')->orderBy('fecha_medicion', 'desc');  
    }

}