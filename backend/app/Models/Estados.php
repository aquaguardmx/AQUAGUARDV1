<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estados extends Model
{
    protected $table = 'estados';

    protected $primaryKey = 'id_estado';

    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'nombre',
    
    ];
}