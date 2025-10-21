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
        Schema::create('intento_trivia', function (Blueprint $table) {
            $table->increments('id_intento_trivia');
            $table->timestamp('fecha_intento');
            $table->smallInteger('puntuacion');
            $table->smallInteger('nivel_evaluado');
            $table->integer('numero_intento');
            $table->integer('id_trivia');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intento_trivia');
    }
};
