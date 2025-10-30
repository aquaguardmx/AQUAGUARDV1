<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    /** @use HasFactory<\Database\Factories\CursoFactory> */
    use HasFactory;

    protected $table = 'categorias';

    protected $primaryKey = 'id';

    // Es una clave autoincremental entera
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'nombre',
        'slug',
    ];

    public function contenidos()
    {
        return $this->hasMany(Contenido::class, 'id');
    }
}
