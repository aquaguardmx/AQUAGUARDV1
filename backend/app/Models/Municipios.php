<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Municipios extends Model
{
    protected $table = 'municipios';

    protected $primaryKey = 'id_municipio';

    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'id_estado',
    ];

    // Relación con Estado
    public function estado()
    {
        return $this->belongsTo(Estados::class, 'id_estado');
    }
}