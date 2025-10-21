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
        Schema::create('opciones_quiz', function (Blueprint $table) {
            $table->increments('id_opcion_quiz');
            $table->string('texto_opcion');
            $table->boolean('es_correcta');
            $table->integer('id_pregunta_leccion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opciones_quiz');
    }
};
