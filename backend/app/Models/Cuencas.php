<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuencas extends Model
{
    protected $table = 'cuencas';

    protected $primaryKey = 'id_cuenca';

    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'descripcion',

    ];
}