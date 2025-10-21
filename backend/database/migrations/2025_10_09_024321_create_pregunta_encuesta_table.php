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
        Schema::create('pregunta_encuesta', function (Blueprint $table) {
            $table->increments('id_pregunta_encuesta');
            $table->string('pregunta_encuesta');
            $table->integer('id_nivel_trivia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pregunta_encuesta');
    }
};
