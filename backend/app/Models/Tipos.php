<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tipos extends Model
{
    protected $table = 'tipos';

    protected $primaryKey = 'id_tipo';

    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = false;  // Si no tiene created_at/updated_at

    protected $fillable = [
        'nombre',
    ];
}