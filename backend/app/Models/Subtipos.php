<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subtipos extends Model
{
    protected $table = 'subtipos';

    protected $primaryKey = 'id_subtipo';

    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
    ];
}