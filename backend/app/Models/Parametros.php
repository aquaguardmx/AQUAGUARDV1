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
}