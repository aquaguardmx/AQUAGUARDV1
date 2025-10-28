<?php
/*
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mapa extends Model
{
protected $table = 'estaciones';

    protected $primaryKey = 'id_estacion';  // Fix: Tu tabla usa id_estacion

    public $incrementing = true;
    protected $keyType = 'int';

    // Timestamps automáticos
    public $timestamps = true;

    // Fillable para todos los campos de estaciones (agrega más si hay)
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
        'created_at',
        'updated_at',
    ];
}*/


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mapa extends Model
{
    protected $table = 'estaciones';

    protected $primaryKey = 'id_estacion';  // Clave primaria real de tu tabla

    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = true;

    // Fillable para todos los campos (agrega más si hay)
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
        return $this->belongsTo(Tipos::class, 'id_tipo');  // Model Tipo para 'tipos'
    }

    public function subtipo()
    {
        return $this->belongsTo(Subtipos::class, 'id_subtipo');  // Model Subtipo para 'subtipos'
    }

    public function cuenca()
    {
        return $this->belongsTo(Cuencas::class, 'id_cuenca');  // Model Cuenca para 'cuencas'
    }

    public function municipio()
    {
        return $this->belongsTo(Municipios::class, 'id_municipio');  // Model Municipio para 'municipios'
    }

    public function estado()
    {
        return $this->belongsToThrough(Estados::class, Municipio::class, 'id_municipio', 'id_estado');  // Indirecta via municipio
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');  // Model User para 'usuarios'
    }

    public function mediciones()
    {
        return $this->hasMany(Mediciones::class, 'id_estacion');  // Model Medicion para 'mediciones'
    }

    public function semaforo()
    {
        return $this->hasMany(Semaforo::class, 'id_estacion')->orderBy('fecha_medicion', 'desc');  // Más reciente primero
    }

    // Accesor para semáforo más reciente (opcional, para usar en JSON)
    public function getUltimoSemaforoAttribute()
    {
        return $this->semaforo->first();  // El primero (más reciente)
    }
}