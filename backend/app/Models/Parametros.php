<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parametros extends Model
{
    protected $table = 'parametros';

    protected $primaryKey = 'id_parametro';

    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = false;  // Si no tiene timestamps

    protected $fillable = [
        'nombre',
        'unidad',
        'norma_min',
        'norma_max',
        'descripcion',
        'definicion',
        'contaminantes_contribuyentes',
    ];

    //Define la relación "un parametro tiene muchas clasificaciones"
    public function clasificaciones()
    {
        return $this->hasMany(ParametroClasificacion::class, 'parametro_id')
                    ->orderBy('orden');
    }

    //Metodo para clasificar un valor en este parámetro
    public function clasificarValor($valor)
    {
        return $this->clasificaciones()
            ->where(function($q) use ($valor) {
                $q->whereNull('min_value')
                  ->orWhere('min_value', '<', $valor);
            })
            ->where(function($q) use ($valor) {
                $q->whereNull('max_value')
                  ->orWhere('max_value', '>=', $valor);
            })
            ->orderBy('orden')
            ->first();
    }
}