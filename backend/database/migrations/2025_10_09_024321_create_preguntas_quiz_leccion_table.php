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
        Schema::create('preguntas_quiz_leccion', function (Blueprint $table) {
            $table->increments('id_pregunta_leccion');
            $table->string('pregunta');
            $table->string('tipo_pregunta', 20);
            $table->integer('id_quiz_leccion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preguntas_quiz_leccion');
    }
};
