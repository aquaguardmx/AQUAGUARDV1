<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CursosFavoritosUsuarios extends Model
{
    /** @use HasFactory<\Database\Factories\CursoFactory> */
    use HasFactory;

    protected $table = 'cursos_favoritos';

    protected $primaryKey = 'id';

    // Es una clave autoincremental entera
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'course_id',
    ];
}
