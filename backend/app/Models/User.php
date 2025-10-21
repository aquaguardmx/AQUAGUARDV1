<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;



class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    protected $table = 'usuarios'; // <-- AÑADE ESTA LÍNEA

    // La llave primaria no es `id` sino `id_usuario` según la migración
    protected $primaryKey = 'id_usuario';

    // Es una clave autoincremental entera
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'nombre',
        'ap_paterno',
        'ap_materno',
        'correo_electronico',
        'contrasena',
        'id_rol',
        'id_escuela',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'contrasena',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    /**
     * The attributes that should be cast.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        // Si el nombre de la columna de la contraseña es `contrasena`, aplicamos el cast hashed
        'contrasena' => 'hashed',
    ];

    /**
     * Relación con la escuela (si existe la tabla/modelo Escuela).
     */
    public function escuela()
    {
        return $this->belongsTo('App\\Models\\Escuela', 'id_escuela');
    }

    /**
     * Relación con el rol (si existe la tabla/modelo Role).
     */
    public function role()
    {
        return $this->belongsTo('App\\Models\\Role', 'id_rol');
    }

    /**
     * Mapear el campo personalizado `correo_electronico` para que
     * las notificaciones de Laravel (mail) lo utilicen como dirección.
     *
     * Esto hace dos cosas útiles:
     * - Permite que routeNotificationForMail devuelva la dirección correcta.
     * - Provee un accesor `email` para compatibilidad con paquetes/expectativas.
     */
    public function routeNotificationForMail($notification)
    {
        return $this->correo_electronico;
    }

    /**
     * Devuelve la dirección que se usará para la verificación de correo.
     * Laravel puede usar este método en contextos de verificación expícitos.
     */
    public function getEmailForVerification()
    {
        return $this->correo_electronico;
    }

    /**
     * Accesor para `$user->email` (compatibilidad con código que espera la columna `email`).
     */
    public function getEmailAttribute()
    {
        return $this->correo_electronico;
    }
}
