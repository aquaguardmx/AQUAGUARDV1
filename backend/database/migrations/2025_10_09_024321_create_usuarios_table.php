<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->increments('id_usuario');
            $table->string('nombre', 20);
            $table->string('ap_paterno', 15);
            $table->string('ap_materno', 15);
            $table->string('correo_electronico', 50);
            $table->timestamp('fecha_registro');
            $table->string('contrasena');
            $table->integer('id_rol');
            $table->integer('id_escuela')->nullable();
            $table->timestamps();
            $table->rememberToken();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
