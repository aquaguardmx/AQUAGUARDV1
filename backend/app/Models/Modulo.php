<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Contenido;

class Modulo extends Model
{
    /** @use HasFactory<\Database\Factories\CursoFactory> */
    use HasFactory;

    protected $table = 'modulos'; // <-- AÑADE ESTA LÍNEA

    public $timestamps = false;

    // La llave primaria no es `id` sino `id_curso` según la migración
    protected $primaryKey = 'id_modulo';

    // Es una clave autoincremental entera
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'curso_id',
        'titulo',
        'orden',
    ];

    public function contenidos()
    {
        return $this->hasMany(Contenido::class, 'id_modulo');
    }
}
