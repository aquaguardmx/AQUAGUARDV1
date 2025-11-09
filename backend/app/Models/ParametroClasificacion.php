<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParametroClasificacion extends Model
{
    use HasFactory;

    protected $table = 'parametro_clasificaciones';

    protected $primaryKey = 'id';

    protected $fillable = [
        'parametro_id',
        'categoria',
        'color',
        'orden',
        'min_value',
        'max_value'
    ];

    protected $casts = [
        'min_value' => 'decimal:2',
        'max_value' => 'decimal:2',
        'orden' => 'integer',
        'parametro_id' => 'integer'
    ];

    public function parametro()
    {
        return $this->belongsTo(Parametro::class, 'parametro_id');
    }

    //Metodo para clasificar un valor directamente
    public static function clasificar($parametroId, $valor)
    {
        return self::where('parametro_id', $parametroId)
            ->where(function($query) use ($valor) {
                $query->whereNull('min_value')
                      ->orWhere('min_value', '<', $valor);
            })
            ->where(function($query) use ($valor) {
                $query->whereNull('max_value')
                      ->orWhere('max_value', '>=', $valor);
            })
            ->orderBy('orden')
            ->first();
    }

}